<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function index()
    {
        $transfers = StockTransfer::with('fromWarehouse', 'toWarehouse', 'product')->get();

        return view('admin_panel.warehouses.stock_transfers.index', compact('transfers'));
    }

    public function create()
    {
        $warehouses = Warehouse::all();
        $products = Product::all();

        return view('admin_panel.warehouses.stock_transfers.create', compact('warehouses', 'products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|integer',
            'product_id' => 'required|array',
            'product_id.*' => 'required|integer|exists:products,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|integer|min:1',
        ]);

        $fromWarehouse = $request->from_warehouse_id;
        $toWarehouse = $request->to_warehouse_id;
        $toShop = $request->to_shop ? true : false;
        $remarks = $request->remarks;

        // Aggregate quantities by product
        $groupped = [];
        foreach ($request->product_id as $index => $pid) {
            $qty = (int) ($request->quantity[$index] ?? 0);
            if ($qty > 0) {
                if (! isset($groupped[$pid])) {
                    $groupped[$pid] = 0;
                }
                $groupped[$pid] += $qty;
            }
        }

        if ($fromWarehouse == $toWarehouse && ! $toShop) {
            return back()->with('error', 'Source and Destination warehouses cannot be the same.');
        }

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($fromWarehouse, $toWarehouse, $toShop, $remarks, $groupped) {

                foreach ($groupped as $productId => $totalQty) {
                    // Check source stock for TOTAL quantity
                    $sourceStock = WarehouseStock::where('warehouse_id', $fromWarehouse)
                        ->where('product_id', $productId)
                        ->lockForUpdate()
                        ->first();

                    $prod = Product::find($productId);
                    $productName = $prod->item_name ?? 'Product #'.$productId;
                    $ppb = ($prod && $prod->pieces_per_box > 0) ? (float) $prod->pieces_per_box : 1.0;
                    $transferredPieces = (float) $totalQty * $ppb;

                    $sourcePieces = $sourceStock
                        ? ($sourceStock->total_pieces != 0 ? (float) $sourceStock->total_pieces : ((float) $sourceStock->quantity * $ppb))
                        : 0.0;

                    if (! $sourceStock || $sourcePieces < $transferredPieces) {
                        $availBoxes = $ppb > 0 ? ($sourcePieces / $ppb) : $sourcePieces;
                        throw new \Exception("Insufficient stock for {$productName}. Requested Total: {$totalQty}, Available: " . round($availBoxes, 2));
                    }

                    // Reduce stock from source (both total_pieces and quantity)
                    $newSourcePieces = max(0.0, $sourcePieces - $transferredPieces);
                    $sourceStock->total_pieces = $newSourcePieces;
                    $sourceStock->quantity = $ppb > 0 ? ($newSourcePieces / $ppb) : $newSourcePieces;
                    $sourceStock->save();

                    // Setup destination
                    if (! $toShop && $toWarehouse) {
                        $destStock = WarehouseStock::firstOrCreate(
                            [
                                'warehouse_id' => $toWarehouse,
                                'product_id' => $productId,
                            ],
                            [
                                'quantity' => 0,
                                'total_pieces' => 0,
                                'price' => $sourceStock->price ?? 0,
                            ]
                        );
                        $destPieces = ($destStock->total_pieces != 0 ? (float) $destStock->total_pieces : ((float) $destStock->quantity * $ppb)) + $transferredPieces;
                        $destStock->total_pieces = $destPieces;
                        $destStock->quantity = $ppb > 0 ? ($destPieces / $ppb) : $destPieces;
                        $destStock->save();
                    }

                    if ($prod) {
                        $stockService = app(\App\Services\StockBalanceService::class);
                        $stockService->syncBatchStockOnDelta($prod, (int) $fromWarehouse, -1 * $transferredPieces, null, null, null, 'Transfer Out');
                        if (! $toShop && $toWarehouse) {
                            $stockService->syncBatchStockOnDelta($prod, (int) $toWarehouse, $transferredPieces, null, null, null, 'Transfer In');

                            if ($prod->tracking_type === 'serial') {
                                $serialIds = \App\Models\ProductSerial::where('product_id', $productId)
                                    ->where('warehouse_id', $fromWarehouse)
                                    ->where('status', 'available')
                                    ->orderBy('id', 'asc')
                                    ->limit((int) $transferredPieces)
                                    ->pluck('id');
                                if ($serialIds->isNotEmpty()) {
                                    \App\Models\ProductSerial::whereIn('id', $serialIds)
                                        ->update(['warehouse_id' => $toWarehouse]);
                                }
                            }
                        }
                    }

                    // Record Transfer (One record per aggregated product, or one per line? Plan implies logical transfer.
                    // Aggregating into one transfer record per product type is cleaner for stock history)
                    $transfer = StockTransfer::create([
                        'from_warehouse_id' => $fromWarehouse,
                        'to_warehouse_id' => $toShop ? null : $toWarehouse,
                        'to_shop' => $toShop,
                        'product_id' => $productId,
                        'quantity' => $totalQty,
                        'remarks' => $remarks,
                    ]);

                    // Log Movement (OUT)
                    \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
                        'product_id' => $productId,
                        'type' => 'out',
                        'qty' => -1 * abs($totalQty),
                        'ref_type' => 'TRANSFER_OUT',
                        'ref_id' => $transfer->id,
                        'note' => 'Transfer to '.($toShop ? 'Shop' : "Warehouse $toWarehouse"),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Log Movement (IN)
                    if (! $toShop && $toWarehouse) {
                        \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
                            'product_id' => $productId,
                            'type' => 'in',
                            'qty' => abs($totalQty),
                            'ref_type' => 'TRANSFER_IN',
                            'ref_id' => $transfer->id,
                            'note' => "Transfer from Warehouse $fromWarehouse",
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        } catch (\Exception $e) {
            return back()->with('error', 'Transfer Failed: '.$e->getMessage());
        }

        return redirect()->route('stock_transfers.index')->with('success', 'Stock transferred successfully.');
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        // Optional: reverse the transfer if needed
        return back()->with('error', 'Deleting transfers not allowed.');
    }

    public function getStockQuantity(Request $request)
    {
        $stock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->first();

        return response()->json([
            'quantity' => $stock ? $stock->quantity : 0,
        ]);
    }

    public function getProductsByWarehouse(Request $request)
    {
        $warehouseId = $request->warehouse_id;
        if (! $warehouseId) {
            return response()->json([]);
        }

        $products = Product::join('warehouse_stocks', 'products.id', '=', 'warehouse_stocks.product_id')
            ->where('warehouse_stocks.warehouse_id', $warehouseId)
            ->where('warehouse_stocks.quantity', '>', 0)
            ->select('products.id', 'products.item_name')
            ->get();

        return response()->json($products);
    }
}

// delvivery challan
// convet out per  stock ledger maintain
