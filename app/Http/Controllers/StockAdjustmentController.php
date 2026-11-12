<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    /**
     * Check if user is authorized for stock adjustments
     */
    private function checkPermission()
    {
        $user = auth()->user();
        if ($user->email === 'admin@admin.com' || $user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return true;
        }

        if ($user->can('stock.adjust.view') || $user->can('stock.adjust.create')) {
            return true;
        }

        abort(403, 'Unauthorized action. You do not have permission to access Stock Adjustments.');
    }

    /**
     * Get warehouses permitted for current user
     */
    private function getPermittedWarehouses()
    {
        $user = auth()->user();

        if ($user->email === 'admin@admin.com' || $user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->can('warehouse.view')) {
            return Warehouse::orderBy('warehouse_name')->get();
        }

        if (isset($user->warehouse_id) && $user->warehouse_id) {
            return Warehouse::where('id', $user->warehouse_id)->get();
        }

        $userWarehouses = Warehouse::where('creater_id', $user->id)->get();
        if ($userWarehouses->count() > 0) {
            return $userWarehouses;
        }

        return Warehouse::limit(1)->get();
    }

    /**
     * Audit Log Index Page
     */
    public function index(Request $request)
    {
        $this->checkPermission();

        $permittedWarehouses = $this->getPermittedWarehouses();
        $permittedWarehouseIds = $permittedWarehouses->pluck('id')->toArray();

        $query = StockAdjustment::with(['user', 'warehouse', 'product'])
            ->whereIn('warehouse_id', $permittedWarehouseIds);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('item_name', 'like', "%{$search}%")
                       ->orWhere('item_code', 'like', "%{$search}%");
                })->orWhere('variant_name', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $adjustments = $query->latest()->paginate(20)->appends($request->query());

        // Summary Stats
        $totalAdjustments = StockAdjustment::whereIn('warehouse_id', $permittedWarehouseIds)->count();
        $totalAdded       = StockAdjustment::whereIn('warehouse_id', $permittedWarehouseIds)->where('type', 'add')->sum('qty');
        $totalSubtracted  = StockAdjustment::whereIn('warehouse_id', $permittedWarehouseIds)->where('type', 'subtract')->sum('qty');
        $thisMonthCount   = StockAdjustment::whereIn('warehouse_id', $permittedWarehouseIds)
                                          ->whereMonth('created_at', now()->month)
                                          ->whereYear('created_at', now()->year)
                                          ->count();

        $products = Product::select('id', 'item_name', 'item_code', 'color', 'size_mode', 'pieces_per_box')->get();

        return view('admin_panel.stock_adjustment.index', [
            'adjustments'       => $adjustments,
            'totalAdjustments'  => $totalAdjustments,
            'totalAdded'        => $totalAdded,
            'totalSubtracted'   => $totalSubtracted,
            'thisMonthCount'    => $thisMonthCount,
            'warehouses'        => $permittedWarehouses,
            'products'          => $products,
        ]);
    }

    /**
     * Dedicated POS-Style Stock Adjustment Terminal Screen
     */
    public function create(Request $request)
    {
        $this->checkPermission();

        $warehouses = $this->getPermittedWarehouses();
        $categories = Category::all();
        $brands     = Brand::all();

        // Fetch products with variant details
        $products = Product::with(['category_relation', 'brand'])
            ->select('id', 'item_name', 'item_code', 'barcode_path', 'image', 'category_id', 'brand_id', 'color', 'size_mode', 'pieces_per_box')
            ->get();

        return view('admin_panel.stock_adjustment.create', compact('warehouses', 'categories', 'brands', 'products'));
    }

    public function getProductVariants(Request $request, $productId)
    {
        $this->checkPermission();

        $product = Product::findOrFail($productId);
        $warehouseId = $request->query('warehouse_id');
        if (!$warehouseId) {
            $warehouseId = $this->getPermittedWarehouses()->first()->id ?? 1;
        }

        $stockPieces = 0;
        if ($warehouseId) {
            $whStock = WarehouseStock::where('warehouse_id', $warehouseId)->where('product_id', $productId)->first();
            $stockPieces = (float) ($whStock->total_pieces ?? 0);
        } else {
            $stockPieces = (float) WarehouseStock::where('product_id', $productId)->sum('total_pieces');
        }

        $variants = [];
        if ($product->color) {
            try {
                $parsed = is_string($product->color) ? json_decode($product->color, true) : $product->color;
                if (is_array($parsed) && count($parsed) > 0) {
                    if (isset($parsed[0]['name']) || isset($parsed[0]['color'])) {
                        foreach ($parsed as $v) {
                            $vName = $v['name'] ?? $product->item_name;
                            $vSize = $v['size'] ?? '-';
                            $vColor = $v['color'] ?? '-';
                            $vInitial = (float)($v['stock'] ?? 0);

                            // Calculate live variant stock balance
                            $purchased = DB::table('purchase_items as pi')
                                ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
                                ->where('pi.product_id', $productId)
                                ->whereIn('pur.status_purchase', ['approved', 'Returned', 'Partial'])
                                ->where(function($q) use ($vColor) {
                                    if ($vColor !== '-') $q->where('pi.color', 'like', "%{$vColor}%");
                                })
                                ->sum('pi.qty');

                            $sold = DB::table('sale_items')
                                ->where('product_id', $productId)
                                ->where(function($q) use ($vColor) {
                                    if ($vColor !== '-') $q->where('color', 'like', "%{$vColor}%");
                                })
                                ->sum('total_pieces');

                            $returned = DB::table('sale_return_items')
                                ->where('product_id', $productId)
                                ->where(function($q) use ($vColor) {
                                    if ($vColor !== '-') $q->where('color', 'like', "%{$vColor}%");
                                })
                                ->sum('qty');

                            $vCurrentStock = max(0, $vInitial + $purchased - $sold + $returned);

                            $variants[] = [
                                'variant_key'   => ($vName . '|' . $vSize . '|' . $vColor),
                                'name'          => $vName,
                                'size'          => $vSize,
                                'color'         => $vColor,
                                'initial_stock' => $vInitial,
                                'current_stock' => $vCurrentStock,
                                'display_label' => $vName . (!empty(array_filter([($vSize !== '-' && !empty($vSize) ? 'Size: ' . $vSize : null), ($vColor !== '-' && !empty($vColor) ? 'Color: ' . $vColor : null)])) ? ' (' . implode(', ', array_filter([($vSize !== '-' && !empty($vSize) ? 'Size: ' . $vSize : null), ($vColor !== '-' && !empty($vColor) ? 'Color: ' . $vColor : null)])) . ')' : '')
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {}
        }

        return response()->json([
            'success'        => true,
            'product_id'     => $product->id,
            'product_name'   => $product->item_name,
            'item_code'      => $product->item_code,
            'total_stock'    => $stockPieces,
            'has_variants'   => count($variants) > 0,
            'variants'       => $variants
        ]);
    }

    /**
     * Store Single Adjustment
     */
    public function store(Request $request)
    {
        $this->checkPermission();

        if (!$request->filled('warehouse_id')) {
            $firstWh = $this->getPermittedWarehouses()->first();
            if ($firstWh) {
                $request->merge(['warehouse_id' => $firstWh->id]);
            }
        }

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id'   => 'required|exists:products,id',
            'type'         => 'required|in:add,subtract,set',
            'qty'          => 'required|numeric|min:0',
            'reason'       => 'required|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $this->processSingleAdjustment([
                    'warehouse_id' => $request->warehouse_id,
                    'product_id'   => $request->product_id,
                    'variant_key'  => $request->variant_key,
                    'variant_name' => $request->variant_name,
                    'type'         => $request->type,
                    'qty'          => (float) $request->qty,
                    'reason'       => $request->reason,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock adjustment processed successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store Multi-Item / Multi-Variant Batch Adjustment
     */
    public function storeBatch(Request $request)
    {
        $this->checkPermission();

        if (!$request->filled('warehouse_id')) {
            $firstWh = $this->getPermittedWarehouses()->first();
            if ($firstWh) {
                $request->merge(['warehouse_id' => $firstWh->id]);
            }
        }

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'items'        => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.type'       => 'required|in:add,subtract,set',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'global_reason'      => 'required|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $warehouseId   = $request->warehouse_id;
                $globalReason  = $request->global_reason;

                foreach ($request->items as $item) {
                    $itemReason = !empty($item['reason']) ? $item['reason'] : $globalReason;

                    $this->processSingleAdjustment([
                        'warehouse_id' => $warehouseId,
                        'product_id'   => $item['product_id'],
                        'variant_key'  => $item['variant_key'] ?? null,
                        'variant_name' => $item['variant_name'] ?? null,
                        'type'         => $item['type'],
                        'qty'          => (float) $item['qty'],
                        'reason'       => $itemReason,
                    ]);
                }
            });

            return response()->json([
                'success' => true,
                'message' => count($request->items) . ' stock adjustment(s) saved successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process batch adjustment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Private helper to execute a single stock adjustment
     */
    private function processSingleAdjustment(array $data)
    {
        $product = Product::where('id', $data['product_id'])->lockForUpdate()->first();

        $warehouseStock = WarehouseStock::firstOrCreate(
            [
                'warehouse_id' => $data['warehouse_id'],
                'product_id'   => $data['product_id'],
            ],
            [
                'quantity'     => 0,
                'total_pieces' => 0,
            ]
        );

        $variantKey  = $data['variant_key'];
        $variantName = $data['variant_name'];

        $oldStock = (float) $warehouseStock->total_pieces;
        $inputQty = (float) $data['qty'];
        $deltaQty = 0;

        if ($data['type'] === 'add') {
            $deltaQty = $inputQty;
            $newStock = $oldStock + $deltaQty;
        } elseif ($data['type'] === 'subtract') {
            $deltaQty = -1 * min($oldStock, $inputQty);
            $newStock = max(0, $oldStock + $deltaQty);
        } else { // 'set'
            $deltaQty = $inputQty - $oldStock;
            $newStock = max(0, $inputQty);
        }

        // Adjust variant stock in product JSON if variant selected
        if ($variantKey && $product->color) {
            try {
                $parsed = is_string($product->color) ? json_decode($product->color, true) : $product->color;
                if (is_array($parsed)) {
                    foreach ($parsed as &$v) {
                        $vKey = ($v['name'] ?? $product->item_name) . '|' . ($v['size'] ?? '-') . '|' . ($v['color'] ?? '-');
                        if ($vKey === $variantKey || ($variantName && ($v['name'] ?? '') === $variantName)) {
                            $vOld = (float)($v['stock'] ?? 0);
                            $v['stock'] = max(0, $vOld + $deltaQty);
                            break;
                        }
                    }
                    unset($v);
                    $product->color = json_encode($parsed);
                    $product->save();
                }
            } catch (\Exception $e) {}
        }

        // Update WarehouseStock
        $warehouseStock->total_pieces = max(0, $oldStock + $deltaQty);
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
        if ($ppb > 1 && in_array($product->size_mode, ['by_cartons', 'by_size'])) {
            $warehouseStock->boxes_quantity = floor($warehouseStock->total_pieces / $ppb);
            $warehouseStock->quantity       = $warehouseStock->boxes_quantity;
        } else {
            $warehouseStock->quantity       = $warehouseStock->total_pieces;
        }
        $warehouseStock->remarks = 'Adjusted via Stock Adjustment module';
        $warehouseStock->save();

        // Keep ProductBatch synced when adjustment changes physical stock
        app(\App\Services\StockBalanceService::class)->syncBatchStockOnDelta(
            $product,
            (int) $data['warehouse_id'],
            (float) $deltaQty,
            $variantKey ?: null,
            null,
            null,
            'Stock Adjustment: ' . ($data['reason'] ?? '')
        );

        // Log Stock Movement
        DB::table('stock_movements')->insert([
            'product_id'   => $product->id,
            'type'         => 'adjustment',
            'qty'          => $deltaQty,
            'ref_type'     => 'STOCK_ADJUSTMENT',
            'ref_id'       => null,
            'note'         => "Warehouse #{$data['warehouse_id']} | " . ($variantName ? "Variant: {$variantName} | " : '') . "Reason: " . $data['reason'],
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Create StockAdjustment audit record
        StockAdjustment::create([
            'user_id'      => auth()->id(),
            'warehouse_id' => $data['warehouse_id'],
            'product_id'   => $product->id,
            'variant_key'  => $variantKey,
            'variant_name' => $variantName ?: null,
            'type'         => $data['type'],
            'qty'          => $inputQty,
            'old_stock'    => $oldStock,
            'new_stock'    => $newStock,
            'reason'       => $data['reason'],
        ]);
    }
}
