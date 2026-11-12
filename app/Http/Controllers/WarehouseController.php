<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    // Return warehouses for a given product_id
    public function getWarehouses(Request $request)
    {
        $productId = $request->input('product_id');
        $variantData = $request->input('variant_data') ?? $request->input('variant_key');
        $product = $productId ? Product::find($productId) : null;
        $stockService = app(\App\Services\StockBalanceService::class);

        // Get all active warehouses first
        $allWarehouses = Warehouse::orderBy('warehouse_name')->get();

        $ppb = ($product && $product->pieces_per_box > 0) ? (float) $product->pieces_per_box : 1.0;
        $sizeMode = $product ? ($product->size_mode ?: 'std') : 'std';

        $response = $allWarehouses->map(function ($warehouse) use ($product, $variantData, $stockService, $ppb, $sizeMode) {
            $stockVal = $product
                ? $stockService->cleanNumber($stockService->getAvailableForSaleItem($product, $warehouse->id, is_string($variantData) ? $variantData : ($variantData ? json_encode($variantData) : null)))
                : 0.0;

            return [
                'warehouse_id' => $warehouse->id,
                'warehouse_name' => $warehouse->warehouse_name,
                'location' => $warehouse->location ?? '',
                'stock' => $stockVal, // Total pieces
                'boxes' => $ppb > 0 ? ($stockVal / $ppb) : 0,
                'ppb' => $product ? ($product->pieces_per_box ?? 1) : 1,
                'size_mode' => $sizeMode,
            ];
        });

        return response()->json($response);
    }

    public function index()
    {
        if (! auth()->user()->can('warehouse.view') && ! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $warehouses = Warehouse::with('user')
            ->withCount('stocks')
            ->withSum('stocks', 'total_pieces')
            ->orderBy('id', 'asc')
            ->get();

        // Summary metrics
        $totalWarehouses = $warehouses->count();
        $totalStockPieces = WarehouseStock::sum('total_pieces');
        $totalProductsStored = WarehouseStock::where('total_pieces', '>', 0)->distinct('product_id')->count('product_id');

        return view('admin_panel.warehouses.index', compact('warehouses', 'totalWarehouses', 'totalStockPieces', 'totalProductsStored'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|exists:warehouses,id',
            'warehouse_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        if ($request->filled('id')) {
            if (! auth()->user()->can('warehouse.edit') && ! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasRole('Admin')) {
                return back()->with('error', 'Unauthorized action to edit warehouse.');
            }

            $warehouse = Warehouse::findOrFail($request->id);
            $warehouse->update([
                'warehouse_name' => $request->warehouse_name,
                'location' => $request->location,
                'remarks' => $request->remarks,
            ]);

            return back()->with('success', 'Warehouse Updated Successfully');
        } else {
            if (! auth()->user()->can('warehouse.create') && ! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasRole('Admin')) {
                return back()->with('error', 'Unauthorized action to create warehouse.');
            }

            Warehouse::create([
                'warehouse_name' => $request->warehouse_name,
                'creater_id' => auth()->id(),
                'location' => $request->location,
                'remarks' => $request->remarks,
            ]);

            return back()->with('success', 'Warehouse Created Successfully');
        }
    }

    public function delete(Request $request, $id)
    {
        if (! auth()->user()->can('warehouse.delete') && ! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasRole('Admin')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Unauthorized action.'], 403);
            }
            return redirect('/warehouse')->with('error', 'Unauthorized action.');
        }

        $warehouse = Warehouse::findOrFail($id);

        // Check if warehouse has active stock
        $activeStockPieces = WarehouseStock::where('warehouse_id', $id)->where('total_pieces', '!=', 0)->sum('total_pieces');
        if ($activeStockPieces != 0) {
            $msg = "Cannot delete warehouse '{$warehouse->warehouse_name}' because it currently contains {$activeStockPieces} active stock pieces. Please transfer or adjust stock first.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return redirect('/warehouse')->with('error', $msg);
        }

        $warehouse->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => 'Warehouse Deleted Successfully',
                'reload' => true,
            ]);
        }

        return redirect('/warehouse')->with('success', "Warehouse '{$warehouse->warehouse_name}' Deleted Successfully");
    }

    /**
     * Complete Warehouse Working Report
     */
    public function workingReport(Request $request)
    {
        if (! auth()->user()->can('warehouse.view') && ! auth()->user()->can('warehouse.stock.view') && ! auth()->user()->hasRole('Super Admin') && ! auth()->user()->hasRole('Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $allWarehouses = Warehouse::orderBy('warehouse_name')->get();
        $categories = Category::orderBy('name')->get();

        $selectedWarehouseId = $request->query('warehouse_id', 'all');
        $categoryId = $request->query('category_id', 'all');
        $stockFilter = $request->query('stock_filter', 'all'); // 'all', 'in_stock', 'low_stock', 'zero_stock'
        $search = $request->query('search');

        $query = WarehouseStock::with(['warehouse', 'product.category_relation', 'product.brand', 'product.unit']);

        if ($selectedWarehouseId !== 'all' && !empty($selectedWarehouseId)) {
            $query->where('warehouse_id', $selectedWarehouseId);
        }

        if ($categoryId !== 'all' && !empty($categoryId)) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        if ($stockFilter === 'in_stock') {
            $query->where('total_pieces', '>', 0);
        } elseif ($stockFilter === 'zero_stock') {
            $query->where('total_pieces', '<=', 0);
        } elseif ($stockFilter === 'low_stock') {
            $query->whereHas('product', function ($q) {
                $q->whereNotNull('alert_carton_quantity')->where('alert_carton_quantity', '>', 0);
            });
        }

        if (!empty($search)) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        $stocks = $query->orderBy('warehouse_id')->orderBy('product_id')->paginate(50)->appends($request->query());

        // Global KPI Stats for this view/filter
        $statsQuery = WarehouseStock::query();
        if ($selectedWarehouseId !== 'all' && !empty($selectedWarehouseId)) {
            $statsQuery->where('warehouse_id', $selectedWarehouseId);
        }

        $totalBoxes = $statsQuery->sum('quantity');
        $totalPieces = $statsQuery->sum('total_pieces');
        $stockedProductsCount = $statsQuery->where('total_pieces', '>', 0)->distinct('product_id')->count('product_id');

        // Estimate inventory value (total_pieces * product.purchase_price_per_piece)
        $inventoryValue = DB::table('warehouse_stocks')
            ->join('products', 'warehouse_stocks.product_id', '=', 'products.id')
            ->when($selectedWarehouseId !== 'all' && !empty($selectedWarehouseId), function ($q) use ($selectedWarehouseId) {
                $q->where('warehouse_stocks.warehouse_id', $selectedWarehouseId);
            })
            ->selectRaw('SUM(warehouse_stocks.total_pieces * COALESCE(products.purchase_price_per_piece, 0)) as total_val')
            ->value('total_val') ?? 0;

        // Recent Transfers involving this warehouse
        $transfersQuery = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'product'])->latest();
        if ($selectedWarehouseId !== 'all' && !empty($selectedWarehouseId)) {
            $transfersQuery->where(function ($q) use ($selectedWarehouseId) {
                $q->where('from_warehouse_id', $selectedWarehouseId)
                  ->orWhere('to_warehouse_id', $selectedWarehouseId);
            });
        }
        $recentTransfers = $transfersQuery->take(15)->get();

        return view('admin_panel.warehouses.working_report', compact(
            'allWarehouses',
            'categories',
            'selectedWarehouseId',
            'categoryId',
            'stockFilter',
            'search',
            'stocks',
            'totalBoxes',
            'totalPieces',
            'stockedProductsCount',
            'inventoryValue',
            'recentTransfers'
        ));
    }
}
