<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductSerial;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OpeningStockController extends Controller
{
    /**
     * Check permission for Opening Stock module
     */
    private function checkPermission()
    {
        $user = auth()->user();
        if ($user->email === 'admin@admin.com' || $user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return true;
        }

        if ($user->can('opening.stock.view') || $user->can('opening.stock.create') || $user->can('warehouse.stock.view') || $user->can('products.create') || $user->can('stock.adjust.create')) {
            return true;
        }

        abort(403, 'Unauthorized action. You do not have permission to access Opening Stock.');
    }

    /**
     * Get permitted warehouses
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

        return Warehouse::orderBy('warehouse_name')->get();
    }

    /**
     * Opening Stock Index & History Page
     */
    public function index(Request $request)
    {
        $this->checkPermission();

        $warehouses = $this->getPermittedWarehouses();
        $warehouseIds = $warehouses->pluck('id')->toArray();

        // Query opening stock movements
        $query = StockMovement::with(['product'])
            ->where('ref_type', 'OPENING_STOCK');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            })->orWhere('note', 'like', "%{$search}%");
        }

        $movements = $query->latest()->paginate(20)->appends($request->query());

        // Stats summary
        $totalOpeningMovements = StockMovement::where('ref_type', 'OPENING_STOCK')->count();
        $totalPiecesAdded      = StockMovement::where('ref_type', 'OPENING_STOCK')->sum('qty');
        $totalBatches          = ProductBatch::count();
        $totalSerials          = ProductSerial::count();

        $rawProducts = Product::where('is_active', true)->select('id', 'item_name', 'item_code', 'size_mode', 'pieces_per_box', 'color', 'purchase_price_per_piece', 'sale_price_per_piece')->get();

        $formattedProducts = [];
        foreach ($rawProducts as $p) {
            $hasVariants = false;
            if ($p->color) {
                try {
                    $parsed = is_string($p->color) ? json_decode($p->color, true) : $p->color;
                    if (is_array($parsed) && count($parsed) > 0) {
                        $hasVariants = true;
                        foreach ($parsed as $v) {
                            $vName = $v['name'] ?? $p->item_name;
                            $vSize = (isset($v['size']) && $v['size'] !== '-' && $v['size'] !== '') ? " - Size: {$v['size']}" : '';
                            $vColor = (isset($v['color']) && $v['color'] !== '-' && $v['color'] !== '') ? " ({$v['color']})" : '';
                            $vKey = $vName . '|' . ($v['size'] ?? '-') . '|' . ($v['color'] ?? '-');

                            $encodedVal = $p->id . '|variant|' . base64_encode(json_encode($v));
                            $label = "{$vName}{$vSize}{$vColor} (SKU: {$p->item_code})";

                            $formattedProducts[] = [
                                'value'        => $encodedVal,
                                'product_id'   => $p->id,
                                'variant_key'  => $vKey,
                                'label'        => $label,
                                'item_name'    => $vName,
                                'item_code'    => $p->item_code,
                                'cost_price'   => (float)($v['purch_price'] ?? $p->purchase_price_per_piece ?? 0),
                            ];
                        }
                    }
                } catch (\Exception $e) {}
            }

            if (!$hasVariants) {
                $formattedProducts[] = [
                    'value'        => (string) $p->id,
                    'product_id'   => $p->id,
                    'variant_key'  => null,
                    'label'        => "{$p->item_name} (SKU: {$p->item_code})",
                    'item_name'    => $p->item_name,
                    'item_code'    => $p->item_code,
                    'cost_price'   => (float)($p->purchase_price_per_piece ?? 0),
                ];
            }
        }

        return view('admin_panel.opening_stock.index', [
            'movements'             => $movements,
            'totalOpeningMovements' => $totalOpeningMovements,
            'totalPiecesAdded'      => $totalPiecesAdded,
            'totalBatches'          => $totalBatches,
            'totalSerials'          => $totalSerials,
            'warehouses'            => $warehouses,
            'products'              => $formattedProducts,
        ]);
    }

    /**
     * Ajax: Get Product & Variant Details
     */
    public function getProductDetails(Request $request, $id)
    {
        $this->checkPermission();

        $rawId = urldecode($id);
        $variantData = null;
        if (strpos($rawId, '|variant|') !== false) {
            $parts = explode('|variant|', $rawId);
            $realId = $parts[0];
            $variantData = json_decode(base64_decode($parts[1]), true);
            $product = Product::with(['unit'])->find($realId);
        } else {
            $product = Product::with(['unit'])->find($rawId);
        }

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $warehouseId = $request->query('warehouse_id');
        $whStock = null;
        if ($warehouseId) {
            $whStock = WarehouseStock::where('warehouse_id', $warehouseId)->where('product_id', $product->id)->first();
        }

        $totalStockPieces = (float) WarehouseStock::where('product_id', $product->id)->sum('total_pieces');
        $currentWhStockPieces = $whStock ? (float) $whStock->total_pieces : 0;

        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
        $unitName = $product->unit->name ?? match($product->size_mode) {
            'by_kg' => 'Kg',
            'by_gm' => 'Gm',
            'by_ton' => 'Ton',
            'by_meter' => 'Meter',
            'by_feet' => 'Ft',
            'by_cartons' => 'Carton',
            'by_size' => 'M²',
            default => 'Pcs',
        };

        $costPrice = $product->purchase_price_per_piece ?? 0;
        $selectedVariant = null;
        $displayName = $product->item_name;

        if ($variantData) {
            if (isset($variantData['purch_price'])) {
                $costPrice = (float) $variantData['purch_price'];
            }
            $vName = $variantData['name'] ?? $product->item_name;
            $vSize = $variantData['size'] ?? '-';
            $vColor = $variantData['color'] ?? '-';
            $selectedVariant = $vName . '|' . $vSize . '|' . $vColor;

            $vSizeStr = ($vSize !== '-' && $vSize !== '') ? " - Size: {$vSize}" : '';
            $vColorStr = ($vColor !== '-' && $vColor !== '') ? " ({$vColor})" : '';
            $displayName = "{$vName}{$vSizeStr}{$vColorStr}";
        }

        return response()->json([
            'success'               => true,
            'product_id'            => $product->id,
            'raw_value'             => $rawId,
            'item_name'             => $displayName,
            'item_code'             => $product->item_code,
            'size_mode'             => $product->size_mode,
            'unit_name'             => $unitName,
            'pieces_per_box'        => $ppb,
            'cost_price'            => (float) $costPrice,
            'sale_price'            => (float) ($variantData['sale_price'] ?? $product->sale_price_per_piece ?? 0),
            'total_stock_pieces'    => $totalStockPieces,
            'current_wh_stock'      => $currentWhStockPieces,
            'selected_variant'      => $selectedVariant,
        ]);
    }

    /**
     * Ajax: Check Duplicate Serial Numbers / IMEIs
     */
    public function checkSerials(Request $request)
    {
        $this->checkPermission();

        $serials = $request->input('serials', []);
        if (!is_array($serials) || empty($serials)) {
            return response()->json(['success' => true, 'duplicates' => []]);
        }

        $cleanSerials = array_map('trim', $serials);
        $cleanSerials = array_filter($cleanSerials);

        $duplicates = ProductSerial::whereIn('serial_number', $cleanSerials)
            ->pluck('serial_number')
            ->toArray();

        return response()->json([
            'success' => true,
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * Store Single Opening Stock Entry
     */
    public function store(Request $request)
    {
        $this->checkPermission();

        $validator = Validator::make($request->all(), [
            'product_id'    => 'required|exists:products,id',
            'warehouse_id'  => 'required|exists:warehouses,id',
            'tracking_type' => 'required|in:simple,batch,serial',
            'variant_key'   => 'nullable|string',
            'qty'           => 'required|numeric|min:0.01',
            'cost_price'    => 'nullable|numeric|min:0',
            'remarks'       => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                $this->executeSingleOpeningStock($request->all());
            });

            return response()->json([
                'success' => true,
                'message' => 'Opening Stock added successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Store Multi-Product / Multi-Variant Batch Opening Stock
     */
    public function storeBatch(Request $request)
    {
        $this->checkPermission();

        $items = $request->input('items', []);
        if (empty($items) || !is_array($items)) {
            return response()->json([
                'success' => false,
                'message' => 'Please add at least one product row to submit opening stock.',
            ], 422);
        }

        // Validate items structure
        foreach ($items as $idx => $item) {
            if (empty($item['product_id'])) {
                return response()->json(['success' => false, 'message' => "Row #" . ($idx + 1) . ": Product is required."], 422);
            }
            if (empty($item['warehouse_id'])) {
                return response()->json(['success' => false, 'message' => "Row #" . ($idx + 1) . ": Warehouse is required."], 422);
            }
            if (empty($item['qty']) || (float)$item['qty'] <= 0) {
                return response()->json(['success' => false, 'message' => "Row #" . ($idx + 1) . ": Valid quantity > 0 is required."], 422);
            }
        }

        // Validate all Serials across all rows for uniqueness
        $allSerials = [];
        foreach ($items as $idx => $item) {
            if (($item['tracking_type'] ?? 'simple') === 'serial') {
                $rawSerials = $item['serials'] ?? [];
                if (is_string($rawSerials)) {
                    $rawSerials = preg_split('/[\r\n,]+/', $rawSerials);
                }
                $clean = array_filter(array_map('trim', (array)$rawSerials));
                if (empty($clean)) {
                    return response()->json(['success' => false, 'message' => "Row #" . ($idx + 1) . ": Please enter Serial / IMEI numbers."], 422);
                }
                foreach ($clean as $s) {
                    if (in_array($s, $allSerials, true)) {
                        return response()->json(['success' => false, 'message' => "Duplicate Serial / IMEI '{$s}' found across your entry rows. Each IMEI must be unique."], 422);
                    }
                    $allSerials[] = $s;
                }
            }
        }

        if (!empty($allSerials)) {
            $dbExists = ProductSerial::whereIn('serial_number', $allSerials)->pluck('serial_number')->toArray();
            if (!empty($dbExists)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The following Serial/IMEI(s) already exist in database: ' . implode(', ', array_slice($dbExists, 0, 10)),
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($items) {
                foreach ($items as $itemData) {
                    $this->executeSingleOpeningStock($itemData);
                }
            });

            return response()->json([
                'success' => true,
                'message' => count($items) . ' product opening stock entry(s) saved successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save opening stock batch: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Helper: Process a single row opening stock item
     */
    private function executeSingleOpeningStock(array $data)
    {
        $rawProductId = $data['product_id'];
        $user = auth()->user();
        $canSelectWarehouse = $user && (
            (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
            (method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('admin') || $user->hasRole('superadmin'))) ||
            $user->can('warehouse.view')
        );
        $userDefaultWhId = (int) ($user?->warehouse_id ?: 1);
        $warehouseId  = $canSelectWarehouse ? ($data['warehouse_id'] ?? $userDefaultWhId) : $userDefaultWhId;
        $trackingType = $data['tracking_type'] ?? 'simple';
        $variantKey   = $data['variant_key'] ?? null;
        $inputQty     = (float) ($data['qty'] ?? 0);
        $remarks      = $data['remarks'] ?? null;

        if (strpos((string)$rawProductId, '|variant|') !== false) {
            $parts = explode('|variant|', (string)$rawProductId);
            $productId = (int) $parts[0];
            $vData = json_decode(base64_decode($parts[1]), true);
            if ($vData && empty($variantKey)) {
                $vName  = $vData['name'] ?? '';
                $vSize  = $vData['size'] ?? '-';
                $vColor = $vData['color'] ?? '-';
                $variantKey = "{$vName}|{$vSize}|{$vColor}";
            }
        } else {
            $productId = (int) $rawProductId;
        }

        $product = Product::where('id', $productId)->lockForUpdate()->first();
        if (!$product) {
            throw new \Exception("Product #{$productId} not found.");
        }

        $costPrice = (float) (isset($data['cost_price']) && $data['cost_price'] !== '' ? $data['cost_price'] : ($product->purchase_price_per_piece ?? 0));

        // Process Serials if serial mode
        $serials = [];
        if ($trackingType === 'serial') {
            $rawSerials = $data['serials'] ?? [];
            if (is_string($rawSerials)) {
                $rawSerials = preg_split('/[\r\n,]+/', $rawSerials);
            }
            $serials = array_filter(array_map('trim', (array)$rawSerials));
            if (empty($serials)) {
                throw new \Exception("Serial / IMEI numbers required for item {$product->item_name}");
            }
        }

        // Calculate added pieces
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
        $addedPieces = $inputQty;
        if (in_array($product->size_mode, ['by_cartons', 'by_bandal', 'by_size']) && $ppb > 1) {
            if (strpos((string)$inputQty, '.') !== false) {
                $parts = explode('.', (string)$inputQty);
                $boxes = (int) ($parts[0] ?? 0);
                $loose = (int) ($parts[1] ?? 0);
                $addedPieces = ($boxes * $ppb) + $loose;
            } else {
                $addedPieces = $inputQty * $ppb;
            }
        }

        if ($trackingType === 'serial' && count($serials) > 0) {
            $addedPieces = count($serials);
            $inputQty = count($serials);
        }

        // Update WarehouseStock
        $whStock = WarehouseStock::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id'   => $product->id,
            ],
            [
                'quantity'     => 0,
                'total_pieces' => 0,
            ]
        );

        $oldStockPieces = (float) $whStock->total_pieces;
        $newStockPieces = $oldStockPieces + $addedPieces;

        $whStock->total_pieces = $newStockPieces;
        if ($ppb > 1 && in_array($product->size_mode, ['by_cartons', 'by_size', 'by_bandal'])) {
            $whStock->boxes_quantity = floor($newStockPieces / $ppb);
            $whStock->quantity       = $whStock->boxes_quantity;
        } else {
            $whStock->quantity       = $newStockPieces;
        }
        $whStock->remarks = "Opening Stock ({$trackingType})";
        $whStock->save();

        // Update Variant Stock in product JSON if variant selected
        if ($variantKey && $product->color) {
            try {
                $parsed = is_string($product->color) ? json_decode($product->color, true) : $product->color;
                if (is_array($parsed)) {
                    foreach ($parsed as &$v) {
                        $vKey = ($v['name'] ?? $product->item_name) . '|' . ($v['size'] ?? '-') . '|' . ($v['color'] ?? '-');
                        if ($vKey === $variantKey) {
                            $vOld = (float)($v['stock'] ?? 0);
                            $v['stock'] = $vOld + $addedPieces;
                            break;
                        }
                    }
                    unset($v);
                    $product->color = json_encode($parsed);
                    $product->save();
                }
            } catch (\Exception $e) {}
        }

        // Save Batch
        $batchId = null;
        $batchNo = $data['batch_no'] ?? null;
        if ($trackingType === 'batch' || !empty($batchNo)) {
            $batch = ProductBatch::create([
                'product_id'   => $product->id,
                'variant_key'  => $variantKey,
                'warehouse_id' => $warehouseId,
                'batch_no'     => $batchNo ?? ('BATCH-' . strtoupper(uniqid())),
                'mfg_date'     => !empty($data['mfg_date']) ? $data['mfg_date'] : null,
                'expiry_date'  => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                'qty'          => $addedPieces,
                'cost_price'   => $costPrice,
                'remarks'      => $remarks ?: 'Opening Stock Batch',
            ]);
            $batchId = $batch->id;
        }

        // Save Serials
        if ($trackingType === 'serial' && count($serials) > 0) {
            foreach ($serials as $sNum) {
                ProductSerial::create([
                    'product_id'    => $product->id,
                    'variant_key'   => $variantKey,
                    'warehouse_id'  => $warehouseId,
                    'batch_id'      => $batchId,
                    'serial_number' => $sNum,
                    'status'        => 'available',
                    'cost_price'    => $costPrice,
                    'remarks'       => $remarks ?: 'Opening Stock Serial/IMEI',
                ]);
            }
        }

        // Log Stock Movement
        $noteDetails = "Warehouse #{$warehouseId} | Opening Stock [" . strtoupper($trackingType) . "]";
        if ($variantKey) {
            $noteDetails .= " | Variant: {$variantKey}";
        }
        if (!empty($batchNo)) {
            $noteDetails .= " | Batch: {$batchNo}";
        }
        if (!empty($data['mfg_date'])) {
            $noteDetails .= " | MFG: {$data['mfg_date']}";
        }
        if (!empty($data['expiry_date'])) {
            $noteDetails .= " | EXP: {$data['expiry_date']}";
        }
        if ($trackingType === 'serial') {
            $noteDetails .= " | Serials: " . count($serials);
        }
        if ($remarks) {
            $noteDetails .= " | " . $remarks;
        }

        StockMovement::create([
            'product_id' => $product->id,
            'type'       => 'adjustment',
            'qty'        => $addedPieces,
            'ref_type'   => 'OPENING_STOCK',
            'note'       => $noteDetails,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
