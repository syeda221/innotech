<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Milon\Barcode\DNS1D;

class ProductController extends Controller
{
    public function getPrice(Request $request)
    {
        $rawId = $request->product_id;
        $variantData = null;
        if (strpos($rawId, '|variant|') !== false) {
            $parts = explode('|variant|', $rawId);
            $realId = $parts[0];
            $variantData = json_decode(base64_decode($parts[1]), true);
            $product = Product::find($realId);
        } else {
            $product = Product::find($rawId);
        }

        if (! $product) {
            return response()->json(['retail_price' => 0]);
        }

        // Determine price based on mode
        $price = 0;
        if ($product->size_mode === 'by_size') {
            $price = $product->price_per_m2;
        } else {
            // For by_cartons or by_pieces, use the box/piece price
            $price = $product->sale_price_per_box;
        }

        $saleDisc = $variantData && isset($variantData['sale_discount_percent']) ? (float)$variantData['sale_discount_percent'] : ($product->sale_discount_percent ?? 0);
        $purchDisc = $variantData && isset($variantData['purchase_discount_percent']) ? (float)$variantData['purchase_discount_percent'] : ($product->purchase_discount_percent ?? 0);

        return response()->json([
            'retail_price'          => $price,
            'wholesale_price'       => (float)($product->wholesale_price ?? 0),
            'weight_per_piece'      => (float)($product->weight_per_piece ?? 0),
            'size_mode'             => $product->size_mode,
            'pieces_per_box'        => $product->pieces_per_box,
            'price_per_m2'          => $product->price_per_m2,
            'sale_price_per_box'    => $product->sale_price_per_box,
            'sale_price_per_piece'  => $product->sale_price_per_piece,
            'height'                => $product->height,
            'width'                 => $product->width,
            'item_code'             => $product->item_code,
            'purchase_discount_percent' => $purchDisc,
            'sale_discount_percent'     => $saleDisc,
        ]);
    }

    public function productget()
    {
        $products = Product::all();

        return response()->json($products);
    }

    private function upsertStocks(int $productId, float $qtyDelta, int $branchId = 1, int $warehouseId = 1): void
    {
        // Fallback to first warehouse if the requested one doesn't exist
        if ($warehouseId === 1 && !\App\Models\Warehouse::find(1)) {
            $firstWh = \App\Models\Warehouse::first();
            $warehouseId = $firstWh ? $firstWh->id : (\App\Models\Warehouse::create(['warehouse_name' => 'Main Warehouse', 'location' => 'Main'])->id);
        }

        $stock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($stock) {
            $stock->quantity += $qtyDelta;
            $stock->save();
        } else {
            \App\Models\WarehouseStock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'quantity' => $qtyDelta,
                'price' => 0,
            ]);
        }
    }

    // ===== High Performance Select2 Search (Ajax) =====
    public function ajaxSearch(Request $request)
    {
        $term        = $request->get('term') ?? $request->get('q') ?? '';
        $warehouseId = $request->get('warehouse_id') ?: 'all';

        $query = Product::query()
            ->select('id', 'item_name', 'tracking_type', 'item_code', 'barcode_path', 'size_mode', 'unit_id', 'height', 'width', 'pieces_per_box', 'purchase_price_per_box', 'purchase_price_per_m2', 'purchase_price_per_piece', 'pieces_per_m2', 'purchase_discount_percent', 'sale_discount_percent', 'color', 'sale_price_per_piece', 'wholesale_price', 'weight_per_piece')
            ->with(['unit', 'warehouseStocks'])
            ->where('is_active', true) /* Only active products */
            ->where(function ($q) use ($term) {
                $q->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%")
                    ->orWhere('barcode_path', 'like', "%{$term}%");
            });

        $products = $query->paginate(10); // Lazy loading (10 per request)
        $stockService = app(\App\Services\StockBalanceService::class);

        $results = $products->getCollection()->flatMap(function ($p) use ($stockService, $warehouseId) {
            $breakdown = $stockService->getProductBreakdown($p, $warehouseId, [], false);
            $hasVariants = count($breakdown) > 1 || ($breakdown[0]['is_variant'] ?? false);

            if ($hasVariants) {
                $expanded = [];
                foreach ($breakdown as $entry) {
                    $v = $entry['variant'] ?? [];
                    $size = (isset($v['size']) && $v['size'] !== '-') ? " {$v['size']}" : '';
                    $color = (isset($v['color']) && $v['color'] !== '-') ? " ({$v['color']})" : '';
                    $vName = ($v['name'] ?? $p->item_name) . $size . $color;
                    $vUnitName = $entry['unit_name'];
                    $vPpb = (float) $entry['ppb'];
                    $vBalance = (float) $entry['balance'];
                    $vStockDisplay = $vBalance;

                    if (isset($v['conv_factor']) && $p->size_mode === 'by_kg') {
                        $factor = (float) $v['conv_factor'];
                        if ($factor == 1) {
                            if ($vBalance < 0.001) {
                                $vStockDisplay = "0 {$vUnitName} (0 Gm)";
                            } elseif ($vBalance < 1 && $vBalance > 0) {
                                $gmVal = (int) round($vBalance * 1000);
                                $vStockDisplay = "{$vBalance} {$vUnitName} ({$gmVal} Gm)";
                            } else {
                                $vStockDisplay = "{$vBalance} {$vUnitName}";
                            }
                        } else {
                            $vStockDisplay = (string) ((int) floor($vBalance));
                        }
                    } elseif ((in_array($p->size_mode, ['by_cartons', 'by_bandal']) || $p->size_mode === 'by_size') && $vPpb > 1) {
                        $vBoxes = (int) floor($vBalance / $vPpb);
                        $vLoose = (int) round($vBalance - ($vBoxes * $vPpb));
                        $vStockDisplay = $vLoose > 0 ? "$vBoxes.$vLoose" : $vBoxes;
                    }

                    $v['current_stock'] = $vStockDisplay;
                    $variantJson = json_encode($v);

                    $expanded[] = [
                        'id'                        => $p->id . '|variant|' . base64_encode($variantJson),
                        'text'                      => $vName . " (SKU: {$p->item_code})",
                        'sku'                       => $p->item_code ?? '',
                        'stock'                     => $vStockDisplay,
                        'stock_pieces'              => $vBalance,
                        'name'                      => $vName,
                        'size_mode'                 => $p->size_mode,
                        'unit_name'                 => $vUnitName,
                        'pieces_per_box'            => $vPpb,
                        'ppb'                       => $vPpb,
                        'trade_price'               => $v['purch_price'] ?? $p->purchase_price_per_piece ?? 0,
                        'retail_price'              => $v['sale_price'] ?? $p->sale_price_per_piece ?? 0,
                        'wholesale_price'           => $v['wholesale_price'] ?? $p->wholesale_price ?? 0,
                        'weight_per_piece'          => $v['weight_per_piece'] ?? $p->weight_per_piece ?? 0,
                        'purchase_price_per_piece'  => $v['purch_price'] ?? $p->purchase_price_per_piece ?? 0,
                        'purchase_price_per_box'    => ($v['purch_price'] ?? $p->purchase_price_per_piece ?? 0) * $vPpb,
                        'purchase_price_per_m2'     => $p->purchase_price_per_m2 ?? 0,
                        'sale_discount_percent'     => (float) ($v['sale_discount_percent'] ?? 0),
                        'purchase_discount_percent' => (float) ($v['purchase_discount_percent'] ?? 0),
                        'variant_data'              => base64_encode($variantJson),
                        'tracking_type'             => $p->tracking_type ?? 'simple',
                    ];
                }
                return $expanded;
            }

            $entry = $breakdown[0];
            $stockPieces = (float) $entry['balance'];
            $ppb = (float) $entry['ppb'];
            $unitName = $entry['unit_name'];

            $stockDisplay = $stockPieces;
            if ((in_array($p->size_mode, ['by_cartons', 'by_bandal']) || $p->size_mode === 'by_size') && $ppb > 1) {
                $boxes = (int) floor($stockPieces / $ppb);
                $loose = (int) round($stockPieces - ($boxes * $ppb));
                $stockDisplay = $loose > 0 ? "$boxes.$loose" : $boxes;
            }

            return [[
                'id'                        => $p->id,
                'text'                      => $p->item_name . " (SKU: {$p->item_code})",
                'sku'                       => $p->item_code ?? '',
                'stock'                     => $stockDisplay,
                'stock_pieces'              => $stockPieces,
                'name'                      => $p->item_name,
                'size_mode'                 => $p->size_mode,
                'unit_name'                 => $unitName,
                'pieces_per_box'            => $ppb,
                'ppb'                       => $ppb,
                'trade_price'               => $p->purchase_price_per_piece ?? 0,
                'retail_price'              => $p->sale_price_per_piece ?? 0,
                'wholesale_price'           => $p->wholesale_price ?? 0,
                'weight_per_piece'          => $p->weight_per_piece ?? 0,
                'purchase_price_per_piece'  => $p->purchase_price_per_piece ?? 0,
                'purchase_price_per_box'    => $p->purchase_price_per_box ?? (($p->purchase_price_per_piece ?? 0) * $ppb),
                'purchase_price_per_m2'     => $p->purchase_price_per_m2 ?? 0,
                'sale_discount_percent'     => $p->sale_discount_percent ?? 0,
                'purchase_discount_percent' => $p->purchase_discount_percent ?? 0,
                'variant_data'              => '',
                'tracking_type'             => $p->tracking_type ?? 'simple',
            ]];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => $products->hasMorePages()],
        ]);
    }

    // ===== Product search (general) =====
    public function searchProducts(Request $request)
    {
        $term = $request->get('q', '');

        $products = Product::with('category_relation', 'sub_category_relation', 'brand')
            ->withSum('warehouseStocks', 'total_pieces')
            ->where('is_active', true) /* Only active products */
            ->when($term, function ($query) use ($term) {
                $query->where('item_name', 'like', "%{$term}%")
                    ->orWhere('item_code', 'like', "%{$term}%")
                    ->orWhereHas('category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('sub_category_relation', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('brand', fn ($q) => $q->where('name', 'like', "%{$term}%"));
            })
            ->limit(500) // limit for performance
            ->get();

        return response()->json($products->map(function ($p, $key) {
            $stockPieces = (float) ($p->warehouse_stocks_sum_total_pieces ?? 0);

            // Calculate Stock Display (Boxes vs Pieces)
            $stockDisplay = $stockPieces;
            $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;

            if ((in_array($p->size_mode, ['by_cartons', 'by_bandal']) || $p->size_mode === 'by_size') && $p->pieces_per_box > 0) {
                $boxes = floor($stockPieces / $ppb);
                $loose = $stockPieces % $ppb;
                $stockDisplay = $loose > 0 ? "$boxes.$loose" : $boxes;
            }

            return [
                'id' => $p->id,
                'item_code' => $p->item_code,
                'item_name' => $p->item_name,
                'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
                'category_name' => $p->category_relation->name ?? '-',
                'sub_category_name' => $p->sub_category_relation->name ?? '-',
                'height' => $p->height ?? null,
                'width' => $p->width ?? null,
                'pieces_per_box' => $ppb,
                'size_mode' => $p->size_mode,
                'stock' => $stockDisplay,
                'trade_price' => $p->purchase_price_per_piece ?? 0,
                'total_m2' => number_format($p->total_m2 ?? 0, 2),
                'price_per_m2' => number_format($p->price_per_m2 ?? 0, 2),
                'total_price' => number_format($p->total_price ?? 0, 2),
                'brand_name' => $p->brand->name ?? '-',
            ];
        }));
    }

    // ===== List page =====
    public function product(Request $request)
    {
        $query = Product::with([
            'category_relation',
            'sub_category_relation',
            'unit',
            'brand',
        ])->withSum('warehouseStocks', 'total_pieces');

        // ── Filters ──
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('item_name', 'like', "%$s%")
                  ->orWhere('item_code', 'like', "%$s%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active' ? 1 : 0);
        }

        $products   = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();

        return view('admin_panel.product.index', compact('products', 'categories', 'brands'));
    }

    public function productview($id)
    {
        $product = Product::with([
            'category_relation',
            'sub_category_relation',
            'brand',
            'unit',
            'warehouseStocks',
        ])->find($id);

        if (! $product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Calculate derived fields
        $totalPieces = $product->warehouseStocks->sum('total_pieces');
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        $boxes = 0;
        $loose = 0;

        if (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || $product->size_mode === 'by_size') {
            $boxes = floor($totalPieces / $ppb);
            $loose = $totalPieces % $ppb;
        } else {
            // For by_pieces, boxes is essentially the piece count if we treat it largely
            // But strict interpretation:
            $boxes = $totalPieces;
            $loose = 0;
        }

        // Append these purely for the view (not saved in DB)
        $product->setAttribute('calculated_total_stock_qty', $totalPieces);
        $product->setAttribute('calculated_boxes_quantity', $boxes);
        $product->setAttribute('calculated_loose_pieces', $loose);

        return response()->json($product);
    }

    // //////////////////////

    // /////////////////////////

    // ===== Create page =====
    public function view_store()
    {
        $categories = Category::select('id', 'name')->get();
        $units = Unit::select('id', 'name')->get();
        $brands = Brand::select('id', 'name')->get();

        return view('admin_panel.product.create', compact('categories', 'units', 'brands'));
    }

    // ===== Dependent subcategories =====
    public function getSubcategories($category_id)
    {
        $subcategories = Subcategory::where('category_id', $category_id)->get();

        return response()->json($subcategories);
    }

    public function getAllSubcategoriesJson()
    {
        return response()->json(Subcategory::orderBy('name')->get(['id', 'name']));
    }

    public function getCategoriesJson()
    {
        return response()->json(Category::orderBy('name')->get(['id', 'name']));
    }

    public function getBrandsJson()
    {
        return response()->json(Brand::orderBy('name')->get(['id', 'name']));
    }

    // ===== Barcode =====
    public function generateBarcode(Request $request)
    {
        $barcodeNumber = $request->filled('code') ? $request->code : rand(100000000000, 999999999999);
        $barcodePNG = (new DNS1D)->getBarcodePNG($barcodeNumber, 'C39', 3, 50);
        $barcodeImage = 'data:image/png;base64,'.$barcodePNG;

        return response()->json([
            'barcode_number' => $barcodeNumber,
            'barcode_image' => $barcodeImage,
        ]);
    }

    // ===== Store product =====
    // ===== Store product =====
    public function store_product(Request $request)
    {
        if (! Auth::id()) {
            return $request->wantsJson()
                ? response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401)
                : redirect()->route('login');
        }

        // 1. Validate
        $validation = $this->validateProductRequest($request);
        if ($validation->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['status' => 'error', 'errors' => $validation->errors()], 422);
            }

            return redirect()->back()->withErrors($validation)->withInput();
        }

        $mode = $request->size_mode;

        // Initialize variables
        $height = 0;
        $width = 0;
        $piecesPerBox = 0;
        $boxesQuantity = 0;
        $loosePieces = 0;
        $pieceQuantity = 0;

        $totalM2 = 0;
        $totalStockQty = 0;
        $m2PerPiece = 0;
        $piecesPerM2 = 0; // New: How many pieces fit in 1 m²

        // Pricing Vars
        $pricePerM2 = 0;
        $purchasePricePerM2 = 0;

        $salePricePerBox = 0;
        $purchasePricePerPiece = 0;
        $purchasePricePerBox = 0;
        $salePricePerPiece = 0;

        $totalPrice = 0;
        $totalPurchasePrice = 0;

        if ($mode === 'by_size') {
            // By Size Mode
            $height = (float) $request->height;
            $width = (float) $request->width;
            $piecesPerBox = (int) $request->pieces_per_box;
            $boxesQuantity = (int) $request->boxes_quantity;
            // No loose pieces in by_size usually, but if needed add here

            // Pricing inputs
            $pricePerM2 = (float) $request->price_per_m2;
            $purchasePricePerM2 = (float) $request->purchase_price_per_m2;

            $m2PerPiece = ($height * $width) / 10000;
            $m2PerBox = $m2PerPiece * $piecesPerBox;
            $totalM2 = $m2PerBox; // Storing m2 per box as requested instead of total stock m2

            // Store m2 per piece (0.72) directly as requested, even though column name is pieces_per_m2
            $piecesPerM2 = $m2PerPiece;

            $totalStockQty = $boxesQuantity * $piecesPerBox; // Store in Pieces

            // Prices calculated from m²
            $salePricePerPiece = $m2PerPiece * $pricePerM2;
            $salePricePerBox = $m2PerBox * $pricePerM2;
            $purchasePricePerPiece = $m2PerPiece * $purchasePricePerM2;
            $purchasePricePerBox = $m2PerBox * $purchasePricePerM2;

        } elseif (in_array($mode, ['by_cartons', 'by_bandal'])) {
            // By Cartons Mode
            $piecesPerBox = (int) $request->pieces_per_box;
            if ($piecesPerBox <= 0) $piecesPerBox = 1;
            
            $boxesQuantityRaw = (string) $request->boxes_quantity;
            $loosePieces = (int) $request->loose_pieces;
            
            if (strpos($boxesQuantityRaw, '.') !== false) {
                $parts = explode('.', $boxesQuantityRaw);
                $boxesQuantity = (int)($parts[0] ?? 0);
                $looseFromDec = (int)($parts[1] ?? 0);
                $totalStockQty = ($piecesPerBox * $boxesQuantity) + $looseFromDec + $loosePieces;
            } else {
                $boxesQuantity = (int) $request->boxes_quantity;
                $totalStockQty = ($piecesPerBox * $boxesQuantity) + $loosePieces;
            }

            $inputSalePc = (float) $request->sale_price_per_box;
            $inputPurchPc = (float) $request->purchase_price_per_piece;

            $salePricePerPiece = $inputSalePc;
            $salePricePerBox = round($inputSalePc * $piecesPerBox, 2);

            $purchasePricePerPiece = $inputPurchPc;
            $purchasePricePerBox = round($inputPurchPc * $piecesPerBox, 2);

        } else {
            // Treat by_pieces, by_kg, by_meter, by_gm as piece-based mode
            $pieceQuantity = (int) $request->piece_quantity;
            $piecesPerBox = 1;
            $boxesQuantity = $pieceQuantity;
            $totalStockQty = $pieceQuantity;

            $inputSalePc = (float) $request->sale_price_per_box;
            $inputPurchPc = (float) $request->purchase_price_per_piece;

            $salePricePerPiece = $inputSalePc;
            $salePricePerBox = $inputSalePc;
            $purchasePricePerPiece = $inputPurchPc;
            $purchasePricePerBox = $inputPurchPc;
        }

        $userId = Auth::id();

        // Auto item_code
        $lastProduct = Product::orderBy('id', 'desc')->first();
        $nextCode = $lastProduct ? ('ITEM-'.str_pad($lastProduct->id + 1, 4, '0', STR_PAD_LEFT)) : 'ITEM-0001';

        // Image upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $imagePath = $filename;
        } else {
            $imagePath = null;
        }

        $product = null;
        DB::transaction(function () use ($request, $userId, $nextCode, $imagePath, $mode, $height, $width, $piecesPerBox, $boxesQuantity,
            $totalM2, $pricePerM2, $purchasePricePerM2, $totalStockQty, $piecesPerM2,
            $salePricePerPiece, $salePricePerBox, $purchasePricePerPiece, $purchasePricePerBox, &$product) {

            $variants = [];
            if ($request->has('variant_name')) {
                $names = $request->variant_name;
                $sizes = $request->variant_size;
                $colors = $request->variant_color;
                $stocks = $request->variant_stock;
                $sale_prices = $request->variant_sale_price;
                $wholesale_prices = $request->variant_wholesale_price;
                $weight_factors = $request->variant_weight_per_piece;
                $purch_prices = $request->variant_purchase_price;
                $sale_discounts = $request->variant_sale_discount;
                $purch_discounts = $request->variant_purchase_discount;
                $alerts = $request->variant_alert_qty;
                $barcodes = $request->variant_barcode;
                $conv_factors = $request->variant_conv_factor;
                $is_bases = $request->variant_is_base;
                $units = $request->variant_unit;

                // Validate Conv Factors if Weight Unit is selected
                if (in_array($mode, ['by_kg', 'by_gm', 'by_ton'])) {
                    $factors = [];
                    $baseCount = 0;
                    for ($i = 0; $i < count($names); $i++) {
                        if (!empty($names[$i])) {
                            $factor = (float)($conv_factors[$i] ?? 0);
                            $isBase = (int)($is_bases[$i] ?? 0);
                            if ($factor <= 0) {
                                throw new \Exception("Conversion Factor must be greater than 0.");
                            }
                            if ($isBase === 1) {
                                $baseCount++;
                                if ($factor != 1) {
                                    throw new \Exception("Base variant must have Conversion Factor exactly equal to 1.");
                                }
                                // Base factor is not added to duplicate list
                            } else {
                                // Non‑base variant: ensure uniqueness
                                if (in_array((string)$factor, $factors, true)) {
                                    throw new \Exception("Duplicate Conversion Factor found: " . $factor);
                                }
                                $factors[] = (string)$factor;
                            }
                        }
                    }
                    if ($baseCount !== 1) {
                        throw new \Exception("Exactly one Base Variant is required for weight units.");
                    }
                }

                $variantStockSum = 0;
                $baseConvForCarton = null;
                for ($i = 0; $i < count($names); $i++) {
                    if (!empty($names[$i])) {
                        $vStockRaw = (string)($stocks[$i] ?? '0');
                        $vStock = (float)$vStockRaw;
                        $vConvFactor = (float)($conv_factors[$i] ?? 0);
                        $isBase = (int)($is_bases[$i] ?? 0);
                        if ($vConvFactor <= 0) $vConvFactor = 1;

                        if (in_array($mode, ['by_kg', 'by_gm', 'by_ton'])) {
                            if ($isBase === 1) {
                                $variantStockSum += $vStock;
                            }
                        } elseif (in_array($mode, ['by_cartons', 'by_bandal'])) {
                            if ($isBase === 1 || $baseConvForCarton === null) {
                                $baseConvForCarton = $vConvFactor;
                            }
                            if (strpos($vStockRaw, '.') !== false) {
                                $parts = explode('.', $vStockRaw);
                                $boxes = (int)($parts[0] ?? 0);
                                $loose = (int)($parts[1] ?? 0);
                                $variantStockSum += ($boxes * $vConvFactor) + $loose;
                            } else {
                                $variantStockSum += ($vStock * $vConvFactor);
                            }
                        } else {
                            $variantStockSum += $vStock;
                        }

                        $vSalePrice = (float)($sale_prices[$i] ?? 0);
                        $vPurchPrice = (float)($purch_prices[$i] ?? 0);
                        $vWholesalePrice = (float)($wholesale_prices[$i] ?? 0);

                        // Price entered by user on product page is per 1 PCS. Carton price is multiplied by conversion factor.

                        $variants[] = [
                            'name' => $names[$i],
                            'size' => $sizes[$i] ?? '-',
                            'color' => $colors[$i] ?? '-',
                            'stock' => 0,
                            'sale_price' => $vSalePrice,
                            'wholesale_price' => $vWholesalePrice,
                            'weight_per_piece' => $weight_factors[$i] ?? 0,
                            'purch_price' => $vPurchPrice,
                            'sale_discount_percent' => (float)($sale_discounts[$i] ?? 0),
                            'purchase_discount_percent' => (float)($purch_discounts[$i] ?? 0),
                            'alert' => $alerts[$i] ?? 0,
                            'barcode' => $barcodes[$i] ?? '',
                            'conv_factor' => $vConvFactor,
                            'is_base_variant' => $is_bases[$i] ?? 0,
                            'unit' => $units[$i] ?? 'Pcs',
                        ];
                    }
                }
                
                if (count($variants) > 0) {
                    $totalStockQty = 0;
                    if (in_array($mode, ['by_cartons', 'by_bandal']) && $baseConvForCarton) {
                        $piecesPerBox = (int)$baseConvForCarton;
                    }
                    $boxesQuantity = 0;

                    $baseVariant = collect($variants)->firstWhere('is_base_variant', 1) ?? $variants[0];
                    if ($baseVariant && (in_array($mode, ['by_cartons', 'by_bandal']) || strtolower($baseVariant['unit'] ?? '') === 'carton')) {
                        $salePricePerPiece = (float)($baseVariant['sale_price'] ?? 0);
                        $purchasePricePerPiece = (float)($baseVariant['purch_price'] ?? 0);
                        $purchasePricePerBox = round($purchasePricePerPiece * $piecesPerBox, 2);
                        $salePricePerBox = round($salePricePerPiece * $piecesPerBox, 2);
                    }
                }

                // Promote base-variant discounts to product-level so sale/purchase pages can fetch them
                $saleDiscountPercent = (float)($request->sale_discount_percent ?? 0);
                $purchaseDiscountPercent = (float)($request->purchase_discount_percent ?? 0);
                if (count($variants) > 0) {
                    $baseVariant = collect($variants)->firstWhere('is_base_variant', 1) ?? $variants[0];
                    if ($baseVariant) {
                        $saleDiscountPercent = (float)($baseVariant['sale_discount_percent'] ?? 0);
                        $purchaseDiscountPercent = (float)($baseVariant['purchase_discount_percent'] ?? 0);
                    }
                }
            }

            // Create product
            $product = Product::create([
                'creater_id' => $userId,
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'item_code' => $nextCode,
                'item_name' => $request->product_name,
                'tracking_type' => $request->tracking_type ?? 'simple',
                'barcode_path' => $request->barcode_path ?? rand(100000000000, 999999999999),
                'unit_id' => $request->unit,
                'brand_id' => $request->brand_id,
                'model' => is_array($request->model) ? json_encode(array_values(array_filter($request->model))) : $request->model,
                'image' => $imagePath,
                'color' => count($variants) > 0 ? json_encode($variants) : ($request->color ? json_encode($request->color) : null),
                'purchase_discount_percent' => $purchaseDiscountPercent,
                'sale_discount_percent' => $saleDiscountPercent,
                'alert_quantity' => $request->alert_quantity,
                'alert_carton_quantity' => $request->alert_carton_quantity,

                // New Fields
                'size_mode' => $mode,
                'height' => $height,
                'width' => $width,
                'pieces_per_box' => $piecesPerBox,
                'pieces_per_m2' => $piecesPerM2,

                'total_m2' => $totalM2,

                // Prices
                'price_per_m2' => $pricePerM2,
                'purchase_price_per_m2' => $purchasePricePerM2,

                'sale_price_per_box' => $salePricePerBox,
                'sale_price_per_piece' => $salePricePerPiece,
                'wholesale_price' => $request->wholesale_price ?? 0,
                'weight_per_piece' => $request->weight_per_piece ?? 0,
                'purchase_price_per_piece' => $purchasePricePerPiece,
                'purchase_price_per_box' => $purchasePricePerBox,

                'is_part' => 0,
                'is_assembled' => 0,
                'is_web_visible' => $request->has('is_web_visible') ? 1 : 0,
                'show_on_homepage' => $request->has('show_on_homepage') ? 1 : 0,
                'auto_hide_out_of_stock' => $request->has('auto_hide_out_of_stock') ? 1 : 0,
                'promo_tag' => $request->promo_tag,
                'web_sale_price' => $request->web_sale_price,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $targetWh = null;
            if ($request->filled('warehouse_id')) {
                $targetWh = \App\Models\Warehouse::find($request->warehouse_id);
            }
            if (!$targetWh) {
                $targetWh = \App\Models\Warehouse::first() ?? \App\Models\Warehouse::create([
                    'warehouse_name' => 'Main Warehouse',
                    'location' => 'Main',
                ]);
            }

            // Create Warehouse Stock (initialize with initial quantity when provided on creation)
            $initPpb = $piecesPerBox > 0 ? $piecesPerBox : 1;
            WarehouseStock::create([
                'warehouse_id' => $targetWh->id,
                'product_id'   => $product->id,
                'quantity'     => $totalStockQty > 0 ? round($totalStockQty / $initPpb, 2) : 0,
                'total_pieces' => $totalStockQty,
                'remarks'      => 'Product Created',
            ]);

            // Log Stock Movement (Initial)
            if ($totalStockQty > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'adjustment',
                    'qty' => $totalStockQty,
                    'ref_type' => 'INIT',
                    'note' => 'Initial Stock',
                ]);

                // Create initial batch if batch product
                if ($product->tracking_type === 'batch') {
                    $initBatchNo = !empty($request->initial_batch_no) ? trim($request->initial_batch_no) : ('BATCH-' . date('Ymd'));
                    \App\Models\ProductBatch::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $targetWh->id,
                        'batch_no' => $initBatchNo,
                        'mfg_date' => $request->initial_mfg_date ?: null,
                        'expiry_date' => $request->initial_expiry_date ?: null,
                        'qty' => $totalStockQty,
                        'cost_price' => $product->purchase_price_per_piece ?? 0,
                        'remarks' => 'Opening Stock Batch',
                    ]);
                }

                // Create initial serials if serial product
                if ($product->tracking_type === 'serial' && !empty($request->initial_serials)) {
                    $serialsRaw = is_array($request->initial_serials) ? $request->initial_serials : preg_split('/[\r\n,]+/', (string)$request->initial_serials);
                    foreach ($serialsRaw as $sNum) {
                        $sNum = trim($sNum);
                        if ($sNum !== '') {
                            \App\Models\ProductSerial::create([
                                'product_id' => $product->id,
                                'warehouse_id' => $targetWh->id,
                                'serial_number' => $sNum,
                                'status' => 'available',
                                'cost_price' => $product->purchase_price_per_piece ?? 0,
                                'remarks' => 'Opening Stock IMEI',
                            ]);
                        }
                    }
                }
            }

            // Upload Website Gallery Images
            if ($request->hasFile('web_images')) {
                foreach ($request->file('web_images') as $index => $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/products'), $fileName);
                    \App\Models\ProductWebImage::create([
                        'product_id' => $product->id,
                        'image_path' => $fileName,
                        'sort_order' => $index
                    ]);
                }
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'product_id' => $product ? $product->id : null,
                'product_name' => $request->product_name,
            ]);
        }

        return redirect()->back()->with('success', 'Product created successfully');
    }

    /*
    // ===== Parts search (for BOM modal) with real available qty =====
        public function searchPartName(Request $request)
    {
        $q = $request->get('q', '');

        $parts = Product::where('is_part', 1)
            ->leftJoin('stocks', 'stocks.product_id', '=', 'products.id')
            ->where(function ($x) use ($q) {
                $x->where('products.item_name', 'like', "%{$q}%")
                  ->orWhere('products.item_code', 'like', "%{$q}%");
            })
            ->groupBy('products.id', 'products.item_name', 'products.item_code', 'products.unit_id')
            ->selectRaw('products.id, products.item_name, products.item_code, products.unit_id, COALESCE(SUM(stocks.qty),0) as available_qty')
            ->limit(20)
            ->get();

        return response()->json($parts->map(function ($p) {
            return [
                'id'            => $p->id,
                'item_name'     => $p->item_name,
                'item_code'     => $p->item_code,
                'unit'          => optional(Unit::find($p->unit_id))->name ?? '',
                'available_qty' => (float)$p->available_qty,
            ];
        }));
    }
    */

    // ===== Update product =====
    public function update(Request $request, $id)
    {
        $userId = auth()->id();

        if ($request->wantsJson()) {
            $validation = $this->validateProductRequest($request);
            if ($validation->fails()) {
                return response()->json(['status' => 'error', 'errors' => $validation->errors()], 422);
            }
            $validated = $validation->validated();
        } else {
            $validation = $this->validateProductRequest($request);
            $validation->validate();
        }

        $mode = $request->size_mode;

        // Initialize variables (defaults)
        $height = 0;
        $width = 0;
        $piecesPerBox = 0;
        $boxesQuantity = 0;
        $loosePieces = 0;
        $pieceQuantity = 0;

        $totalM2 = 0;
        $totalStockQty = 0;
        $m2PerPiece = 0;
        $piecesPerM2 = 0; // New: How many pieces fit in 1 m²

        $pricePerM2 = 0;
        $purchasePricePerM2 = 0;

        $salePricePerPiece = 0;
        $salePricePerBox = 0;
        $purchasePricePerPiece = 0;
        $purchasePricePerBox = 0;

        $totalPrice = 0;
        $totalPurchasePrice = 0;

        if ($mode === 'by_size') {
            $height = (float) $request->height;
            $width = (float) $request->width;
            $piecesPerBox = (int) $request->pieces_per_box;
            $boxesQuantity = (int) $request->boxes_quantity;

            // Pricing
            $pricePerM2 = (float) $request->price_per_m2;
            $purchasePricePerM2 = (float) $request->purchase_price_per_m2;

            $m2PerPiece = ($height * $width) / 10000;
            $m2PerBox = $m2PerPiece * $piecesPerBox;
            $totalM2 = $m2PerBox * $boxesQuantity;

            // Calculate pieces per m²
            $piecesPerM2 = $m2PerPiece > 0 ? (1 / $m2PerPiece) : 0;
            $piecesPerM2 = $m2PerPiece > 0 ? (1 / $m2PerPiece) : 0;

            // Logic validation
            if ($totalM2 <= 0) {
                if ($request->wantsJson()) {
                    return response()->json(['status' => 'error', 'errors' => ['total_m2' => ['Total m² cannot be zero.']]], 422);
                }

                return redirect()->back()->withErrors(['total_m2' => 'Total m² cannot be zero.']);
            }

            $totalPrice = $totalM2 * $pricePerM2;
            $totalPurchasePrice = $totalM2 * $purchasePricePerM2;

            // Set total stock qty for by_size
            $totalStockQty = $boxesQuantity;

            $salePricePerPiece = $salePricePerBox; // as salePricePerBox is piece price input
            $salePricePerBox = $salePricePerPiece * $piecesPerBox;
            $purchasePricePerPiece = $purchasePricePerPiece;
            $purchasePricePerBox = $purchasePricePerPiece * $piecesPerBox;

            $totalPrice = $totalStockQty * $salePricePerBox;
            $totalPurchasePrice = $totalStockQty * $purchasePricePerPiece;

        } elseif (in_array($mode, ['by_cartons', 'by_bandal'])) {
            // By Cartons Mode
            $piecesPerBox = (int) $request->pieces_per_box;
            if ($piecesPerBox <= 0) $piecesPerBox = 1;

            $boxesQuantityRaw = (string) $request->boxes_quantity;
            $loosePieces = (int) $request->loose_pieces;

            if (strpos($boxesQuantityRaw, '.') !== false) {
                $parts = explode('.', $boxesQuantityRaw);
                $boxesQuantity = (int)($parts[0] ?? 0);
                $looseFromDec = (int)($parts[1] ?? 0);
                $totalStockQty = ($piecesPerBox * $boxesQuantity) + $looseFromDec + $loosePieces;
            } else {
                $boxesQuantity = (int) $request->boxes_quantity;
                $totalStockQty = ($piecesPerBox * $boxesQuantity) + $loosePieces;
            }

            $inputSalePc = (float) $request->sale_price_per_box;
            $inputPurchPc = (float) $request->purchase_price_per_piece;

            $salePricePerPiece = $inputSalePc;
            $salePricePerBox = round($inputSalePc * $piecesPerBox, 2);

            $purchasePricePerPiece = $inputPurchPc;
            $purchasePricePerBox = round($inputPurchPc * $piecesPerBox, 2);

            $totalPrice = $totalStockQty * $salePricePerPiece;
            $totalPurchasePrice = $totalStockQty * $purchasePricePerPiece;

        } else {
            // Treat by_pieces, by_kg, by_meter, by_gm, by_feet, by_ton as piece-based mode
            $pieceQuantity = (int) $request->piece_quantity;
            $piecesPerBox = 1;

            // Pricing
            $salePricePerBox = (float) $request->sale_price_per_box;
            $purchasePricePerPiece = (float) $request->purchase_price_per_piece;

            $totalStockQty = $pieceQuantity;

            $salePricePerPiece = $salePricePerBox;
            $salePricePerBox = $salePricePerPiece;
            $purchasePricePerPiece = $purchasePricePerPiece;
            $purchasePricePerBox = $purchasePricePerPiece;

            $totalPrice = $totalStockQty * $salePricePerBox;
            $totalPurchasePrice = $totalStockQty * $purchasePricePerPiece;
        }

        // image handle
        $imagePath = Product::where('id', $id)->value('image');
        if ($request->hasFile('image')) {
            $imageName = time().'.'.$request->image->extension();
            $request->image->move(public_path('uploads/products'), $imageName);
            $imagePath = $imageName;
        }

        DB::transaction(function () use ($request, $id, $userId, $imagePath, $mode, $height, $width, $piecesPerBox,
            $boxesQuantity, $loosePieces, $pieceQuantity, $totalStockQty,
            $totalM2, $pricePerM2, $purchasePricePerM2, $salePricePerBox, $purchasePricePerPiece, $piecesPerM2,
            $salePricePerPiece, $purchasePricePerBox) {

            $variants = [];
            if ($request->has('variant_name')) {
                $names = $request->variant_name;
                $sizes = $request->variant_size;
                $colors = $request->variant_color;
                $stocks = $request->variant_stock;
                $sale_prices = $request->variant_sale_price;
                $wholesale_prices = $request->variant_wholesale_price;
                $weight_factors = $request->variant_weight_per_piece;
                $purch_prices = $request->variant_purchase_price;
                $sale_discounts = $request->variant_sale_discount;
                $purch_discounts = $request->variant_purchase_discount;
                $alerts = $request->variant_alert_qty;
                $barcodes = $request->variant_barcode;
                $conv_factors = $request->variant_conv_factor;
                $is_bases = $request->variant_is_base;
                $units = $request->variant_unit;

                // Validate Conv Factors if Weight Unit is selected
                if (in_array($mode, ['by_kg', 'by_gm', 'by_ton'])) {
                    $factors = [];
                    $baseCount = 0;
                    for ($i = 0; $i < count($names); $i++) {
                        if (!empty($names[$i])) {
                            $factor = (float)($conv_factors[$i] ?? 0);
                            $isBase = (int)($is_bases[$i] ?? 0);
                            if ($factor <= 0) {
                                throw new \Exception("Conversion Factor must be greater than 0.");
                            }
                            if ($isBase === 1) {
                                $baseCount++;
                                if ($factor != 1) {
                                    throw new \Exception("Base variant must have Conversion Factor exactly equal to 1.");
                                }
                                // do not add base factor to $factors array
                            } else {
                                // Non‑base variant: ensure uniqueness
                                if (in_array((string)$factor, $factors, true)) {
                                    throw new \Exception("Duplicate Conversion Factor found: " . $factor);
                                }
                                $factors[] = (string)$factor;
                            }
                        }
                    }
                    if ($baseCount !== 1) {
                        throw new \Exception("Exactly one Base Variant is required for weight units.");
                    }
                }

                $baseConvForCarton = null;
                for ($i = 0; $i < count($names); $i++) {
                    if (!empty($names[$i])) {
                        $vConvFactor = (float)($conv_factors[$i] ?? 0);
                        $isBase = (int)($is_bases[$i] ?? 0);
                        if ($vConvFactor <= 0) $vConvFactor = 1;
                        if (in_array($mode, ['by_cartons', 'by_bandal']) && ($isBase === 1 || $baseConvForCarton === null)) {
                            $baseConvForCarton = $vConvFactor;
                        }

                        $vSalePrice = (float)($sale_prices[$i] ?? 0);
                        $vPurchPrice = (float)($purch_prices[$i] ?? 0);
                        $vWholesalePrice = (float)($wholesale_prices[$i] ?? 0);

                        // Price entered by user on product page is per 1 PCS. Carton price is multiplied by conversion factor.

                        $variants[] = [
                            'name' => $names[$i],
                            'size' => $sizes[$i] ?? '-',
                            'color' => $colors[$i] ?? '-',
                            'stock' => $stocks[$i] ?? 0,
                            'sale_price' => $vSalePrice,
                            'wholesale_price' => $vWholesalePrice,
                            'weight_per_piece' => $weight_factors[$i] ?? 0,
                            'purch_price' => $vPurchPrice,
                            'sale_discount_percent' => (float)($sale_discounts[$i] ?? 0),
                            'purchase_discount_percent' => (float)($purch_discounts[$i] ?? 0),
                            'alert' => $alerts[$i] ?? 0,
                            'barcode' => $barcodes[$i] ?? '',
                            'conv_factor' => $vConvFactor,
                            'is_base_variant' => $is_bases[$i] ?? 0,
                            'unit' => $units[$i] ?? 'Pcs',
                        ];
                    }
                }
                if (in_array($mode, ['by_cartons', 'by_bandal']) && $baseConvForCarton) {
                    $piecesPerBox = (int)$baseConvForCarton;
                    $baseVariant = collect($variants)->firstWhere('is_base_variant', 1) ?? $variants[0];
                    if ($baseVariant) {
                        $salePricePerPiece = (float)($baseVariant['sale_price'] ?? 0);
                        $purchasePricePerPiece = (float)($baseVariant['purch_price'] ?? 0);
                        $purchasePricePerBox = round($purchasePricePerPiece * $piecesPerBox, 2);
                        $salePricePerBox = round($salePricePerPiece * $piecesPerBox, 2);
                    }
                }

                // Promote base-variant discounts to product-level so sale/purchase pages can fetch them
                $saleDiscountPercent = (float)($request->sale_discount_percent ?? 0);
                $purchaseDiscountPercent = (float)($request->purchase_discount_percent ?? 0);
                if (count($variants) > 0) {
                    $baseVariant = collect($variants)->firstWhere('is_base_variant', 1) ?? $variants[0];
                    if ($baseVariant) {
                        $saleDiscountPercent = (float)($baseVariant['sale_discount_percent'] ?? 0);
                        $purchaseDiscountPercent = (float)($baseVariant['purchase_discount_percent'] ?? 0);
                    }
                }
            }

            // color update logic
            $final_color = null;
            if (count($variants) > 0) {
                $final_color = json_encode($variants);
            } else if ($request->has('color')) {
                $final_color = json_encode($request->color);
            } else {
                // keep old color if not submitted
                $final_color = Product::where('id', $id)->value('color');
            }

            Product::where('id', $id)->update([
                'creater_id' => $userId,
                'category_id' => $request->category_id,
                'sub_category_id' => $request->sub_category_id,
                'item_code' => $request->item_code ?? Product::where('id', $id)->value('item_code'),
                'item_name' => $request->product_name,
                'barcode_path' => $request->barcode_path ?? rand(100000000000, 999999999999),
                'unit_id' => $request->unit,
                'brand_id' => $request->brand_id,
                'model' => is_array($request->model) ? json_encode(array_values(array_filter($request->model))) : $request->model,
                'image' => $imagePath,
                'color' => $final_color,
                'purchase_discount_percent' => $purchaseDiscountPercent,
                'sale_discount_percent' => $saleDiscountPercent,
                'alert_quantity' => $request->alert_quantity,
                'alert_carton_quantity' => $request->alert_carton_quantity,

                // New Fields
                'size_mode' => $mode,
                'height' => $height,
                'width' => $width,
                'pieces_per_box' => $piecesPerBox,
                'pieces_per_m2' => $piecesPerM2,
                // 'boxes_quantity' => $boxesQuantity,
                // 'loose_pieces' => $loosePieces,
                // 'piece_quantity' => $pieceQuantity,
                // 'total_stock_qty' => $totalStockQty,

                'total_m2' => $totalM2,

                // Prices
                'price_per_m2' => $pricePerM2,
                'purchase_price_per_m2' => $purchasePricePerM2,

                'sale_price_per_box' => $salePricePerBox,
                'sale_price_per_piece' => $salePricePerPiece,
                'wholesale_price' => $request->wholesale_price ?? 0,
                'weight_per_piece' => $request->weight_per_piece ?? 0,
                'purchase_price_per_piece' => $purchasePricePerPiece,
                'purchase_price_per_box' => $purchasePricePerBox,

                // 'total_price' => $totalPrice, // Removed: Not in DB
                // 'total_purchase_price' => $totalPurchasePrice, // Removed: Not in DB

                'is_part' => 0,
                'is_assembled' => 0,
                'is_web_visible' => $request->has('is_web_visible') ? 1 : 0,
                'show_on_homepage' => $request->has('show_on_homepage') ? 1 : 0,
                'auto_hide_out_of_stock' => $request->has('auto_hide_out_of_stock') ? 1 : 0,
                'promo_tag' => $request->promo_tag,
                'web_sale_price' => $request->web_sale_price,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'updated_at' => now(),
            ]);

            // BOM re-save logic removed as table does not exist
            // DB::table('product_boms')->where('product_id', $id)->delete();

            // Upload Website Gallery Images
            if ($request->hasFile('web_images')) {
                // Remove old images if new ones are uploaded
                $oldImages = \App\Models\ProductWebImage::where('product_id', $id)->get();
                foreach($oldImages as $oldImg) {
                    if(file_exists(public_path('uploads/products/'.$oldImg->image_path))) {
                        unlink(public_path('uploads/products/'.$oldImg->image_path));
                    }
                    $oldImg->delete();
                }

                foreach ($request->file('web_images') as $index => $file) {
                    $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('uploads/products'), $fileName);
                    \App\Models\ProductWebImage::create([
                        'product_id' => $id,
                        'image_path' => $fileName,
                        'sort_order' => $index
                    ]);
                }
            }

            // ✅ Update WarehouseStock box quantity based on new pieces_per_box (preserve total_pieces)
            $warehouseStock = \App\Models\WarehouseStock::where('product_id', $id)->first();
            
            $ppb = $piecesPerBox > 0 ? $piecesPerBox : 1;

            if ($warehouseStock) {
                $hasInit = \App\Models\StockMovement::where('product_id', $id)->where('ref_type', 'INIT')->exists();
                $initQty = \App\Models\StockMovement::where('product_id', $id)->where('ref_type', 'INIT')->sum('qty');
                
                $oldInitial = $hasInit ? $initQty : $warehouseStock->total_pieces;
                $delta = $totalStockQty - $oldInitial;

                if ($delta != 0) {
                    $warehouseStock->total_pieces += $delta;
                    
                    \App\Models\StockMovement::create([
                        'product_id' => $id,
                        'type' => 'adjustment',
                        'qty' => $delta,
                        'ref_type' => 'INIT',
                        'note' => 'Initial stock corrected (' . $oldInitial . ' -> ' . $totalStockQty . ')',
                    ]);
                }

                $warehouseStock->quantity = round($warehouseStock->total_pieces / $ppb, 2);
                $warehouseStock->save();
            }

            // Manual stock adjustment (extra on top)
            if ($request->filled('stock_adjust') && (float) $request->stock_adjust != 0) {
                $adjQty = (float) $request->stock_adjust;

                StockMovement::create([
                    'product_id' => $id,
                    'type'       => 'adjustment',
                    'qty'        => $adjQty,
                    'ref_type'   => 'ADJ',
                    'note'       => 'Manual stock adjustment',
                ]);

                $this->upsertStocks($id, $adjQty, 1, 1);
            }
        });

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'message' => 'Product updated successfully']);
        }

        return redirect()->back()->with('success', 'Product updated successfully');
    }

    public function bulkUpdate(Request $request)
    {
        $userId = auth()->id();
        $productsData = $request->input('products', []);

        if (empty($productsData)) {
            return response()->json(['status' => 'error', 'message' => 'No products selected for update.'], 400);
        }

        try {
            DB::transaction(function () use ($productsData, $userId) {
                foreach ($productsData as $id => $data) {
                    $product = Product::findOrFail($id);
                    $updateData = [
                        'creater_id' => $userId,
                        'updated_at' => now(),
                    ];

                    // 1. Category
                    if (array_key_exists('category_id', $data)) {
                        $updateData['category_id'] = $data['category_id'];
                    }

                    // 2. Alert quantity (Min Qty in Cartons)
                    if (array_key_exists('alert_carton_quantity', $data)) {
                        $val = $data['alert_carton_quantity'];
                        $updateData['alert_carton_quantity'] = ($val !== null && $val !== '') ? (int)$val : null;
                        
                        // Sync alert_quantity (pieces) for legacy compatibility
                        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                        $updateData['alert_quantity'] = ($val !== null && $val !== '') ? ((int)$val * $ppb) : null;
                    }

                    // 3. Discounts
                    if (array_key_exists('purchase_discount_percent', $data)) {
                        $updateData['purchase_discount_percent'] = (float)$data['purchase_discount_percent'];
                    }
                    if (array_key_exists('sale_discount_percent', $data)) {
                        $updateData['sale_discount_percent'] = (float)$data['sale_discount_percent'];
                    }

                    // 4. Prices
                    $tradePrice = null;
                    $retailPrice = null;

                    if (array_key_exists('purchase_price_per_piece', $data)) {
                        $tradePrice = (float)$data['purchase_price_per_piece'];
                    }
                    if (array_key_exists('sale_price_per_piece', $data)) {
                        $retailPrice = (float)$data['sale_price_per_piece'];
                    }

                    if ($product->size_mode === 'by_size') {
                        $m2PerPiece = ($product->height * $product->width) / 10000;
                        $m2PerBox = $m2PerPiece * ($product->pieces_per_box ?: 1);

                        if ($tradePrice !== null) {
                            $updateData['purchase_price_per_piece'] = $tradePrice;
                            $purchasePricePerM2 = $m2PerPiece > 0 ? ($tradePrice / $m2PerPiece) : 0;
                            $updateData['purchase_price_per_m2'] = $purchasePricePerM2;
                            $updateData['purchase_price_per_box'] = $m2PerBox * $purchasePricePerM2;
                        }

                        if ($retailPrice !== null) {
                            $updateData['sale_price_per_piece'] = $retailPrice;
                            $pricePerM2 = $m2PerPiece > 0 ? ($retailPrice / $m2PerPiece) : 0;
                            $updateData['price_per_m2'] = $pricePerM2;
                            $updateData['sale_price_per_box'] = $m2PerBox * $pricePerM2;
                        }
                    } elseif (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) {
                        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

                        if ($tradePrice !== null) {
                            $updateData['purchase_price_per_piece'] = $tradePrice;
                            $updateData['purchase_price_per_box'] = $tradePrice * $ppb;
                        }

                        if ($retailPrice !== null) {
                            $updateData['sale_price_per_piece'] = $retailPrice;
                            $updateData['sale_price_per_box'] = $retailPrice * $ppb;
                        }
                    } else { // by_pieces / fallback
                        if ($tradePrice !== null) {
                            $updateData['purchase_price_per_piece'] = $tradePrice;
                            $updateData['purchase_price_per_box'] = $tradePrice;
                        }

                        if ($retailPrice !== null) {
                            $updateData['sale_price_per_piece'] = $retailPrice;
                            $updateData['sale_price_per_box'] = $retailPrice;
                        }
                    }

                    $product->update($updateData);
                }
            });

            return response()->json(['status' => 'success', 'message' => 'Products bulk updated successfully.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ===== Edit view =====
    public function edit($id)
    {
        $product = Product::with('category_relation', 'sub_category_relation', 'unit', 'brand', 'warehouseStocks')
            ->findOrFail($id);
        $categories = Category::all();
        $subcategories = SubCategory::all();
        $brands = Brand::all();

        // Calculate current stock from WarehouseStock (the real source of truth)
        $totalPieces = $product->warehouseStocks->sum('total_pieces');
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        // For editing initial stock, we want to show the original initial stock, not the current remaining stock
        $initQty = \App\Models\StockMovement::where('product_id', $product->id)->where('ref_type', 'INIT')->sum('qty');
        // If there's an INIT record, use it. Otherwise fallback to current stock.
        $hasInit = \App\Models\StockMovement::where('product_id', $product->id)->where('ref_type', 'INIT')->exists();
        $displayPieces = $hasInit ? $initQty : $totalPieces;

        if (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || $product->size_mode === 'by_size') {
            $product->boxes_quantity = (int) floor($displayPieces / $ppb);
            $product->loose_pieces   = (int) ($displayPieces % $ppb);
        } elseif ($product->size_mode === 'by_pieces') {
            $product->piece_quantity  = (int) $displayPieces;
            $product->boxes_quantity  = 0;
            $product->loose_pieces    = 0;
        }

        // Robustly parse variants from $product->color
        $variants = [];
        if (!empty($product->color)) {
            $raw = $product->color;
            if (is_string($raw)) {
                // Check if base64 encoded
                $b64 = base64_decode($raw, true);
                if ($b64 !== false && (str_starts_with(trim($b64), '[') || str_starts_with(trim($b64), '{'))) {
                    $raw = $b64;
                }
                $decoded = json_decode($raw, true);
                // Handle double json encoded strings
                if (is_string($decoded)) {
                    $decoded2 = json_decode($decoded, true);
                    if (is_array($decoded2)) {
                        $decoded = $decoded2;
                    }
                }
            } else {
                $decoded = $raw;
            }

            if (is_array($decoded)) {
                if (isset($decoded['name'])) {
                    $variants = [$decoded];
                } elseif (isset($decoded[0])) {
                    if (is_array($decoded[0])) {
                        $variants = array_values($decoded);
                    } elseif (is_string($decoded[0])) {
                        foreach ($decoded as $idx => $cName) {
                            $variants[] = [
                                'name' => $product->item_name . ($cName ? ' - ' . $cName : ''),
                                'size' => '',
                                'color' => $cName,
                                'unit' => optional($product->unit)->name ?? (in_array($product->size_mode, ['by_cartons', 'by_bandal']) ? 'Carton' : 'Pcs'),
                                'stock' => $idx === 0 ? ((in_array($product->size_mode, ['by_cartons', 'by_bandal'])) ? (($product->boxes_quantity ?: 0) . ($product->loose_pieces ? '.' . $product->loose_pieces : '')) : ($totalPieces ?: 0)) : 0,
                                'sale_price' => $product->sale_price_per_piece ?? $product->sale_price_per_box ?? 0,
                                'wholesale_price' => $product->wholesale_price ?? 0,
                                'purch_price' => $product->purchase_price_per_piece ?? 0,
                                'alert' => $product->alert_quantity ?? (($product->alert_carton_quantity ?? 0) * ($product->pieces_per_box > 0 ? $product->pieces_per_box : 1)),
                                'barcode' => '',
                                'conv_factor' => (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) ? ($product->pieces_per_box ?: 1) : 1,
                                'is_base_variant' => $idx === 0 ? 1 : 0
                            ];
                        }
                    }
                } else {
                    $variants = array_values($decoded);
                }
            } elseif (is_string($raw) && trim($raw) !== '' && trim($raw) !== 'null') {
                $variants[] = [
                    'name' => $product->item_name . ' - ' . $raw,
                    'size' => '',
                    'color' => $raw,
                    'unit' => optional($product->unit)->name ?? (in_array($product->size_mode, ['by_cartons', 'by_bandal']) ? 'Carton' : 'Pcs'),
                    'stock' => (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) ? (($product->boxes_quantity ?: 0) . ($product->loose_pieces ? '.' . $product->loose_pieces : '')) : ($totalPieces ?: 0),
                    'sale_price' => $product->sale_price_per_piece ?: $product->sale_price_per_box ?: 0,
                    'wholesale_price' => $product->wholesale_price ?? 0,
                    'purch_price' => $product->purchase_price_per_piece ?? 0,
                    'alert' => $product->alert_quantity ?? (($product->alert_carton_quantity ?? 0) * ($product->pieces_per_box > 0 ? $product->pieces_per_box : 1)),
                    'barcode' => $product->barcode_path ?? '',
                    'conv_factor' => (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) ? ($product->pieces_per_box ?: 1) : 1,
                    'is_base_variant' => 1
                ];
            }
        }

        if (empty($variants)) {
            $variants = [
                [
                    'name' => $product->item_name,
                    'size' => '-',
                    'color' => '-',
                    'unit' => optional($product->unit)->name ?? (in_array($product->size_mode, ['by_cartons', 'by_bandal']) ? 'Carton' : 'Pcs'),
                    'stock' => (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) ? (($product->boxes_quantity ?: 0) . ($product->loose_pieces ? '.' . $product->loose_pieces : '')) : ($totalPieces ?: 0),
                    'sale_price' => $product->sale_price_per_piece ?: $product->sale_price_per_box ?: 0,
                    'wholesale_price' => $product->wholesale_price ?: 0,
                    'purch_price' => $product->purchase_price_per_piece ?: 0,
                    'alert' => $product->alert_quantity ?: (($product->alert_carton_quantity ?? 0) * ($product->pieces_per_box > 0 ? $product->pieces_per_box : 1)),
                    'barcode' => $product->barcode_path ?: '',
                    'conv_factor' => (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) ? ($product->pieces_per_box ?: 1) : 1,
                    'is_base_variant' => 1,
                ]
            ];
        }

        if (!empty($variants)) {
            $hasBase = false;
            foreach ($variants as $v) {
                if (!empty($v['is_base_variant'])) {
                    $hasBase = true;
                    break;
                }
            }
            if (!$hasBase && isset($variants[0])) {
                $variants[0]['is_base_variant'] = 1;
            }
        }

        // Variants prices are stored and edited as per-piece (1 PCS) prices. Carton prices are computed live on page.

        return view('admin_panel.product.edit', compact('product', 'categories', 'subcategories', 'brands', 'variants'));
    }

    // ===== Barcode view =====
    public function barcode($id)
    {
        $product = Product::findOrFail($id);

        return view('admin_panel.product.barcode', compact('product'));
    }

    // Shared validation rules
    private function validateProductRequest(Request $request)
    {
        $rules = [
            'product_name' => 'required|string|max:255',
            'category_id' => 'required',
            'sub_category_id' => 'nullable',
            'brand_id' => 'required',
            'unit' => 'nullable',
            'model' => 'nullable', // Made nullable
            'size_mode' => 'required|in:by_size,by_cartons,by_bandal,by_pieces,by_kg,by_meter,by_gm,by_feet,by_ton',
            'purchase_discount_percent' => 'nullable|numeric|min:0|max:100',
            'sale_discount_percent' => 'nullable|numeric|min:0|max:100',
            'alert_quantity' => 'nullable|integer|min:0',
            'alert_carton_quantity' => 'nullable|integer|min:0',
        ];

        // Conditional rules logic
        $mode = $request->size_mode;

        if ($mode === 'by_size') {
            $rules = array_merge($rules, [
                'height' => 'required|numeric|gt:0',
                'width' => 'required|numeric|gt:0',
                'pieces_per_box' => 'required|integer|gt:0',
                'boxes_quantity' => 'required|integer|min:0', // Allowed 0 stock
                'price_per_m2' => 'required|numeric|min:0', // Allowed 0 price
                'purchase_price_per_m2' => 'required|numeric|min:0',
            ]);
        } elseif (in_array($mode, ['by_cartons', 'by_bandal'])) {
            $rules = array_merge($rules, [
                'pieces_per_box' => 'nullable|integer|min:1',
                'boxes_quantity' => 'nullable|numeric|min:0',
                'loose_pieces' => 'nullable|integer|min:0',
                'sale_price_per_box' => 'nullable|numeric|min:0',
                'purchase_price_per_piece' => 'nullable|numeric|min:0',
            ]);
        } else {
            $rules = array_merge($rules, [
                'piece_quantity' => 'nullable|numeric|min:0', // Allowed 0 stock
                'sale_price_per_box' => 'nullable|numeric|min:0',
                'purchase_price_per_piece' => 'nullable|numeric|min:0',
            ]);
        }

        return \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
    }

    // AJAX Validation Endpoint
    public function validateForm(Request $request)
    {
        $validator = $this->validateProductRequest($request);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        return response()->json(['status' => 'success', 'message' => 'Valid']);
    }

    // ===== Toggle Product Active/Inactive =====
    public function toggleActive($id)
    {
        $product = Product::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        return response()->json([
            'success'   => true,
            'is_active' => $product->is_active,
            'message'   => $product->is_active ? 'Product activated successfully.' : 'Product deactivated successfully.',
        ]);
    }

    /**
     * Match a sale item to a specific variant based on size and color stored in color field.
     */
    private function matchSaleItemToVariant($saleItem, $variant)
    {
        $itemColor = $saleItem->color;
        if (empty($itemColor)) {
            return false;
        }

        $itemVariant = [];
        $b64Decoded = base64_decode($itemColor, true);
        if ($b64Decoded !== false) {
            $json = json_decode($b64Decoded, true);
            if (is_array($json)) {
                $itemVariant = $json;
            }
        }
        if (empty($itemVariant)) {
            $json = json_decode($itemColor, true);
            if (is_array($json)) {
                $itemVariant = $json;
            }
        }

        if (empty($itemVariant)) {
            // Simple string comparison
            return strtolower(trim($itemColor)) === strtolower(trim($variant['color'] ?? ''));
        }

        // 1. Compare barcodes if present on both sides
        $vBarcode = trim($variant['barcode'] ?? '');
        $itemBarcode = trim($itemVariant['barcode'] ?? '');
        if (!empty($vBarcode) && !empty($itemBarcode)) {
            return $vBarcode === $itemBarcode;
        }

        // 2. Compare base variant status if present
        if (isset($variant['is_base_variant']) && isset($itemVariant['is_base_variant'])) {
            $vIsBase = (int) $variant['is_base_variant'];
            $itemIsBase = (int) $itemVariant['is_base_variant'];
            if ($vIsBase !== $itemIsBase) {
                return false;
            }
        }

        // 3. Compare conversion factors if present
        if (isset($variant['conv_factor']) && isset($itemVariant['conv_factor'])) {
            $vConv = (float) $variant['conv_factor'];
            $itemConv = (float) $itemVariant['conv_factor'];
            if ($vConv > 0 && $itemConv > 0 && abs($vConv - $itemConv) > 0.0001) {
                return false;
            }
        }

        // 4. Compare unit names if present
        $vUnit = strtolower(trim($variant['unit'] ?? ''));
        $itemUnit = strtolower(trim($itemVariant['unit'] ?? ''));
        if (!empty($vUnit) && !empty($itemUnit) && $vUnit !== $itemUnit) {
            return false;
        }

        // 5. Compare name, color and size
        $vColor = strtolower(trim($variant['color'] ?? '-'));
        $vSize = strtolower(trim($variant['size'] ?? '-'));
        $vName = strtolower(trim($variant['name'] ?? ''));

        $itemVColor = strtolower(trim($itemVariant['color'] ?? ($itemVariant['color_val'] ?? '-')));
        $itemVSize = strtolower(trim($itemVariant['size'] ?? ($itemVariant['size_val'] ?? '-')));
        $itemVName = strtolower(trim($itemVariant['name'] ?? ''));

        if ($vColor === '') $vColor = '-';
        if ($vSize === '') $vSize = '-';
        if ($itemVColor === '') $itemVColor = '-';
        if ($itemVSize === '') $itemVSize = '-';

        $colorSizeMatch = ($vColor === $itemVColor && $vSize === $itemVSize);

        if ($vName !== '' && $itemVName !== '') {
            return $colorSizeMatch && ($vName === $itemVName);
        }

        return $colorSizeMatch;
    }

    /**
     * AJAX endpoint to get full tracking details of a product
     */
    public function getTrackingInfo(Request $request, $id)
    {
        if (strpos((string)$id, '|variant|') !== false) {
            $parts = explode('|variant|', (string)$id);
            $id = (int) $parts[0];
        }

        $product = Product::findOrFail($id);
        $warehouseId = $request->get('warehouse_id');

        $trackingType = $product->tracking_type ?? 'simple';

        // Available batches with stock > 0
        $batchQuery = \App\Models\ProductBatch::where('product_id', $product->id)->where('qty', '>', 0);
        if ($warehouseId) {
            $batchQuery->where('warehouse_id', $warehouseId);
        }
        $batches = $batchQuery->orderBy('expiry_date', 'asc')->get();

        // All batches ever created for this product (for purchase restocking)
        $allBatches = \App\Models\ProductBatch::where('product_id', $product->id)
            ->orderBy('id', 'desc')
            ->get(['id', 'batch_no', 'mfg_date', 'expiry_date', 'qty']);

        // Available serials (status = available)
        $serialQuery = \App\Models\ProductSerial::where('product_id', $product->id)->where('status', 'available');
        if ($warehouseId) {
            $serialQuery->where('warehouse_id', $warehouseId);
        }
        $serials = $serialQuery->orderBy('serial_number', 'asc')->get(['id', 'serial_number', 'batch_id', 'status']);

        // Check if product has any transactions
        $hasTransactions = \App\Models\StockMovement::where('product_id', $product->id)->exists()
            || \App\Models\SaleItem::where('product_id', $product->id)->exists()
            || \App\Models\PurchaseItem::where('product_id', $product->id)->exists();

        return response()->json([
            'success' => true,
            'product_id' => $product->id,
            'product_name' => $product->item_name,
            'tracking_type' => $trackingType,
            'is_batch' => $trackingType === 'batch',
            'is_serial' => $trackingType === 'serial',
            'is_simple' => $trackingType === 'simple',
            'has_transactions' => $hasTransactions,
            'batches' => $batches,
            'all_batches' => $allBatches,
            'serials' => $serials,
        ]);
    }

    /**
     * Set / Lock product tracking nature (simple, batch, serial)
     */
    public function setTrackingType(Request $request, $id)
    {
        if (strpos((string)$id, '|variant|') !== false) {
            $parts = explode('|variant|', (string)$id);
            $id = (int) $parts[0];
        }

        $request->validate([
            'tracking_type' => 'required|in:simple,batch,serial',
        ]);

        $product = Product::findOrFail($id);
        $product->tracking_type = $request->tracking_type;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => "Product tracking set to '{$request->tracking_type}'.",
            'tracking_type' => $product->tracking_type,
        ]);
    }

}
