<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockEntryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseStockController extends Controller
{
    protected $stockService;

    public function __construct(StockEntryService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getByWarehouse($warehouseId)
    {
        $products = WarehouseStock::with('product')
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->product->id,
                    'name' => $row->product->item_name,
                    'qty' => $row->quantity,
                ];
            });

        return response()->json($products);
    }

    public function searchWarehouses(Request $request)
    {
        $term = $request->get('q', '');

        $warehouses = Warehouse::query()
            ->select('id', 'warehouse_name')
            ->when($term, function ($query) use ($term) {
                $query->where('warehouse_name', 'like', "%{$term}%");
            })
            ->limit(20)
            ->get();

        return response()->json($warehouses->map(function ($w) {
            return [
                'id' => $w->id,
                'text' => $w->warehouse_name,
            ];
        }));
    }

    public function index(Request $request)
    {
        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $query = WarehouseStock::with('warehouse', 'product');

        if ($request->filled('warehouse_id') && $request->warehouse_id !== 'all') {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        $stocks = $query->latest()->paginate(25)->appends($request->query());

        return view('admin_panel.warehouses.warehouse_stocks.index', compact('stocks', 'warehouses'));
    }

    public function show($id)
    {
        return redirect()->route('warehouse_stocks.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'total_pieces' => 'required|integer|min:0',
            'total_box' => 'required|integer|min:0',
            'remarks' => 'nullable|string|max:255',
        ]);

        try {
            $this->stockService->addStock(
                $validated['warehouse_id'],
                $validated['product_id'],
                $validated['total_pieces'],
                $validated['total_box'],
                $request->input('remarks')
            );

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding stock: '.$e->getMessage(),
            ], 500);
        }
    }

    public function editData($id)
    {
        $stock = WarehouseStock::with(['product', 'warehouse'])->findOrFail($id);

        $piecesPerBox = $stock->product->pieces_per_box ?? 0;
        $boxes = 0;
        $loose = $stock->total_pieces;

        if ($piecesPerBox > 0) {
            $boxes = floor($stock->total_pieces / $piecesPerBox);
            $loose = $stock->total_pieces % $piecesPerBox;
        }

        return response()->json([
            'id' => $stock->id,
            'warehouse_id' => $stock->warehouse_id,
            'warehouse_name' => $stock->warehouse->warehouse_name ?? '',
            'product_id' => $stock->product_id,
            'product_name' => $stock->product->item_name,
            'product_code' => $stock->product->item_code,
            'pieces_per_box' => $piecesPerBox,
            'total_pieces' => $stock->total_pieces,
            'image' => $stock->product->image ? asset('uploads/products/'.$stock->product->image) : null,
            'remarks' => $stock->remarks,
        ]);
    }

    public function update(Request $request, $id)
    {
        $warehouseStock = WarehouseStock::findOrFail($id);

        $request->validate([
            'total_pieces' => 'required|integer|min:0',
            'remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $warehouseStock) {
            $oldQty = $warehouseStock->total_pieces;
            $newQty = $request->total_pieces;
            $delta = $newQty - $oldQty;

            if ($delta != 0) {
                $ppb = ($warehouseStock->product && $warehouseStock->product->pieces_per_box > 0) ? $warehouseStock->product->pieces_per_box : 1;
                $warehouseStock->total_pieces = $newQty;
                $warehouseStock->quantity = $newQty / $ppb;
                $warehouseStock->remarks = $request->remarks;
                $warehouseStock->save();

                if ($warehouseStock->product) {
                    app(\App\Services\StockBalanceService::class)->syncBatchStockOnDelta(
                        $warehouseStock->product,
                        (int) $warehouseStock->warehouse_id,
                        (float) $delta,
                        null,
                        null,
                        null,
                        'Manual Stock Edit via Modal'
                    );
                }

                // Log Movement safely without non-existent warehouse_id column
                DB::table('stock_movements')->insert([
                    'product_id' => $warehouseStock->product_id,
                    'type' => 'adjustment',
                    'qty' => $delta,
                    'ref_type' => 'MANUAL_EDIT_MODAL',
                    'ref_id' => $warehouseStock->id,
                    'note' => 'Manual Stock Edit via Modal (WH #' . $warehouseStock->warehouse_id . ')',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully!',
        ]);
    }

    public function destroy(WarehouseStock $warehouseStock)
    {
        $warehouseStock->delete();

        return back()->with('success', 'Stock deleted successfully.');
    }

    public function searchProducts(Request $request)
    {
        $term = $request->get('q', '');

        $products = Product::query()
            ->select('id', 'item_name', 'item_code', 'pieces_per_box', 'image')
            ->when($term, function ($query) use ($term) {
                $query->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%");
            })
            ->limit(20)
            ->get();

        return response()->json($products->map(function ($p) {
            return [
                'id' => $p->id,
                'text' => "{$p->item_code} - {$p->item_name}",
                'item_name' => $p->item_name,
                'item_code' => $p->item_code,
                'pieces_per_box' => $p->pieces_per_box ?? 0,
                'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
            ];
        }));
    }

    public function getWarehouseStock(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $stock = WarehouseStock::where('warehouse_id', $request->warehouse_id)
            ->where('product_id', $request->product_id)
            ->first();

        return response()->json([
            'total_pieces' => $stock ? $stock->total_pieces : 0,
        ]);
    }
}
