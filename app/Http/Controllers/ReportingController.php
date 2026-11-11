<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    public function onhand()
    {
        $rows = Product::leftJoin('v_stock_onhand as soh', 'soh.product_id', '=', 'products.id')
            ->leftJoin('brands', 'brands.id', '=', 'products.brand_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->selectRaw('
                products.id,
                products.item_code,
                products.item_name,
                COALESCE(brands.name, "") as brand_name,
                COALESCE(units.name, "") as unit_name,
                COALESCE(soh.onhand_qty, 0) as onhand_qty
            ')
            ->orderBy('products.item_name')
            ->get();

        return view('admin_panel.Reporting.onhand', compact('rows'));
    }

    public function inventory_demand_report()
    {
        $user = auth()->user();
        $categories = Category::orderBy('name')->get();
        $brands     = Brand::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();

        if ($user && ($user->email === 'admin@admin.com' || $user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->can('warehouse.view') || $user->can('warehouse.stock.view'))) {
            $warehouses = Warehouse::orderBy('warehouse_name')->get();
        } else {
            $warehouses = collect();
        }

        return view('admin_panel.reporting.inventory_demand_report', compact('categories', 'brands', 'warehouses', 'units'));
    }

        private function getVariantStockBalances($product, $warehouseId = 'all')
    {
        $dateFrom = null;
        $dateTo = null;
        
        $parsedVariants = [];
        if ($product->color) {
            try {
                $decoded = is_string($product->color) ? json_decode($product->color, true) : $product->color;
                if (is_array($decoded) && count($decoded) > 0 && isset($decoded[0]['name'])) {
                    $parsedVariants = $decoded;
                }
            } catch (\Exception $e) {}
        }
        
        if (count($parsedVariants) == 0) {
            if ($warehouseId && $warehouseId !== 'all') {
                $balance = (float) $product->warehouseStocks->where('warehouse_id', $warehouseId)->sum('total_pieces');
            } else {
                $balance = (float) $product->warehouseStocks->sum('total_pieces');
            }
            return [[
                'name' => $product->item_name,
                'code' => $product->item_code,
                'stock' => $balance,
                "unit_name" => ($product->size_mode === 'by_bandal') ? 'Bundal' : ($product->unit->name ?? "Pcs"),
                "is_carton_mode" => (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || strtolower($product->unit->name ?? "") === "carton"),
                "ppb" => ($product->pieces_per_box > 0 ? $product->pieces_per_box : 1),
                "size_mode" => $product->size_mode,
                'is_variant' => false,
                "purch_price" => null
            ]];
        }
        
        $unitName = ($product->size_mode === 'by_bandal') ? 'Bundal' : ($product->unit->name ?? 'Pcs');
        $results = [];
        
        if ($product->size_mode === 'by_kg') {
            if ($warehouseId && $warehouseId !== 'all') {
                $parentClosing = (float) $product->warehouseStocks->where('warehouse_id', $warehouseId)->sum('total_pieces');
            } else {
                $parentClosing = (float) $product->warehouseStocks->sum('total_pieces');
            }
            [$parentPurchased, $parentPurchaseAmount] = $this->getPurchasedQtyAndNetAmount($product->id, ['from' => null, 'to' => null], $warehouseId);
            $saleStatsQuery = \DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sale_items.product_id', $product->id)->whereIn('sales.sale_status', ['posted', 'returned']);
            if ($warehouseId && $warehouseId !== 'all') $saleStatsQuery->where('sale_items.warehouse_id', $warehouseId);
            $saleStats = $saleStatsQuery->selectRaw('COALESCE(SUM(sale_items.total_pieces),0) as total_qty')->first();
            $parentSold = (float) $saleStats->total_qty;
            $retQuery = \DB::table('stock_movements')->where('product_id', $product->id)->where('type', 'sale_return');
            if ($warehouseId && $warehouseId !== 'all') $retQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
            $parentReturnedQty = (float) $retQuery->sum('qty');
            $pRetQuery = \DB::table('purchase_return_items as pri')->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')->where('pri.product_id', $product->id);
            if ($warehouseId && $warehouseId !== 'all') $pRetQuery->where('pr.warehouse_id', $warehouseId);
            $parentPReturned = (float) $pRetQuery->sum('pri.qty');
            $adjQuery = \DB::table('stock_movements')->where('product_id', $product->id)->where('type', 'adjustment')->where(function($q) { $q->whereNull('ref_type')->orWhereNotIn('ref_type', ['INIT', 'OPENING_STOCK']); });
            if ($warehouseId && $warehouseId !== 'all') $adjQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
            $parentAdjustments = (float) $adjQuery->sum('qty');
            $parentOpening = max(0, $parentClosing - $parentPurchased + $parentSold - $parentReturnedQty + $parentPReturned - $parentAdjustments);
        } else {
            $salesQuery = \DB::table('sale_items')->join('sales', 'sales.id', '=', 'sale_items.sale_id')->where('sale_items.product_id', $product->id)->whereIn('sales.sale_status', ['posted', 'returned']);
            if ($warehouseId && $warehouseId !== 'all') $salesQuery->where('sale_items.warehouse_id', $warehouseId);
            $salesList = $salesQuery->select('sale_items.total_pieces', 'sale_items.total', 'sale_items.color')->get();
            $webSalesQuery = \DB::table('ecommerce_order_items as eoi')->join('ecommerce_orders as eo', 'eo.id', '=', 'eoi.ecommerce_order_id')->where('eoi.product_id', $product->id)->where('eo.is_stock_deducted', 1);
            if ($warehouseId && $warehouseId !== 'all' && $warehouseId != 1) $webSalesQuery->whereRaw('1 = 0');
            $webSalesList = $webSalesQuery->select('eoi.quantity as total_pieces', 'eoi.total', 'eoi.color', 'eoi.size')->get();
            $salesListArray = $salesList->toArray();
            foreach ($webSalesList as $wItem) {
                $salesListArray[] = (object) [
                    'total_pieces' => $wItem->total_pieces,
                    'total' => $wItem->total,
                    'color' => json_encode(['color' => $wItem->color ?: '-', 'size' => $wItem->size ?: '-'])
                ];
            }
            $salesList = collect($salesListArray);
            $returnsQuery = \DB::table('sale_return_items as sri')->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')->where('sri.product_id', $product->id);
            if ($warehouseId && $warehouseId !== 'all') $returnsQuery->where('sri.warehouse_id', $warehouseId);
            $returnsList = $returnsQuery->select('sri.qty', 'sri.color', 'sr.sale_id')->get();
            $purchasesQuery = \DB::table('purchase_items as pi')->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')->where('pi.product_id', $product->id)->whereIn('pur.status_purchase', ['approved', 'Returned', 'Partial']);
            if ($warehouseId && $warehouseId !== 'all') $purchasesQuery->where('pur.warehouse_id', $warehouseId);
            $purchasesList = $purchasesQuery->select('pi.qty', 'pi.unit', 'pi.pieces_per_box', 'pi.boxes_qty', 'pi.loose_qty', 'pi.line_total', 'pi.color')->get();
            $pReturnsQuery = \DB::table('purchase_return_items as pri')->where('pri.product_id', $product->id);
            $purchaseReturnsList = $pReturnsQuery->select('pri.qty', 'pri.line_total', 'pri.color')->get();
            $adjQuery = \DB::table('stock_movements')->where('product_id', $product->id)->where('type', 'adjustment')->where(function($q) { $q->whereNull('ref_type')->orWhereNotIn('ref_type', ['INIT', 'OPENING_STOCK']); });
            if ($warehouseId && $warehouseId !== 'all') $adjQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
            $adjList = $adjQuery->select('qty', 'note', 'ref_type')->get();
            $saleIds = $returnsList->pluck('sale_id')->unique()->toArray();
            $saleItemsMap = [];
            if (!empty($saleIds)) {
                $siList = \DB::table('sale_items')->whereIn('sale_id', $saleIds)->where('product_id', $product->id)->select('sale_id', 'color')->get();
                foreach ($siList as $si) {
                    $saleItemsMap[$si->sale_id][] = $si->color;
                }
            }
        }
        
        foreach ($parsedVariants as $v) {
            $vName = $v['name'] ?? $product->item_name;
            $vSize = $v['size'] ?? '-';
            $vColor = $v['color'] ?? '-';
            
            $vUnitName = $v['unit'] ?? $unitName;
            if ($product->size_mode === 'by_bandal' && strtolower($vUnitName) === 'carton') {
                $vUnitName = 'Bundal';
            }
            $isCartonMode = (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || strtolower($vUnitName) === 'carton');
            $ppb = (float) ($product->pieces_per_box ?? 1);
            if ($isCartonMode) {
                $vConv = (float) ($v['conv_factor'] ?? 0);
                if ($vConv > 0) $ppb = $vConv;
            }
            
            if ($product->size_mode === 'by_kg') {
                $factor = isset($v['conv_factor']) ? (float)$v['conv_factor'] : 1.0;
                $factor = $factor > 0 ? $factor : 1.0;
                $initial = $parentOpening / $factor;
                $purchased = $parentPurchased / $factor;
                $sold = $parentSold / $factor;
                $returnedQty = $parentReturnedQty / $factor;
                $pReturned = $parentPReturned / $factor;
                $adjustments = $parentAdjustments / $factor;
                $balance = $parentClosing / $factor;
            } else {
                $purchased = 0;
                foreach ($purchasesList as $pItem) {
                    if ($this->matchSaleItemToVariant($pItem, $v)) {
                        $pUnit = strtolower(trim($pItem->unit ?? ''));
                        $pPPB = (float) ($pItem->pieces_per_box > 0 ? $pItem->pieces_per_box : $ppb);
                        if ($pPPB <= 0) $pPPB = 1;
                        if (in_array($pUnit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl'])) {
                            if (isset($pItem->boxes_qty) && ($pItem->boxes_qty > 0 || $pItem->loose_qty > 0)) {
                                $pPieces = (((int) $pItem->boxes_qty) * $pPPB) + ((int) $pItem->loose_qty);
                            } else {
                                [$b, $l] = \App\Http\Controllers\PurchaseController::parseCartonQty($pItem->qty);
                                $pPieces = ($b * $pPPB) + $l;
                            }
                        } elseif (in_array($pUnit, ['gm', 'g'])) {
                            $pPieces = ((float) $pItem->qty) / 1000.0;
                        } else {
                            $pPieces = (float) $pItem->qty;
                        }
                        $purchased += $pPieces;
                    }
                }
                
                $pReturned = 0;
                foreach ($purchaseReturnsList as $prItem) {
                    if ($this->matchSaleItemToVariant($prItem, $v)) {
                        $pReturned += (float) $prItem->qty;
                    }
                }
                
                $sold = 0;
                foreach ($salesList as $sItem) {
                    if ($this->matchSaleItemToVariant($sItem, $v)) {
                        $sold += (float) $sItem->total_pieces;
                    }
                }
                
                $returnedQty = 0;
                foreach ($returnsList as $rItem) {
                    $rColor = $rItem->color;
                    if (empty($rColor)) {
                        $saleColors = $saleItemsMap[$rItem->sale_id] ?? [];
                        $rColor = !empty($saleColors) ? $saleColors[0] : '';
                    }
                    $rItemCopy = (object)['qty' => $rItem->qty, 'color' => $rColor];
                    if ($this->matchSaleItemToVariant($rItemCopy, $v)) {
                        $returnedQty += (float) $rItem->qty;
                    }
                }
                
                $adjustments = 0;
                foreach ($adjList as $adjItem) {
                    if ($this->matchAdjustmentToVariant($adjItem, $v)) {
                        $adjustments += (float) $adjItem->qty;
                    }
                }

                // Determine warehouse-specific closing stock accurately
                $whPieces = ($warehouseId && $warehouseId !== 'all')
                    ? (float) $product->warehouseStocks->where('warehouse_id', $warehouseId)->sum('total_pieces')
                    : (float) $product->warehouseStocks->sum('total_pieces');

                $vBatchStock = null;
                if ($product->tracking_type === 'batch') {
                    $bQuery = \App\Models\ProductBatch::where('product_id', $product->id);
                    if (count($parsedVariants) > 1) {
                        $cleanVKey = str_replace(' ', '', $vKey);
                        $bQuery->where(function($q) use ($vKey, $cleanVKey, $vName) {
                            $q->where('variant_key', $vKey)
                              ->orWhere('variant_key', $cleanVKey)
                              ->orWhere('variant_key', $vName);
                        });
                    }
                    if ($warehouseId && $warehouseId !== 'all') {
                        $bQuery->where('warehouse_id', $warehouseId);
                    }
                    $vBatchStock = (float) $bQuery->sum('qty');
                } elseif ($product->tracking_type === 'serial') {
                    $sQuery = \App\Models\ProductSerial::where('product_id', $product->id)->where('status', 'available');
                    if ($warehouseId && $warehouseId !== 'all') {
                        $sQuery->where('warehouse_id', $warehouseId);
                    }
                    $vBatchStock = (float) $sQuery->count();
                }

                if ($vBatchStock !== null) {
                    $balance = max(0, $vBatchStock);
                } elseif (count($parsedVariants) === 1) {
                    $balance = max(0, $whPieces);
                } else {
                    $totalVRawStock = 0;
                    foreach ($parsedVariants as $pv) {
                        $totalVRawStock += (float) ($pv['stock'] ?? 0);
                    }
                    $vRaw = (float) ($v['stock'] ?? 0);
                    if ($totalVRawStock > 0 && $whPieces > 0) {
                        $balance = max(0, round(($vRaw / $totalVRawStock) * $whPieces, 2));
                    } else {
                        $balance = ($warehouseId && $warehouseId !== 'all') ? 0 : max(0, $whPieces);
                    }
                }
                
                $initial = max(0, $balance - $purchased + $sold - $returnedQty + $pReturned - $adjustments);
            }
            
            $results[] = [
                'name' => $vName . (!empty(array_filter([$vSize, $vColor], fn($val) => !empty($val) && trim($val) !== '-' && trim($val) !== '')) ? ' (' . implode(' | ', array_filter([$vSize, $vColor], fn($val) => !empty($val) && trim($val) !== '-' && trim($val) !== '')) . ')' : ''),
                'code' => $product->item_code,
                'stock' => $balance,
                "alert" => isset($v["alert"]) ? (float)$v["alert"] : null,
                "unit_name" => $vUnitName,
                "is_carton_mode" => $isCartonMode,
                "ppb" => $ppb,
                "size_mode" => $product->size_mode,
                'is_variant' => true,
                "purch_price" => isset($v["purch_price"]) && $v["purch_price"] !== "" ? (float)$v["purch_price"] : null
            ];
        }
        
        return $results;
    }

    public function fetchInventoryDemand(Request $request)
    {
        $brandId     = $request->brand_id ?: $request->company_id;
        $categoryId  = $request->category_id;
        $warehouseId = $request->warehouse_id;
        $demandOnly  = $request->has('demand_only') ? filter_var($request->demand_only, FILTER_VALIDATE_BOOLEAN) : false;
        $search      = $request->search;

        $query = Product::with(['warehouseStocks', 'unit', 'category_relation', 'brand']);

        if ($brandId && $brandId !== 'all') {
            $query->where('brand_id', $brandId);
        }
        if ($categoryId && $categoryId !== 'all') {
            $query->where('category_id', $categoryId);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('item_name')->get();

        $rows = [];
        $totalItemsCount   = 0;
        $totalDemandItems  = 0;
        $totalStockDeficit = 0;
        $totalDemandQty    = 0;
        $totalCostAmount   = 0;

        foreach ($products as $product) {
            $variants = $this->getVariantStockBalances($product, $warehouseId);
            
            // Purchase Price / Unit Cost
            $purchPrice = 0;
            if ($product->size_mode === "by_size" || $product->size_mode === "by_m2") {
                $m2PerPiece = (float) ($product->pieces_per_m2 ?? 0);
                $purchPerM2 = (float) ($product->purchase_price_per_m2 ?? 0);
                $purchPrice = $m2PerPiece * $purchPerM2;
            } else {
                $purchPrice = (float) ($product->purchase_price_per_piece ?? 0);
            }
            
            $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
            $minQty = (float) ($product->alert_quantity ?? (($product->alert_carton_quantity ?? 0) * $ppb));
            
            foreach ($variants as $variant) {
                $isCartonMode = $variant["is_carton_mode"];
                $vPPB = $variant["ppb"];
                $vUnitName = $variant["unit_name"];
                $vSizeMode = $variant["size_mode"];
                $stock = $variant["stock"];
                
                if (isset($variant["alert"]) && $variant["alert"] !== null && $variant["alert"] !== "") {
                    $vMinQty = (float)$variant["alert"];
                    if ($isCartonMode || ($vPPB > 1 && $vSizeMode === "by_size")) {
                        $vMinQty = $vMinQty * $vPPB;
                    }
                } else {
                    $vMinQty = $minQty;
                }
                
                $reqQty = max(0, $vMinQty - $stock);
                if ($demandOnly && $reqQty <= 0) {
                    continue;
                }
                
                $vPurchPrice = (isset($variant["purch_price"]) && $variant["purch_price"] !== null) ? $variant["purch_price"] : $purchPrice;
                $costAmount = round($reqQty * $vPurchPrice, 2);
                $formatQty = function($qty) use ($isCartonMode, $vPPB, $vUnitName, $vSizeMode) {
                    if ($isCartonMode) {
                        $ctnLbl = ($vSizeMode === 'by_bandal') ? 'Bndl' : 'Ctn';
                        $cartons = (int) floor($qty / $vPPB);
                        $loose   = (int) round($qty - ($cartons * $vPPB));
                        return ($loose > 0) ? "{$cartons} {$ctnLbl} + {$loose} Pcs" : "{$cartons} {$ctnLbl}";
                    } elseif ($vPPB > 1 && $vSizeMode === "by_size") {
                        $cartons = (int) floor($qty / $vPPB);
                        $loose   = (int) round($qty - ($cartons * $vPPB));
                        return ($loose > 0) ? "{$cartons} Box . {$loose} Pcs" : "{$cartons} Boxes";
                    } else {
                        return number_format($qty, (in_array($vSizeMode, ["by_kg","by_gm","by_ton","by_meter","by_feet"]) ? 2 : 0)) . " {$vUnitName}";
                    }
                };
                
                $formatCartonOnly = function($qty) use ($isCartonMode, $vPPB, $vUnitName, $vSizeMode) {
                    if ($isCartonMode) {
                        $ctnLbl = ($vSizeMode === 'by_bandal') ? 'Bndl' : 'Ctn';
                        $cartons = round($qty / $vPPB, 2);
                        return "{$cartons} {$ctnLbl}";
                    } elseif ($vPPB > 1 && $vSizeMode === "by_size") {
                        $cartons = round($qty / $vPPB, 2);
                        return "{$cartons} Boxes";
                    } else {
                        return number_format($qty, (in_array($vSizeMode, ["by_kg","by_gm","by_ton","by_meter","by_feet"]) ? 2 : 0)) . " {$vUnitName}";
                    }
                };
                
                $formattedStock = $formatQty($stock);
                $formattedMin = $formatCartonOnly($vMinQty);
                $formattedReq = $formatCartonOnly($reqQty);
                $rows[] = [
                    "id"          => $product->id,
                    "code"        => $variant["code"] ?: "0",
                    "item_name"   => $variant["name"],
                    "category"    => $product->category_relation->name ?? "-",
                    "company"     => $product->brand->name ?? "-",
                    "unit"        => ($product->size_mode === 'by_bandal') ? 'Bundal' : ($product->unit->name ?? "Pcs"),
                    "stock"       => round($stock, 2),
                    "formatted_stock" => $formattedStock,
                    "p_price"     => round($vPurchPrice, 2),
                    "min_qty"     => round($vMinQty, 2),
                    "formatted_min_qty" => $formattedMin,
                    "req_qty"     => round($reqQty, 2),
                    "formatted_req_qty" => $formattedReq,
                    "cost_amount" => $costAmount,
                    "status"      => $stock <= 0 ? "out_of_stock" : ($stock < $vMinQty ? "low_stock" : "adequate"),
                ];
                
                $totalItemsCount++;
                if ($reqQty > 0) {
                    $totalDemandItems++;
                }
                if ($stock < 0) {
                    $totalStockDeficit += abs($stock);
                }
                $totalDemandQty  += $reqQty;
                $totalCostAmount += $costAmount;
            }
        }
        return response()->json([
            'success' => true,
            'rows'    => $rows,
            'summary' => [
                'total_items'        => $totalItemsCount,
                'demand_items_count' => $totalDemandItems,
                'total_deficit'      => round($totalStockDeficit, 2),
                'total_demand_qty'   => round($totalDemandQty, 2),
                'total_cost_amount'  => round($totalCostAmount, 2),
            ],
            'generated_on' => now()->format('d-m-Y H:i:s'),
        ]);
    }

    public function item_stock_report()
    {
        $user = auth()->user();
        $categories = Category::orderBy('name')->get();
        $units      = Unit::orderBy('name')->get();

        // Warehouse Permission Filter: Show warehouses only if user has warehouse permission
        if ($user && ($user->email === 'admin@admin.com' || $user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->can('warehouse.view') || $user->can('warehouse.stock.view'))) {
            $warehouses = Warehouse::orderBy('warehouse_name')->get();
        } else {
            $warehouses = collect();
        }

        return view('admin_panel.reporting.item_stock_report', compact('categories', 'warehouses', 'units'));
    }

    // AJAX endpoint to fetch report rows
    public function fetchItemStock(Request $request)
    {
        $productId   = $request->product_id;
        $variantKey  = $request->variant_key;
        $categoryId  = $request->category_id;
        $warehouseId = $request->warehouse_id;
        $unitType    = $request->unit_type; // 'all', 'cartons_pcs', 'weight_kg', 'area_m2'
        $reportMode  = $request->report_mode ?: 'summary'; // 'summary' vs 'ledger'
        $dateFrom    = $request->date_from;
        $dateTo      = $request->date_to;

        $productsQuery = Product::with(['warehouseStocks', 'unit', 'category_relation']);

        if ($productId && $productId !== 'all') {
            $productsQuery->where('id', $productId);
        }
        if ($categoryId && $categoryId !== 'all') {
            $productsQuery->where('category_id', $categoryId);
        }

        // Unit Mode Filter
        if ($unitType && $unitType !== 'all') {
            if ($unitType === 'weight_kg') {
                $productsQuery->whereIn('size_mode', ['by_kg', 'by_gm', 'by_ton']);
            } elseif ($unitType === 'area_m2') {
                $productsQuery->whereIn('size_mode', ['by_size', 'by_m2']);
            } elseif ($unitType === 'cartons_pcs') {
                $productsQuery->whereIn('size_mode', ['by_cartons', 'by_pieces', 'std']);
            }
        }

        $products = $productsQuery->orderBy('item_name')->get();

        $rows = [];
        $grandTotalValue   = 0;
        $totalCurrentStock = 0;
        $totalAdjustments  = 0;
        $totalSoldAmount   = 0;

        foreach ($products as $product) {
            // Determine default purchase price per piece
            $productPurchPrice = 0;
            if ($product->size_mode === 'by_size' || $product->size_mode === 'by_m2') {
                $m2PerPiece         = (float) ($product->pieces_per_m2 ?? 0);
                $purchPerM2         = (float) ($product->purchase_price_per_m2 ?? 0);
                $productPurchPrice  = $m2PerPiece * $purchPerM2;
            } else {
                $productPurchPrice = (float) ($product->purchase_price_per_piece ?? 0);
            }

            // Determine size mode & unit display label
            $unitName = ($product->size_mode === 'by_bandal') ? 'Bundal' : ($product->unit->name ?? 'Pcs');
            $sizeMode = $product->size_mode ?: 'std';

            // Check if product has variants
            $parsedVariants = [];
            if ($product->color) {
                try {
                    $decoded = json_decode($product->color, true);
                    if (is_array($decoded) && count($decoded) > 0 && isset($decoded[0]['name'])) {
                        $parsedVariants = $decoded;
                    }
                } catch (\Exception $e) {}
            }

            if (count($parsedVariants) > 0) {
                if ($product->size_mode === 'by_kg') {
                    // Fetch parent level weight stock and transactions
                    if ($warehouseId && $warehouseId !== 'all') {
                        $parentClosing = (float) $product->warehouseStocks->where('warehouse_id', $warehouseId)->sum('total_pieces');
                    } else {
                        $parentClosing = (float) $product->warehouseStocks->sum('total_pieces');
                    }

                    [$parentPurchased, $parentPurchaseAmount] = $this->getPurchasedQtyAndNetAmount($product->id, ['from' => $dateFrom, 'to' => $dateTo], $warehouseId);

                    // Parent Sold qty & amount
                    $saleStatsQuery = DB::table('sale_items')
                        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->where('sale_items.product_id', $product->id)
                        ->whereIn('sales.sale_status', ['posted', 'returned']);
                    if ($warehouseId && $warehouseId !== 'all') $saleStatsQuery->where('sale_items.warehouse_id', $warehouseId);
                    if ($dateFrom) $saleStatsQuery->whereDate('sale_items.created_at', '>=', $dateFrom);
                    if ($dateTo)   $saleStatsQuery->whereDate('sale_items.created_at', '<=', $dateTo);
                    $saleStats = $saleStatsQuery->selectRaw('COALESCE(SUM(sale_items.total_pieces),0) as total_qty, COALESCE(SUM(sale_items.total),0) as total_amount')->first();
                    $parentSold       = (float) $saleStats->total_qty;
                    $parentSaleAmount = (float) $saleStats->total_amount;

                    // Parent Returned qty
                    $retQuery = DB::table('stock_movements')
                        ->where('product_id', $product->id)
                        ->where('type', 'sale_return');
                    if ($warehouseId && $warehouseId !== 'all') {
                        $retQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
                    }
                    if ($dateFrom) $retQuery->whereDate('created_at', '>=', $dateFrom);
                    if ($dateTo)   $retQuery->whereDate('created_at', '<=', $dateTo);
                    $parentReturnedQty = (float) $retQuery->sum('qty');

                    // Parent Purchase Returned qty
                    $pRetQuery = DB::table('purchase_return_items as pri')
                        ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
                        ->where('pri.product_id', $product->id);
                    if ($warehouseId && $warehouseId !== 'all') {
                        $pRetQuery->where('pr.warehouse_id', $warehouseId);
                    }
                    if ($dateFrom) $pRetQuery->whereDate('pr.created_at', '>=', $dateFrom);
                    if ($dateTo)   $pRetQuery->whereDate('pr.created_at', '<=', $dateTo);
                    $parentPReturned = (float) $pRetQuery->sum('pri.qty');

                    // Parent Adjustments
                    $adjQuery = DB::table('stock_movements')
                        ->where('product_id', $product->id)
                        ->where('type', 'adjustment')
                        ->where(function($q) {
                            $q->whereNull('ref_type')->orWhereNotIn('ref_type', ['INIT', 'OPENING_STOCK']);
                        });
                    if ($warehouseId && $warehouseId !== 'all') {
                        $adjQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
                    }
                    if ($dateFrom) $adjQuery->whereDate('created_at', '>=', $dateFrom);
                    if ($dateTo)   $adjQuery->whereDate('created_at', '<=', $dateTo);
                    $parentAdjustments = (float) $adjQuery->sum('qty');

                    // Parent Opening stock
                    $parentOpening = max(0, $parentClosing - $parentPurchased + $parentSold - $parentReturnedQty + $parentPReturned - $parentAdjustments);
                } else {
                    // Fetch all sales and returns for this product to distribute
                    $salesQuery = DB::table('sale_items')
                        ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                        ->where('sale_items.product_id', $product->id)
                        ->whereIn('sales.sale_status', ['posted', 'returned']);
                    if ($warehouseId && $warehouseId !== 'all') {
                        $salesQuery->where('sale_items.warehouse_id', $warehouseId);
                    }
                    if ($dateFrom) $salesQuery->whereDate('sale_items.created_at', '>=', $dateFrom);
                    if ($dateTo)   $salesQuery->whereDate('sale_items.created_at', '<=', $dateTo);
                    $salesList = $salesQuery->select('sale_items.total_pieces', 'sale_items.total', 'sale_items.color')->get();

                    // Fetch confirmed web sales
                    $webSalesQuery = DB::table('ecommerce_order_items as eoi')
                        ->join('ecommerce_orders as eo', 'eo.id', '=', 'eoi.ecommerce_order_id')
                        ->where('eoi.product_id', $product->id)
                        ->where('eo.is_stock_deducted', 1);

                    if ($warehouseId && $warehouseId !== 'all' && $warehouseId != 1) {
                        $webSalesQuery->whereRaw('1 = 0');
                    }
                    if ($dateFrom) $webSalesQuery->whereDate('eo.created_at', '>=', $dateFrom);
                    if ($dateTo)   $webSalesQuery->whereDate('eo.created_at', '<=', $dateTo);

                    $webSalesList = $webSalesQuery->select('eoi.quantity as total_pieces', 'eoi.total', 'eoi.color', 'eoi.size')->get();

                    $salesListArray = $salesList->toArray();
                    foreach ($webSalesList as $wItem) {
                        $salesListArray[] = (object) [
                            'total_pieces' => $wItem->total_pieces,
                            'total' => $wItem->total,
                            'color' => json_encode([
                                'color' => $wItem->color ?: '-',
                                'size' => $wItem->size ?: '-'
                            ])
                        ];
                    }
                    $salesList = collect($salesListArray);

                    $returnsQuery = DB::table('sale_return_items as sri')
                        ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                        ->where('sri.product_id', $product->id);
                    if ($warehouseId && $warehouseId !== 'all') {
                        $returnsQuery->where('sri.warehouse_id', $warehouseId);
                    }
                    if ($dateFrom) $returnsQuery->whereDate('sr.created_at', '>=', $dateFrom);
                    if ($dateTo)   $returnsQuery->whereDate('sr.created_at', '<=', $dateTo);
                    $returnsList = $returnsQuery->select('sri.qty', 'sri.color', 'sr.sale_id')->get();

                    // Fetch all approved/returned purchases
                    $purchasesQuery = DB::table('purchase_items as pi')
                        ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
                        ->where('pi.product_id', $product->id)
                        ->whereIn('pur.status_purchase', ['approved', 'Returned', 'Partial']);
                    if ($warehouseId && $warehouseId !== 'all') {
                        $purchasesQuery->where('pur.warehouse_id', $warehouseId);
                    }
                    if ($dateFrom) $purchasesQuery->whereDate('pur.created_at', '>=', $dateFrom);
                    if ($dateTo)   $purchasesQuery->whereDate('pur.created_at', '<=', $dateTo);
                    $purchasesList = $purchasesQuery->select('pi.qty', 'pi.unit', 'pi.pieces_per_box', 'pi.boxes_qty', 'pi.loose_qty', 'pi.line_total', 'pi.color')->get();

                    // Fetch all purchase returns
                    $pReturnsQuery = DB::table('purchase_return_items as pri')->where('pri.product_id', $product->id);
                    if ($dateFrom) $pReturnsQuery->whereDate('pri.created_at', '>=', $dateFrom);
                    if ($dateTo)   $pReturnsQuery->whereDate('pri.created_at', '<=', $dateTo);
                    $purchaseReturnsList = $pReturnsQuery->select('pri.qty', 'pri.line_total', 'pri.color')->get();

                    // Fetch Stock Adjustments
                    $adjQuery = DB::table('stock_movements')
                        ->where('product_id', $product->id)
                        ->where('type', 'adjustment')
                        ->where(function($q) {
                            $q->whereNull('ref_type')->orWhereNotIn('ref_type', ['INIT', 'OPENING_STOCK']);
                        });
                    if ($warehouseId && $warehouseId !== 'all') {
                        $adjQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
                    }
                    if ($dateFrom) $adjQuery->whereDate('created_at', '>=', $dateFrom);
                    if ($dateTo)   $adjQuery->whereDate('created_at', '<=', $dateTo);
                    $adjList = $adjQuery->select('qty', 'note', 'ref_type')->get();

                    $saleIds = $returnsList->pluck('sale_id')->unique()->toArray();
                    $saleItemsMap = [];
                    if (!empty($saleIds)) {
                        $siList = DB::table('sale_items')
                            ->whereIn('sale_id', $saleIds)
                            ->where('product_id', $product->id)
                            ->select('sale_id', 'color')
                            ->get();
                        foreach ($siList as $si) {
                            $saleItemsMap[$si->sale_id][] = $si->color;
                        }
                    }
                }
                foreach ($parsedVariants as $v) {
                    $vName = $v['name'] ?? $product->item_name;
                    $vSize = $v['size'] ?? '-';
                    $vColor = $v['color'] ?? '-';
                    $vKey = trim($vName) . '|' . trim($vSize) . '|' . trim($vColor);

                    if ($variantKey && $variantKey !== 'all' && $variantKey !== '') {
                        if ($vKey !== trim($variantKey) && trim($v['name'] ?? '') !== trim($variantKey)) {
                            continue;
                        }
                    }

                    // Variant Unit Logic
                    $vUnitName = $v['unit'] ?? $unitName;
                    if ($product->size_mode === 'by_bandal' && strtolower($vUnitName) === 'carton') {
                        $vUnitName = 'Bundal';
                    }
                    $isCartonMode = (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || strtolower($vUnitName) === 'carton');

                    // Cartons / Loose / Unit Formatting
                    $ppb = (float) ($product->pieces_per_box ?? 1);
                    if ($isCartonMode) {
                        $vConv = (float) ($v['conv_factor'] ?? 0);
                        if ($vConv > 0) $ppb = $vConv;
                    }

                    if ($product->size_mode === 'by_kg') {
                        $factor = isset($v['conv_factor']) ? (float)$v['conv_factor'] : 1.0;
                        $factor = $factor > 0 ? $factor : 1.0;

                        $initial        = $parentOpening / $factor;
                        $purchased      = $parentPurchased / $factor;
                        $purchaseAmount = $parentPurchaseAmount;
                        $sold           = $parentSold / $factor;
                        $saleAmount     = $parentSaleAmount;
                        $returnedQty    = $parentReturnedQty / $factor;
                        $pReturned      = $parentPReturned / $factor;
                        $adjustments    = $parentAdjustments / $factor;
                        $balance        = $parentClosing / $factor;
                    } else {
                        // Purchased for variant
                        $purchased = 0; $purchaseAmount = 0;
                        foreach ($purchasesList as $pItem) {
                            if ($this->matchSaleItemToVariant($pItem, $v)) {
                                $pUnit = strtolower(trim($pItem->unit ?? ''));
                                $pPPB = (float) ($pItem->pieces_per_box > 0 ? $pItem->pieces_per_box : $ppb);
                                if ($pPPB <= 0) $pPPB = 1;

                                if (in_array($pUnit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl'])) {
                                    if (isset($pItem->boxes_qty) && ($pItem->boxes_qty > 0 || $pItem->loose_qty > 0)) {
                                        $pPieces = (((int) $pItem->boxes_qty) * $pPPB) + ((int) $pItem->loose_qty);
                                    } else {
                                        [$b, $l] = \App\Http\Controllers\PurchaseController::parseCartonQty($pItem->qty);
                                        $pPieces = ($b * $pPPB) + $l;
                                    }
                                } elseif (in_array($pUnit, ['gm', 'g'])) {
                                    $pPieces = ((float) $pItem->qty) / 1000.0;
                                } else {
                                    $pPieces = (float) $pItem->qty;
                                }

                                $purchased += $pPieces;
                                $purchaseAmount += (float) $pItem->line_total;
                            }
                        }

                        // Purchase Returned for variant
                        $pReturned = 0; $pReturnAmount = 0;
                        foreach ($purchaseReturnsList as $prItem) {
                            if ($this->matchSaleItemToVariant($prItem, $v)) {
                                $pReturned += (float) $prItem->qty;
                                $pReturnAmount += (float) $prItem->line_total;
                            }
                        }

                        // Sold for variant
                        $sold = 0; $saleAmount = 0;
                        foreach ($salesList as $sItem) {
                            if ($this->matchSaleItemToVariant($sItem, $v)) {
                                $sold += (float) $sItem->total_pieces;
                                $saleAmount += (float) $sItem->total;
                            }
                        }

                        // Returned for variant
                        $returnedQty = 0;
                        foreach ($returnsList as $rItem) {
                            $rColor = $rItem->color;
                            if (empty($rColor)) {
                                $saleColors = $saleItemsMap[$rItem->sale_id] ?? [];
                                $rColor = !empty($saleColors) ? $saleColors[0] : '';
                            }
                            $rItemCopy = (object)['qty' => $rItem->qty, 'color' => $rColor];
                            if ($this->matchSaleItemToVariant($rItemCopy, $v)) {
                                $returnedQty += (float) $rItem->qty;
                            }
                        }

                        // Adjustments for variant
                        $adjustments = 0;
                        foreach ($adjList as $adjItem) {
                            if ($this->matchAdjustmentToVariant($adjItem, $v)) {
                                $adjustments += (float) $adjItem->qty;
                            }
                        }

                        // Determine warehouse-specific closing stock accurately
                        $whPieces = ($warehouseId && $warehouseId !== 'all')
                            ? (float) $product->warehouseStocks->where('warehouse_id', $warehouseId)->sum('total_pieces')
                            : (float) $product->warehouseStocks->sum('total_pieces');

                        $vBatchStock = null;
                        if ($product->tracking_type === 'batch') {
                            $bQuery = \App\Models\ProductBatch::where('product_id', $product->id);
                            if (count($parsedVariants) > 1) {
                                $cleanVKey = str_replace(' ', '', $vKey);
                                $bQuery->where(function($q) use ($vKey, $cleanVKey, $vName) {
                                    $q->where('variant_key', $vKey)
                                      ->orWhere('variant_key', $cleanVKey)
                                      ->orWhere('variant_key', $vName);
                                });
                            }
                            if ($warehouseId && $warehouseId !== 'all') {
                                $bQuery->where('warehouse_id', $warehouseId);
                            }
                            $vBatchStock = (float) $bQuery->sum('qty');
                        } elseif ($product->tracking_type === 'serial') {
                            $sQuery = \App\Models\ProductSerial::where('product_id', $product->id)->where('status', 'available');
                            if ($warehouseId && $warehouseId !== 'all') {
                                $sQuery->where('warehouse_id', $warehouseId);
                            }
                            $vBatchStock = (float) $sQuery->count();
                        }

                        if ($vBatchStock !== null) {
                            $balance = max(0, $vBatchStock);
                        } elseif (count($parsedVariants) === 1) {
                            $balance = max(0, $whPieces);
                        } else {
                            $totalVRawStock = 0;
                            foreach ($parsedVariants as $pv) {
                                $totalVRawStock += (float) ($pv['stock'] ?? 0);
                            }
                            $vRaw = (float) ($v['stock'] ?? 0);
                            if ($totalVRawStock > 0 && $whPieces > 0) {
                                $balance = max(0, round(($vRaw / $totalVRawStock) * $whPieces, 2));
                            } else {
                                $balance = ($warehouseId && $warehouseId !== 'all') ? 0 : max(0, $whPieces);
                            }
                        }

                        // Initial Stock for this warehouse context:
                        // Initial = Balance - Purchased + Sold - Returned + Purchased Returned - Adjustments
                        $initial = max(0, $balance - $purchased + $sold - $returnedQty + $pReturned - $adjustments);
                    }

                    // Weighted Average Purchase Price
                    $vPurchPrice = (float) ($v['purch_price'] ?? $productPurchPrice);
                    $initialAmount = $initial * $vPurchPrice;
                    $totalQtyIn = $initial + $purchased;
                    $totalAmountIn = $initialAmount + $purchaseAmount;
                    $averagePrice = $totalQtyIn > 0 ? ($totalAmountIn / $totalQtyIn) : $vPurchPrice;

                    $stockValue = $balance * $averagePrice;
                    $grandTotalValue += $stockValue;
                    $totalCurrentStock += $balance;
                    $totalAdjustments  += $adjustments;
                    $totalSoldAmount   += $saleAmount;

                    if ($isCartonMode) {
                        $ctnLbl = ($product->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';
                        $cartons = (int) floor($balance / $ppb);
                        $loose   = (int) round($balance - ($cartons * $ppb));
                        $formattedStock = ($loose > 0) ? "{$cartons} {$ctnLbl} + {$loose} Pcs" : "{$cartons} {$ctnLbl}";
                        $cartonDisplay = ($loose > 0) ? "{$cartons} {$ctnLbl} + {$loose} Pcs <span class='text-muted small'>({$ppb} pcs/" . strtolower($ctnLbl) . ")</span>" : "{$cartons} {$ctnLbl} <span class='text-muted small'>({$ppb} pcs/" . strtolower($ctnLbl) . ")</span>";
                    } elseif ($ppb > 1 && $product->size_mode === 'by_size') {
                        $cartons = (int) floor($balance / $ppb);
                        $loose   = (int) round($balance - ($cartons * $ppb));
                        $formattedStock = ($loose > 0) ? "{$cartons} Box . {$loose} Pcs" : "{$cartons} Boxes";
                        $cartonDisplay = ($loose > 0) ? "{$cartons} Box + {$loose} Pcs" : "{$cartons} Box";
                    } else {
                        $cartons = '-';
                        $loose   = $balance;
                        $formattedStock = number_format($balance, (in_array($product->size_mode, ['by_kg','by_gm','by_ton','by_meter','by_feet']) ? 2 : 0)) . " {$vUnitName}";
                        $cartonDisplay = '—';
                    }

                    // Stock Status Badge
                    $status = 'healthy';
                    $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                    $minQ = $product->alert_quantity ?? (($product->alert_carton_quantity ?? 0) * $ppb);
                    if ($balance <= 0) $status = 'out_of_stock';
                    elseif ($minQ > 0 && $balance < $minQ) $status = 'low_stock';

                    $rows[] = [
                        'id'              => $product->id,
                        'item_code'       => $product->item_code,
                        'item_name'       => $vName . (!empty(array_filter([$vSize, $vColor], fn($val) => !empty($val) && trim($val) !== '-' && trim($val) !== '')) ? ' (' . implode(' | ', array_filter([$vSize, $vColor], fn($val) => !empty($val) && trim($val) !== '-' && trim($val) !== '')) . ')' : ''),
                        'category_name'   => $product->category_relation->name ?? 'Standard',
                        'unit_name'       => $vUnitName,
                        'size_mode'       => $sizeMode,
                        'initial_stock'   => $initial,
                        'purchased'       => $purchased,
                        'purchase_amount' => $purchaseAmount,
                        'sold'            => $sold,
                        'sale_amount'     => $saleAmount,
                        'returned_qty'    => $returnedQty,
                        'purch_returned_qty' => $pReturned,
                        'adjustments'     => $adjustments,
                        'balance'         => $balance,
                        'formatted_stock' => $formattedStock,
                        'carton_display'  => $cartonDisplay,
                        'cartons'         => $cartons,
                        'loose'           => $loose,
                        'average_price'   => $averagePrice,
                        'stock_value'     => $stockValue,
                        'status'          => $status,
                    ];
                }
            } else {
                // Product has no variants
                if ($variantKey && $variantKey !== 'all' && $variantKey !== '') {
                    continue;
                }

                if ($warehouseId && $warehouseId !== 'all') {
                    $balance = (float) $product->warehouseStocks->where('warehouse_id', $warehouseId)->sum('total_pieces');
                } else {
                    $balance = (float) $product->warehouseStocks->sum('total_pieces');
                }

                [$purchased, $purchaseAmount] = $this->getPurchasedQtyAndNetAmount($product->id, ['from' => $dateFrom, 'to' => $dateTo], $warehouseId);

                // Sold qty & amount
                $saleStatsQuery = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sale_items.product_id', $product->id)
                    ->whereIn('sales.sale_status', ['posted', 'returned']);
                if ($warehouseId && $warehouseId !== 'all') $saleStatsQuery->where('sale_items.warehouse_id', $warehouseId);
                if ($dateFrom) $saleStatsQuery->whereDate('sale_items.created_at', '>=', $dateFrom);
                if ($dateTo)   $saleStatsQuery->whereDate('sale_items.created_at', '<=', $dateTo);
                $saleStats = $saleStatsQuery->selectRaw('COALESCE(SUM(sale_items.total_pieces),0) as total_qty, COALESCE(SUM(sale_items.total),0) as total_amount')->first();

                $sold       = (float) $saleStats->total_qty;
                $saleAmount = (float) $saleStats->total_amount;

                // Returned qty
                $retQuery = DB::table('stock_movements')
                    ->where('product_id', $product->id)
                    ->where('type', 'sale_return');
                if ($warehouseId && $warehouseId !== 'all') {
                    $retQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
                }
                if ($dateFrom) $retQuery->whereDate('created_at', '>=', $dateFrom);
                if ($dateTo)   $retQuery->whereDate('created_at', '<=', $dateTo);
                $returnedQty = (float) $retQuery->sum('qty');

                // Purchase Returned qty
                $pRetQuery = DB::table('purchase_return_items as pri')
                    ->join('purchase_returns as pr', 'pr.id', '=', 'pri.purchase_return_id')
                    ->where('pri.product_id', $product->id);
                if ($warehouseId && $warehouseId !== 'all') {
                    $pRetQuery->where('pr.warehouse_id', $warehouseId);
                }
                if ($dateFrom) $pRetQuery->whereDate('pr.created_at', '>=', $dateFrom);
                if ($dateTo)   $pRetQuery->whereDate('pr.created_at', '<=', $dateTo);
                $pReturned = (float) $pRetQuery->sum('pri.qty');

                // Stock Adjustments
                $adjQuery = DB::table('stock_movements')
                    ->where('product_id', $product->id)
                    ->where('type', 'adjustment')
                    ->where(function($q) {
                        $q->whereNull('ref_type')->orWhereNotIn('ref_type', ['INIT', 'OPENING_STOCK']);
                    });
                if ($warehouseId && $warehouseId !== 'all') {
                    $adjQuery->where('note', 'like', "%Warehouse #{$warehouseId}%");
                }
                if ($dateFrom) $adjQuery->whereDate('created_at', '>=', $dateFrom);
                if ($dateTo)   $adjQuery->whereDate('created_at', '<=', $dateTo);
                $adjustments = (float) $adjQuery->sum('qty');

                // Opening stock
                $initial = max(0, $balance - $purchased + $sold - $returnedQty + $pReturned - $adjustments);

                // Weighted Average Purchase Price
                $initialAmount  = $initial * $productPurchPrice;
                $totalQtyIn     = $initial + $purchased;
                $totalAmountIn  = $initialAmount + $purchaseAmount;
                $averagePrice   = $totalQtyIn > 0 ? ($totalAmountIn / $totalQtyIn) : $productPurchPrice;

                $stockValue        = $balance * $averagePrice;
                $grandTotalValue  += $stockValue;
                $totalCurrentStock += $balance;
                $totalAdjustments  += $adjustments;
                $totalSoldAmount   += $saleAmount;

                // Cartons / Loose
                $isCartonMode = (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || strtolower($unitName) === 'carton');
                $ppb = (float) ($product->pieces_per_box ?? 1);
                if ($ppb <= 0) $ppb = 1;
                if ($isCartonMode) {
                    $ctnLbl = ($product->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';
                    $cartons = (int) floor($balance / $ppb);
                    $loose   = (int) round($balance - ($cartons * $ppb));
                    $formattedStock = ($loose > 0) ? "{$cartons} {$ctnLbl} + {$loose} Pcs" : "{$cartons} {$ctnLbl}";
                    $cartonDisplay = ($loose > 0) ? "{$cartons} {$ctnLbl} + {$loose} Pcs <span class='text-muted small'>({$ppb} pcs/" . strtolower($ctnLbl) . ")</span>" : "{$cartons} {$ctnLbl} <span class='text-muted small'>({$ppb} pcs/" . strtolower($ctnLbl) . ")</span>";
                } elseif ($ppb > 1 && $product->size_mode === 'by_size') {
                    $cartons = (int) floor($balance / $ppb);
                    $loose   = (int) round($balance - ($cartons * $ppb));
                    $formattedStock = ($loose > 0) ? "{$cartons} Box . {$loose} Pcs" : "{$cartons} Boxes";
                    $cartonDisplay = ($loose > 0) ? "{$cartons} Box + {$loose} Pcs" : "{$cartons} Box";
                } else {
                    $cartons = '-';
                    $loose   = $balance;
                    $formattedStock = number_format($balance, (in_array($product->size_mode, ['by_kg','by_gm','by_ton','by_meter','by_feet']) ? 2 : 0)) . " {$unitName}";
                    $cartonDisplay = '—';
                }

                // Stock Status Badge
                $status = 'healthy';
                $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;
                $minQ = $product->alert_quantity ?? (($product->alert_carton_quantity ?? 0) * $ppb);
                if ($balance <= 0) $status = 'out_of_stock';
                elseif ($minQ > 0 && $balance < $minQ) $status = 'low_stock';

                $rows[] = [
                    'id'              => $product->id,
                    'item_code'       => $product->item_code,
                    'item_name'       => $product->item_name,
                    'category_name'   => $product->category_relation->name ?? 'Standard',
                    'unit_name'       => $unitName,
                    'size_mode'       => $sizeMode,
                    'initial_stock'   => $initial,
                    'purchased'       => $purchased,
                    'purchase_amount' => $purchaseAmount,
                    'sold'            => $sold,
                    'sale_amount'     => $saleAmount,
                    'returned_qty'    => $returnedQty,
                    'purch_returned_qty' => $pReturned,
                    'adjustments'     => $adjustments,
                    'balance'         => $balance,
                    'formatted_stock' => $formattedStock,
                    'carton_display'  => $cartonDisplay,
                    'cartons'         => $cartons,
                    'loose'           => $loose,
                    'average_price'   => $averagePrice,
                    'stock_value'     => $stockValue,
                    'status'          => $status,
                ];
            }
        }

        return response()->json([
            'data'                  => $rows,
            'grand_total'           => $grandTotalValue,
            'total_current_stock'   => $totalCurrentStock,
            'total_adjustments_qty' => $totalAdjustments,
            'total_sold_amount'     => $totalSoldAmount,
        ]);
    }

    /**
     * AJAX endpoint to fetch variants for a specific product for filtering
     */
    public function getProductVariants(Request $request, $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'variants' => []]);
        }

        $variants = [];
        if ($product->color) {
            try {
                $decoded = is_string($product->color) ? json_decode($product->color, true) : $product->color;
                if (is_array($decoded) && count($decoded) > 0) {
                    foreach ($decoded as $v) {
                        $vName = $v['name'] ?? $product->item_name;
                        $vSize = $v['size'] ?? '-';
                        $vColor = $v['color'] ?? '-';
                        $vKey = trim($vName) . '|' . trim($vSize) . '|' . trim($vColor);
                        $vText = $vName . (!empty(array_filter([$vSize, $vColor], fn($val) => !empty($val) && trim($val) !== '-' && trim($val) !== '')) ? ' (' . implode(' | ', array_filter([$vSize, $vColor], fn($val) => !empty($val) && trim($val) !== '-' && trim($val) !== '')) . ')' : '');
                        if (!empty($v['barcode'])) {
                            $vText .= ' - [' . $v['barcode'] . ']';
                        }
                        $variants[] = [
                            'key'   => $vKey,
                            'name'  => $vName,
                            'size'  => $vSize,
                            'color' => $vColor,
                            'text'  => $vText,
                        ];
                    }
                }
            } catch (\Exception $e) {}
        }

        return response()->json([
            'success'      => true,
            'has_variants' => count($variants) > 0,
            'variants'     => $variants,
        ]);
    }

    /**
     * AJAX endpoint to fetch full stock history, batches, serials, warehouse breakdown & movements timeline for a product
     */
    public function fetchProductHistory(Request $request, $productId)
    {
        try {
            $product = Product::with(['unit', 'category_relation'])->findOrFail($productId);

            // 1. Batches
            $batches = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('product_batches')) {
                try {
                    $batchesCollection = DB::table('product_batches as b')
                        ->leftJoin('warehouses as w', 'w.id', '=', 'b.warehouse_id')
                        ->where('b.product_id', $productId)
                        ->select(
                            'b.*',
                            DB::raw("COALESCE(w.warehouse_name, 'Main Stock') as warehouse_name")
                        )
                        ->orderBy('b.expiry_date', 'asc')
                        ->get();

                    $batchMap = [];
                    foreach ($batchesCollection as $b) {
                        $expDate = $b->expiry_date ?? null;
                        $mfgDate = $b->mfg_date ?? null;

                        // Fallback to sale_items if exp_date or mfg_date on batch is empty
                        if (empty($expDate) || empty($mfgDate)) {
                            $saleFallback = DB::table('sale_items')
                                ->where('product_id', $productId)
                                ->where(function($q) use ($b) {
                                    if (!empty($b->id)) $q->where('batch_id', $b->id);
                                    if (!empty($b->batch_no)) $q->orWhere('batch_no', $b->batch_no);
                                })
                                ->where(function($q) {
                                    $q->whereNotNull('exp_date')->orWhereNotNull('mfg_date');
                                })
                                ->select('exp_date', 'mfg_date')
                                ->first();

                            if ($saleFallback) {
                                if (empty($expDate) && !empty($saleFallback->exp_date)) {
                                    $expDate = $saleFallback->exp_date;
                                }
                                if (empty($mfgDate) && !empty($saleFallback->mfg_date)) {
                                    $mfgDate = $saleFallback->mfg_date;
                                }
                            }
                        }

                        $status = 'active';
                        if ($expDate) {
                            try {
                                $exp = \Carbon\Carbon::parse($expDate);
                                if ($exp->isPast()) {
                                    $status = 'expired';
                                } elseif ($exp->diffInDays(now()) <= 30) {
                                    $status = 'near_expiry';
                                }
                            } catch (\Exception $e) {}
                        }

                        $bKey = !empty($b->batch_no) ? $b->batch_no : ('BATCH-' . $b->id);
                        $batchMap[$bKey] = [
                            'id'             => $b->id ?? 0,
                            'batch_no'       => $b->batch_no ?? '-',
                            'variant_key'    => $b->variant_key ?? '-',
                            'warehouse_name' => $b->warehouse_name ?? 'Main Stock',
                            'mfg_date'       => !empty($mfgDate) ? date('d M Y', strtotime($mfgDate)) : '-',
                            'expiry_date'    => !empty($expDate) ? date('d M Y', strtotime($expDate)) : '-',
                            'qty'            => (float) ($b->qty ?? 0),
                            'cost_price'     => (float) ($b->cost_price ?? 0),
                            'remarks'        => $b->remarks ?? '-',
                            'status'         => $status,
                        ];
                    }

                    // Also check if sale_items has batch / expiry entries not in product_batches table
                    $saleBatches = DB::table('sale_items as si')
                        ->leftJoin('warehouses as w', 'w.id', '=', 'si.warehouse_id')
                        ->where('si.product_id', $productId)
                        ->where(function($q) {
                            $q->whereNotNull('si.batch_no')->orWhereNotNull('si.exp_date');
                        })
                        ->select(
                            'si.batch_no',
                            'si.mfg_date',
                            'si.exp_date',
                            'si.price',
                            DB::raw("COALESCE(w.warehouse_name, 'Main Stock') as warehouse_name"),
                            DB::raw("SUM(si.total_pieces) as qty")
                        )
                        ->groupBy('si.batch_no', 'si.mfg_date', 'si.exp_date', 'si.price', 'w.warehouse_name')
                        ->get();

                    foreach ($saleBatches as $sb) {
                        $bNo = !empty($sb->batch_no) ? $sb->batch_no : 'SALE-BATCH';
                        if (!isset($batchMap[$bNo])) {
                            $expDate = $sb->exp_date ?? null;
                            $mfgDate = $sb->mfg_date ?? null;
                            $status = 'active';
                            if ($expDate) {
                                try {
                                    $exp = \Carbon\Carbon::parse($expDate);
                                    if ($exp->isPast()) {
                                        $status = 'expired';
                                    } elseif ($exp->diffInDays(now()) <= 30) {
                                        $status = 'near_expiry';
                                    }
                                } catch (\Exception $e) {}
                            }
                            $batchMap[$bNo] = [
                                'id'             => 0,
                                'batch_no'       => $bNo,
                                'variant_key'    => '-',
                                'warehouse_name' => $sb->warehouse_name ?? 'Main Stock',
                                'mfg_date'       => !empty($mfgDate) ? date('d M Y', strtotime($mfgDate)) : '-',
                                'expiry_date'    => !empty($expDate) ? date('d M Y', strtotime($expDate)) : '-',
                                'qty'            => (float) ($sb->qty ?? 0),
                                'cost_price'     => (float) ($sb->price ?? 0),
                                'remarks'        => 'Sale Invoice Batch',
                                'status'         => $status,
                            ];
                        }
                    }

                    $batches = array_values($batchMap);

                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error fetching product batches: " . $e->getMessage());
                }
            }

            // 2. Serials
            $serials = [];
            if (\Illuminate\Support\Facades\Schema::hasTable('product_serials')) {
                try {
                    $serials = DB::table('product_serials as s')
                        ->leftJoin('warehouses as w', 'w.id', '=', 's.warehouse_id')
                        ->leftJoin('product_batches as b', 'b.id', '=', 's.batch_id')
                        ->where('s.product_id', $productId)
                        ->select(
                            's.*',
                            'b.batch_no',
                            'b.expiry_date',
                            'b.mfg_date',
                            DB::raw("COALESCE(w.warehouse_name, 'Main Stock') as warehouse_name")
                        )
                        ->orderBy('s.created_at', 'desc')
                        ->get()
                        ->map(function ($s) use ($productId) {
                            $expDate = $s->expiry_date ?? null;
                            if (empty($expDate) && !empty($s->batch_no)) {
                                $saleExp = DB::table('sale_items')
                                    ->where('product_id', $productId)
                                    ->where('batch_no', $s->batch_no)
                                    ->whereNotNull('exp_date')
                                    ->value('exp_date');
                                if ($saleExp) $expDate = $saleExp;
                            }

                            return [
                                'id'             => $s->id ?? 0,
                                'serial_number'  => $s->serial_number ?? '-',
                                'batch_no'       => $s->batch_no ?? '-',
                                'variant_key'    => $s->variant_key ?? '-',
                                'warehouse_name' => $s->warehouse_name ?? 'Main Stock',
                                'status'         => strtolower($s->status ?? 'available'),
                                'cost_price'     => (float) ($s->cost_price ?? 0),
                                'expiry_date'    => !empty($expDate) ? date('d M Y', strtotime($expDate)) : '-',
                                'remarks'        => $s->remarks ?? '-',
                                'created_at'     => !empty($s->created_at) ? date('d M Y h:i A', strtotime($s->created_at)) : '-',
                            ];
                        });
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error fetching product serials: " . $e->getMessage());
                }
            }

            // 3. Warehouse Stocks Breakdown
            $ppb = (float) ($product->pieces_per_box ?? 1);
            if ($ppb <= 0) $ppb = 1;
            $unitName = $product->unit->name ?? 'Pcs';
            $isCartonMode = (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || strtolower($unitName) === 'carton');
            $ctnLbl = ($product->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';

            $warehouseStocks = [];
            $totalStock = 0;
            if (\Illuminate\Support\Facades\Schema::hasTable('warehouse_stocks')) {
                try {
                    $warehouseStocks = DB::table('warehouse_stocks as ws')
                        ->leftJoin('warehouses as w', 'w.id', '=', 'ws.warehouse_id')
                        ->where('ws.product_id', $productId)
                        ->select(
                            'ws.*',
                            DB::raw("COALESCE(w.warehouse_name, 'Main Stock') as warehouse_name")
                        )
                        ->get()
                        ->map(function ($ws) use ($isCartonMode, $ppb, $ctnLbl) {
                            $totalPieces = (float) ($ws->total_pieces ?? $ws->quantity ?? 0);
                            $boxesQty   = (float) ($ws->boxes_quantity ?? $ws->quantity ?? 0);
                            if ($isCartonMode) {
                                $cartons = (int) floor($totalPieces / $ppb);
                                $loose   = (int) round($totalPieces - ($cartons * $ppb));
                                $formatted = ($loose > 0) ? "{$cartons} {$ctnLbl} + {$loose} Pcs" : "{$cartons} {$ctnLbl}";
                            } else {
                                $formatted = number_format($totalPieces, 2);
                            }

                            return [
                                'warehouse_name'  => $ws->warehouse_name ?? 'Main Stock',
                                'boxes_quantity'  => $boxesQty,
                                'total_pieces'    => $totalPieces,
                                'formatted_stock' => $formatted,
                                'remarks'         => $ws->remarks ?? '-',
                            ];
                        });

                    $totalStock = (float) DB::table('warehouse_stocks')
                        ->where('product_id', $productId)
                        ->selectRaw('COALESCE(SUM(COALESCE(total_pieces, quantity, 0)), 0) as total')
                        ->value('total');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("Error fetching warehouse stocks: " . $e->getMessage());
                }
            }

            // 4. Stock Movement Timeline
            $movements = [];
            try {
                $movements = DB::table('stock_movements')
                    ->where('product_id', $productId)
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($m) {
                        $typeBadge = 'info';
                        $typeLabel = strtoupper($m->type ?? '');
                        if (($m->type ?? '') === 'in' || ($m->type ?? '') === 'assembly_in') {
                            $typeBadge = 'success';
                            $typeLabel = 'INWARD (+)';
                        } elseif (($m->type ?? '') === 'out' || ($m->type ?? '') === 'assembly_out') {
                            $typeBadge = 'danger';
                            $typeLabel = 'OUTWARD (-)';
                        } elseif (($m->type ?? '') === 'adjustment') {
                            $typeBadge = 'warning';
                            $typeLabel = 'ADJUSTMENT (' . (($m->qty ?? 0) >= 0 ? '+' : '') . ')';
                        } elseif (($m->type ?? '') === 'sale_return') {
                            $typeBadge = 'info';
                            $typeLabel = 'SALE RETURN (+)';
                        }

                        return [
                            'id'          => $m->id ?? 0,
                            'date'        => !empty($m->created_at) ? date('d M Y h:i A', strtotime($m->created_at)) : '-',
                            'type'        => $typeLabel,
                            'type_badge'  => $typeBadge,
                            'qty'         => (float) ($m->qty ?? 0),
                            'ref_type'    => $m->ref_type ?? 'GENERAL',
                            'note'        => $m->note ?? 'N/A',
                        ];
                    });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Error fetching stock movements: " . $e->getMessage());
            }

            // Calculate available serials count and non-serial stock
            $availableSerialsCount = collect($serials)->where('status', 'available')->count();
            $nonSerialStock = max(0, (float)$totalStock - $availableSerialsCount);

            return response()->json([
                'success'                 => true,
                'product_name'            => $product->item_name,
                'item_code'               => $product->item_code,
                'category_name'           => $product->category_relation->name ?? 'Standard',
                'unit_name'               => $unitName,
                'total_stock'             => (float) $totalStock,
                'available_serials_count' => $availableSerialsCount,
                'non_serial_stock'        => (float) $nonSerialStock,
                'batches'                 => $batches,
                'serials'                 => $serials,
                'warehouse_stocks'        => $warehouseStocks,
                'history'                 => $movements
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("fetchProductHistory global exception: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'history' => []
            ], 500);
        }
    }

    public function profit_loss_report(Request $request)
    {
        $products = Product::orderBy('item_name')->get();
        $categories = Category::orderBy('name')->get();
        $custQuery = DB::table('customers')->orderBy('customer_name');
        if ($request->filled('province') && $request->province !== 'all') {
            $custQuery->where('province', $request->province);
        }
        if ($request->filled('city') && $request->city !== 'all') {
            $custQuery->where('city', $request->city);
        }
        if ($request->filled('area') && $request->area !== 'all') {
            $custQuery->where('area', $request->area);
        }
        $customers = $custQuery->get();

        return view('admin_panel.reporting.profit_loss_report', compact('products', 'categories', 'customers'));
    }

    public function fetchProfitLoss(Request $request)
    {
        $start = $request->start_date ?: now()->startOfDay()->toDateTimeString();
        $end = $request->end_date ?: now()->endOfDay()->toDateTimeString();

        if (strlen($start) === 10) {
            $start .= ' 00:00:00';
        } elseif (strlen($start) === 16) {
            $start .= ':00';
        }

        if (strlen($end) === 10) {
            $end .= ' 23:59:59';
        } elseif (strlen($end) === 16) {
            $end .= ':59';
        }

        $productId = $request->product_id;
        $categoryId = $request->category_id;
        $customerId = $request->customer_id;

        $productsQuery = Product::query();
        if ($productId && $productId !== 'all') {
            $productsQuery->where('id', $productId);
        }
        if ($categoryId && $categoryId !== 'all') {
            $productsQuery->where('category_id', $categoryId);
        }
        
        $products = $productsQuery->get();
        $productStats = [];
        $totalGrossProfit = 0;
        $avgPriceMap = [];

        foreach ($products as $product) {
            // Determine default purchase price per piece
            $productPurchPrice = 0;
            if ($product->size_mode === 'by_size') {
                $m2PerPiece = (float) ($product->pieces_per_m2 ?? 0);
                $purchPerM2 = (float) ($product->purchase_price_per_m2 ?? 0);
                $productPurchPrice = $m2PerPiece * $purchPerM2;
            } else {
                $productPurchPrice = (float) ($product->purchase_price_per_piece ?? 0);
            }

            // Calculate overall product average price (for fallback)
            $initial = (float) DB::table('stock_movements')
                ->where('product_id', $product->id)
                ->whereIn('ref_type', ['INIT', 'OPENING_STOCK'])
                ->sum('qty');

            [$purchased, $purchaseAmount] = $this->getPurchasedQtyAndNetAmount($product->id);

            $initialAmount = $initial * $productPurchPrice;
            $totalQtyIn = $initial + $purchased;
            $totalAmountIn = $initialAmount + $purchaseAmount;
            $averagePrice = $totalQtyIn > 0 ? ($totalAmountIn / $totalQtyIn) : $productPurchPrice;
            $avgPriceMap[$product->id] = $averagePrice;

            // Check if product has variants
            $parsedVariants = [];
            if ($product->color) {
                try {
                    $decoded = json_decode($product->color, true);
                    if (is_array($decoded) && count($decoded) > 0 && isset($decoded[0]['name'])) {
                        $parsedVariants = $decoded;
                    }
                } catch (\Exception $e) {}
            }

            if (count($parsedVariants) > 0) {
                // Fetch all sales and returns for this product to distribute
                $saleQuery = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sale_items.product_id', $product->id);
                
                if ($start && $end) {
                    $saleQuery->whereBetween('sales.created_at', [$start, $end]);
                }
                if ($customerId && $customerId !== 'all') {
                    $saleQuery->where('sales.customer_id', $customerId);
                }

                $salesList = $saleQuery->select(
                    'sale_items.total_pieces',
                    'sale_items.qty',
                    'sale_items.total',
                    'sale_items.color',
                    'sales.total_extradiscount',
                    'sales.total_bill_amount'
                )->get();

                // Fetch confirmed web sales
                $webSalesQuery = DB::table('ecommerce_order_items as eoi')
                    ->join('ecommerce_orders as eo', 'eo.id', '=', 'eoi.ecommerce_order_id')
                    ->where('eoi.product_id', $product->id)
                    ->where('eo.is_stock_deducted', 1);

                if ($start && $end) {
                    $webSalesQuery->whereBetween('eo.created_at', [$start, $end]);
                }
                if ($customerId && $customerId !== 'all') {
                    $webSalesQuery->whereRaw('1 = 0');
                }

                $webSalesList = $webSalesQuery->select(
                    'eoi.quantity as total_pieces',
                    'eoi.quantity as qty',
                    'eoi.total',
                    'eoi.color',
                    'eoi.size',
                    'eo.discount as total_extradiscount',
                    'eo.subtotal as total_bill_amount'
                )->get();

                $salesListArray = $salesList->toArray();
                foreach ($webSalesList as $wItem) {
                    $salesListArray[] = (object) [
                        'total_pieces' => $wItem->total_pieces,
                        'qty' => $wItem->qty,
                        'total' => $wItem->total,
                        'total_extradiscount' => $wItem->total_extradiscount,
                        'total_bill_amount' => $wItem->total_bill_amount,
                        'color' => json_encode([
                            'color' => $wItem->color ?: '-',
                            'size' => $wItem->size ?: '-'
                        ])
                    ];
                }
                $salesList = collect($salesListArray);

                $returnQuery = DB::table('sale_return_items as sri')
                    ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->where('sri.product_id', $product->id);
                
                if ($start && $end) {
                    $returnQuery->whereBetween('sr.created_at', [$start, $end]);
                }
                $returnsList = $returnQuery->select('sri.qty', 'sri.color', 'sr.sale_id', 'sri.line_total')
                    ->get();

                $saleIds = $returnsList->pluck('sale_id')->unique()->toArray();
                $saleItemsMap = [];
                if (!empty($saleIds)) {
                    $siList = DB::table('sale_items')
                        ->whereIn('sale_id', $saleIds)
                        ->where('product_id', $product->id)
                        ->select('sale_id', 'color')
                        ->get();
                    foreach ($siList as $si) {
                        $saleItemsMap[$si->sale_id][] = $si->color;
                    }
                }

                foreach ($parsedVariants as $v) {
                    $vName = $v['name'] ?? $product->item_name;
                    $vSize = $v['size'] ?? '-';
                    $vColor = $v['color'] ?? '-';
                    $vUnit = $v['unit'] ?? ($product->unit->name ?? 'Pcs');
                    $vConv = (float)($v['conv_factor'] ?? 1);
                    $isBase = (int)($v['is_base_variant'] ?? 0);
                    $ppb = (float)($product->pieces_per_box ?? 1);
                    if ($ppb <= 0) $ppb = 1;

                    // Determine Unit Badge
                    $unitBadge = '';
                    if (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) {
                        $cartonPpb = $vConv > 1 ? $vConv : $ppb;
                        $cartonLbl = ($product->size_mode === 'by_bandal') ? 'Bundal' : 'Carton';
                        $unitBadge = "{$cartonLbl} ({$cartonPpb} pcs/" . strtolower($cartonLbl) . ")";
                    } elseif ($product->size_mode === 'by_kg') {
                        if ($isBase == 1 || $vConv == 1) {
                            $unitBadge = "Kg (Base)";
                        } else {
                            $gm = (int) round($vConv * 1000);
                            $unitBadge = "Kg / Pcs ({$gm}g)";
                        }
                    } elseif ($product->size_mode === 'by_gm') {
                        $unitBadge = "Gm";
                    } elseif ($product->size_mode === 'by_meter') {
                        $unitBadge = "Meter";
                    } elseif ($product->size_mode === 'by_feet') {
                        $unitBadge = "Ft";
                    } elseif ($product->size_mode === 'by_size') {
                        $unitBadge = "M²";
                    } else {
                        $unitBadge = "Pcs";
                    }

                    $soldQty = 0;
                    $soldQtyPieces = 0;
                    $soldAmount = 0;
                    foreach ($salesList as $sItem) {
                        if ($this->matchSaleItemToVariant($sItem, $v)) {
                            $soldQty += (float) $sItem->qty;
                            $soldQtyPieces += (float) $sItem->total_pieces;
                            
                            $itemNet = (float) $sItem->total;
                            if ($sItem->total_bill_amount > 0 && $sItem->total_extradiscount > 0) {
                                $proportion = $itemNet / (float) $sItem->total_bill_amount;
                                $itemNet -= ($sItem->total_extradiscount * $proportion);
                            }
                            $soldAmount += $itemNet;
                        }
                    }

                    $returnedQtyPieces = 0;
                    $returnedAmount = 0;
                    foreach ($returnsList as $rItem) {
                        $rColor = $rItem->color;
                        if (empty($rColor)) {
                            $saleColors = $saleItemsMap[$rItem->sale_id] ?? [];
                            $rColor = !empty($saleColors) ? $saleColors[0] : '';
                        }
                        $rItemCopy = (object)[
                            'qty' => $rItem->qty,
                            'color' => $rColor
                        ];
                        if ($this->matchSaleItemToVariant($rItemCopy, $v)) {
                            $returnedQtyPieces += (float) $rItem->qty;
                            $returnedAmount += (float) $rItem->line_total;
                        }
                    }

                    $vAveragePrice = (float) ($v['purch_price'] ?? $productPurchPrice);
                    $netSoldAmount = $soldAmount - $returnedAmount;
                    $netQtyPieces = $soldQtyPieces - $returnedQtyPieces;
                    $costOfGoodsSold = $netQtyPieces * $vAveragePrice;
                    $grossProfit = $netSoldAmount - $costOfGoodsSold;

                    if ($soldQtyPieces > 0 || $returnedQtyPieces > 0) {
                        // Format Sold Qty Display
                        if (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) {
                            $cartonPpb = $vConv > 1 ? $vConv : $ppb;
                            $ctnLbl = ($product->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';
                            $cartonFull = ($product->size_mode === 'by_bandal') ? 'Bundal' : 'Carton';
                            $cartonFulls = ($product->size_mode === 'by_bandal') ? 'Bundals' : 'Cartons';
                            $soldBoxes = (int) floor($soldQtyPieces / $cartonPpb);
                            $soldLoose = (int) round($soldQtyPieces - ($soldBoxes * $cartonPpb));
                            if ($soldBoxes > 0 && $soldLoose > 0) {
                                $soldQtyDisplay = "{$soldBoxes} {$ctnLbl} . {$soldLoose} Pcs";
                            } elseif ($soldBoxes > 0) {
                                $soldQtyDisplay = ($soldBoxes == 1) ? "1 {$cartonFull}" : "{$soldBoxes} {$cartonFulls}";
                            } else {
                                $soldQtyDisplay = "{$soldQtyPieces} Pcs";
                            }

                            $retBoxes = (int) floor($returnedQtyPieces / $cartonPpb);
                            $retLoose = (int) round($returnedQtyPieces - ($retBoxes * $cartonPpb));
                            if ($retBoxes > 0 && $retLoose > 0) {
                                $retQtyDisplay = "{$retBoxes} {$ctnLbl} . {$retLoose} Pcs";
                            } elseif ($retBoxes > 0) {
                                $retQtyDisplay = ($retBoxes == 1) ? "1 {$cartonFull}" : "{$retBoxes} {$cartonFulls}";
                            } else {
                                $retQtyDisplay = ($returnedQtyPieces > 0) ? "{$returnedQtyPieces} Pcs" : "0";
                            }
                        } elseif ($product->size_mode === 'by_kg') {
                            if ($isBase == 1 || $vConv == 1) {
                                $soldQtyDisplay = number_format($soldQty, 2) . " Kg";
                                $retQtyDisplay = ($returnedQtyPieces > 0) ? (number_format($returnedQtyPieces, 2) . " Kg") : "0";
                            } else {
                                $soldQtyDisplay = ((int)$soldQtyPieces) . " Pcs";
                                $retQtyDisplay = ($returnedQtyPieces > 0) ? (((int)$returnedQtyPieces) . " Pcs") : "0";
                            }
                        } elseif (in_array($product->size_mode, ['by_meter', 'by_feet', 'by_gm'])) {
                            $uom = match($product->size_mode) {
                                'by_meter' => 'Mtr',
                                'by_feet' => 'Ft',
                                'by_gm' => 'Gm',
                                default => 'Pcs'
                            };
                            $soldQtyDisplay = number_format($soldQty, 2) . " {$uom}";
                            $retQtyDisplay = ($returnedQtyPieces > 0) ? (number_format($returnedQtyPieces, 2) . " {$uom}") : "0";
                        } else {
                            $soldQtyDisplay = ((int)$soldQtyPieces) . " Pcs";
                            $retQtyDisplay = ($returnedQtyPieces > 0) ? (((int)$returnedQtyPieces) . " Pcs") : "0";
                        }

                        // Format Item Display Name
                        $displayName = $vName;
                        $extras = [];
                        if ($vSize !== '-' && !empty($vSize)) $extras[] = $vSize;
                        if ($vColor !== '-' && !empty($vColor)) $extras[] = $vColor;
                        if ($product->size_mode === 'by_kg' && $isBase != 1 && $vConv > 0 && $vConv != 1) {
                            $gm = (int) round($vConv * 1000);
                            $extras[] = "{$gm}g";
                        }
                        if (!empty($extras)) {
                            $displayName .= ' (' . implode(' | ', $extras) . ')';
                        }

                        $productStats[] = [
                            'item_code' => $product->item_code,
                            'item_name' => $displayName,
                            'unit_badge' => $unitBadge,
                            'sold_qty' => $soldQtyDisplay,
                            'returned_qty' => $retQtyDisplay,
                            'revenue' => $netSoldAmount,
                            'avg_cost' => $vAveragePrice,
                            'cogs' => $costOfGoodsSold,
                            'profit' => $grossProfit
                        ];
                        $totalGrossProfit += $grossProfit;
                    }
                }
            } else {
                // Product has no variants: original logic
                $saleQuery = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sale_items.product_id', $product->id);
                
                if ($start && $end) {
                    $saleQuery->whereBetween('sales.created_at', [$start, $end]);
                }

                if ($customerId && $customerId !== 'all') {
                    $saleQuery->where('sales.customer_id', $customerId);
                }

                $salesList = $saleQuery->select(
                    'sale_items.total_pieces',
                    'sale_items.qty',
                    'sale_items.total',
                    'sales.total_extradiscount',
                    'sales.total_bill_amount'
                )->get();

                $soldQty = 0;
                $soldQtyPieces = 0;
                $soldAmount = 0;
                foreach ($salesList as $sItem) {
                    $soldQty += (float) $sItem->qty;
                    $soldQtyPieces += (float) $sItem->total_pieces;
                    
                    $itemNet = (float) $sItem->total;
                    if ($sItem->total_bill_amount > 0 && $sItem->total_extradiscount > 0) {
                        $proportion = $itemNet / (float) $sItem->total_bill_amount;
                        $itemNet -= ($sItem->total_extradiscount * $proportion);
                    }
                    $soldAmount += $itemNet;
                }
                
                $returnQuery = DB::table('sale_return_items as sri')
                    ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->where('sri.product_id', $product->id);
                
                if ($start && $end) {
                    $returnQuery->whereBetween('sr.created_at', [$start, $end]);
                }
                $returnsList = $returnQuery->select('sri.qty', 'sri.line_total')->get();

                $returnedQtyPieces = 0;
                $returnedAmount = 0;
                foreach ($returnsList as $rItem) {
                    $returnedQtyPieces += (float) $rItem->qty;
                    $returnedAmount += (float) $rItem->line_total;
                }

                $netSoldAmount = $soldAmount - $returnedAmount;
                $netQtyPieces = $soldQtyPieces - $returnedQtyPieces;
                $costOfGoodsSold = $netQtyPieces * $averagePrice;
                $grossProfit = $netSoldAmount - $costOfGoodsSold;

                if ($soldQtyPieces > 0 || $returnedQtyPieces > 0) {
                    $ppb = (float)($product->pieces_per_box ?? 1);
                    if ($ppb <= 0) $ppb = 1;

                    // Determine Unit Badge
                    $unitBadge = '';
                    if (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) {
                        $cartonLbl = ($product->size_mode === 'by_bandal') ? 'Bundal' : 'Carton';
                        $unitBadge = "{$cartonLbl} ({$ppb} pcs/" . strtolower($cartonLbl) . ")";
                    } elseif ($product->size_mode === 'by_kg') {
                        $unitBadge = "Kg";
                    } elseif ($product->size_mode === 'by_gm') {
                        $unitBadge = "Gm";
                    } elseif ($product->size_mode === 'by_meter') {
                        $unitBadge = "Meter";
                    } elseif ($product->size_mode === 'by_feet') {
                        $unitBadge = "Ft";
                    } elseif ($product->size_mode === 'by_size') {
                        $unitBadge = "M²";
                    } else {
                        $unitBadge = "Pcs";
                    }

                    // Format Sold / Returned Qty
                    if (in_array($product->size_mode, ['by_cartons', 'by_bandal'])) {
                        $ctnLbl = ($product->size_mode === 'by_bandal') ? 'Bndl' : 'Ctn';
                        $cartonFull = ($product->size_mode === 'by_bandal') ? 'Bundal' : 'Carton';
                        $cartonFulls = ($product->size_mode === 'by_bandal') ? 'Bundals' : 'Cartons';
                        $soldBoxes = (int) floor($soldQtyPieces / $ppb);
                        $soldLoose = (int) round($soldQtyPieces - ($soldBoxes * $ppb));
                        if ($soldBoxes > 0 && $soldLoose > 0) {
                            $soldQtyDisplay = "{$soldBoxes} {$ctnLbl} . {$soldLoose} Pcs";
                        } elseif ($soldBoxes > 0) {
                            $soldQtyDisplay = ($soldBoxes == 1) ? "1 {$cartonFull}" : "{$soldBoxes} {$cartonFulls}";
                        } else {
                            $soldQtyDisplay = "{$soldQtyPieces} Pcs";
                        }

                        $retBoxes = (int) floor($returnedQtyPieces / $ppb);
                        $retLoose = (int) round($returnedQtyPieces - ($retBoxes * $ppb));
                        if ($retBoxes > 0 && $retLoose > 0) {
                            $retQtyDisplay = "{$retBoxes} {$ctnLbl} . {$retLoose} Pcs";
                        } elseif ($retBoxes > 0) {
                            $retQtyDisplay = ($retBoxes == 1) ? "1 {$cartonFull}" : "{$retBoxes} {$cartonFulls}";
                        } else {
                            $retQtyDisplay = ($returnedQtyPieces > 0) ? "{$returnedQtyPieces} Pcs" : "0";
                        }
                    } elseif ($product->size_mode === 'by_kg') {
                        $soldQtyDisplay = number_format($soldQty, 2) . " Kg";
                        $retQtyDisplay = ($returnedQtyPieces > 0) ? (number_format($returnedQtyPieces, 2) . " Kg") : "0";
                    } elseif (in_array($product->size_mode, ['by_meter', 'by_feet', 'by_gm'])) {
                        $uom = match($product->size_mode) {
                            'by_meter' => 'Mtr',
                            'by_feet' => 'Ft',
                            'by_gm' => 'Gm',
                            default => 'Pcs'
                        };
                        $soldQtyDisplay = number_format($soldQty, 2) . " {$uom}";
                        $retQtyDisplay = ($returnedQtyPieces > 0) ? (number_format($returnedQtyPieces, 2) . " {$uom}") : "0";
                    } else {
                        $soldQtyDisplay = ((int)$soldQtyPieces) . " Pcs";
                        $retQtyDisplay = ($returnedQtyPieces > 0) ? (((int)$returnedQtyPieces) . " Pcs") : "0";
                    }

                    $productStats[] = [
                        'item_code' => $product->item_code,
                        'item_name' => $product->item_name,
                        'unit_badge' => $unitBadge,
                        'sold_qty' => $soldQtyDisplay,
                        'returned_qty' => $retQtyDisplay,
                        'revenue' => $netSoldAmount,
                        'avg_cost' => $averagePrice,
                        'cogs' => $costOfGoodsSold,
                        'profit' => $grossProfit
                    ];
                    $totalGrossProfit += $grossProfit;
                }
            }
        }

        // 2b. Manual Products P&L
        $manualSaleQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sale_items.is_manual', 1);

        if ($start && $end) {
            $manualSaleQuery->whereBetween('sales.created_at', [$start, $end]);
        }
        if ($customerId && $customerId !== 'all') {
            $manualSaleQuery->where('sales.customer_id', $customerId);
        }

        $manualSalesList = $manualSaleQuery->select(
            'sale_items.product_name',
            'sale_items.total_pieces',
            'sale_items.qty',
            'sale_items.total',
            'sale_items.purchase_price',
            'sales.total_extradiscount',
            'sales.total_bill_amount'
        )->get();

        if ($manualSalesList->isNotEmpty()) {
            $manualGrouped = $manualSalesList->groupBy('product_name');

            foreach ($manualGrouped as $mName => $mItems) {
                $soldQty = 0;
                $soldQtyPieces = 0;
                $soldAmount = 0;
                $cogs = 0;

                foreach ($mItems as $sItem) {
                    $soldQty += (float) $sItem->qty;
                    $soldQtyPieces += (float) $sItem->total_pieces;
                    $cogs += ((float) $sItem->total_pieces) * ((float) $sItem->purchase_price);

                    $itemNet = (float) $sItem->total;
                    if ($sItem->total_bill_amount > 0 && $sItem->total_extradiscount > 0) {
                        $proportion = $itemNet / (float) $sItem->total_bill_amount;
                        $itemNet -= ($sItem->total_extradiscount * $proportion);
                    }
                    $soldAmount += $itemNet;
                }

                $manualRetQuery = DB::table('sale_return_items')
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->where('sale_return_items.is_manual', 1)
                    ->where('sale_return_items.product_name', $mName);

                if ($start && $end) {
                    $manualRetQuery->whereBetween('sale_returns.created_at', [$start, $end]);
                }
                if ($customerId && $customerId !== 'all') {
                    $manualRetQuery->where('sale_returns.customer_id', $customerId);
                }

                $manualReturns = $manualRetQuery->select(
                    'sale_return_items.qty',
                    'sale_return_items.line_total',
                    'sale_return_items.purchase_price'
                )->get();

                $returnedQtyPieces = 0;
                foreach ($manualReturns as $mr) {
                    $returnedQtyPieces += (float) $mr->qty;
                    $soldAmount -= (float) $mr->line_total;
                    $cogs -= ((float) $mr->qty) * ((float) $mr->purchase_price);
                }

                $grossProfit = $soldAmount - $cogs;
                $averageCost = $soldQtyPieces > 0 ? ($cogs / $soldQtyPieces) : 0;

                if ($soldQty > 0 || $returnedQtyPieces > 0) {
                     $productStats[] = [
                        'item_code' => 'MANUAL',
                        'item_name' => $mName ? $mName . ' (Manual)' : 'Manual Product',
                        'sold_qty' => $soldQty,
                        'returned_qty' => $returnedQtyPieces,
                        'revenue' => $soldAmount,
                        'avg_cost' => $averageCost,
                        'cogs' => $cogs,
                        'profit' => $grossProfit
                    ];
                    $totalGrossProfit += $grossProfit;
                }
            }
        }

        // 3. Calculate Expenses
        $expenseQueryV1 = DB::table('expense_vouchers');
        $expenseQueryV2 = DB::table('voucher_masters')->where('voucher_type', 'expense');

        if ($start && $end) {
            $expenseQueryV1->whereBetween('entry_date', [$start, $end]);
            $expenseQueryV2->whereBetween('date', [$start, $end]);
        }

        $totalExpenses = $expenseQueryV1->sum('total_amount') + $expenseQueryV2->sum('total_amount');

        // 4. Top 10 Customers by Profit
        $allCustomers = DB::table('customers')->get();
        $customerProfits = [];

        $balanceService = app(\App\Services\BalanceService::class);
        foreach ($allCustomers as $customer) {
            $custSaleQuery = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sales.customer_id', $customer->id);

            if ($start && $end) {
                $custSaleQuery->whereBetween('sales.created_at', [$start, $end]);
            }

            $custSaleItems = $custSaleQuery->select(
                'sale_items.product_id', 
                'sale_items.is_manual',
                'sale_items.purchase_price',
                'sale_items.color',
                'sale_items.total_pieces',
                'sale_items.total',
                'sales.total_extradiscount',
                'sales.total_bill_amount'
            )->get();

            $custRevenue = 0;
            $custCogs = 0;

            foreach ($custSaleItems as $item) {
                $avgPrice = 0;
                
                if ($item->is_manual) {
                    $avgPrice = (float) $item->purchase_price;
                } else {
                    $product = $products->firstWhere('id', $item->product_id);
                    if ($product) {
                        $itemColor = $item->color;
                        $itemVariant = null;
                        if (!empty($itemColor)) {
                            $b64Decoded = base64_decode($itemColor, true);
                            if ($b64Decoded !== false) {
                                $itemVariant = json_decode($b64Decoded, true);
                            }
                            if (empty($itemVariant)) {
                                $itemVariant = json_decode($itemColor, true);
                            }
                        }

                        if (is_array($itemVariant) && isset($itemVariant['color'])) {
                            $vColor = strtolower(trim($itemVariant['color'] ?? '-'));
                            $vSize = strtolower(trim($itemVariant['size'] ?? '-'));
                            if ($vColor === '') $vColor = '-';
                            if ($vSize === '') $vSize = '-';

                            $decoded = json_decode($product->color, true);
                            if (is_array($decoded)) {
                                foreach ($decoded as $v) {
                                    $vC = strtolower(trim($v['color'] ?? '-'));
                                    $vS = strtolower(trim($v['size'] ?? '-'));
                                    if ($vC === '') $vC = '-';
                                    if ($vS === '') $vS = '-';

                                    if ($vC === $vColor && $vS === $vSize) {
                                        $avgPrice = (float) ($v['purch_price'] ?? 0);
                                        break;
                                    }
                                }
                            }
                        }

                        if ($avgPrice <= 0) {
                            $avgPrice = $avgPriceMap[$item->product_id] ?? 0;
                        }
                    }
                }

                $itemNet = (float) $item->total;
                if ($item->total_bill_amount > 0 && $item->total_extradiscount > 0) {
                    $proportion = $itemNet / (float) $item->total_bill_amount;
                    $itemNet -= ($item->total_extradiscount * $proportion);
                }

                $custRevenue += $itemNet;
                $custCogs += (float) ($item->total_pieces ?? 0) * $avgPrice;
            }

            $custProfit = $custRevenue - $custCogs;

            if ($custRevenue > 0) {
                $customerProfits[] = [
                    'id' => $customer->id,
                    'name' => $customer->customer_name,
                    'balance' => $balanceService->getCustomerBalance($customer->id),
                    'revenue' => round($custRevenue, 2),
                    'cogs' => round($custCogs, 2),
                    'profit' => round($custProfit, 2),
                ];
            }
        }

        // Sort by profit descending and take top 10
        usort($customerProfits, function($a, $b) {
            return $b['profit'] <=> $a['profit'];
        });
        $topCustomers = array_slice($customerProfits, 0, 10);

        return response()->json([
            'products' => $productStats,
            'total_gross_profit' => round($totalGrossProfit, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($totalGrossProfit - $totalExpenses, 2),
            'top_customers' => $topCustomers
        ]);
    }

    public function purchase_report()
    {
        $products = \App\Models\Product::orderBy('item_name')->get();
        $vendors = \App\Models\Vendor::orderBy('name')->get();
        $minDate = \App\Models\Purchase::min('purchase_date');
        $defaultStartDate = $minDate ? date('Y-m-d', strtotime($minDate)) : date('Y-01-01');
        return view('admin_panel.reporting.purchase_report', compact('products', 'vendors', 'defaultStartDate'));
    }

    public function fetchPurchaseReport(Request $request)
    {
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $productId = $request->product_id;
        $vendorId = $request->vendor_id;

        $query = DB::table('purchases')
            ->join('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->leftJoin('products', 'purchase_items.product_id', '=', 'products.id')
            ->leftJoin('vendors', 'purchases.vendor_id', '=', 'vendors.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->select(
                'purchases.purchase_date',
                'purchases.invoice_no',
                DB::raw('COALESCE(vendors.name, "Unknown Vendor") as vendor_name'),
                'products.item_code',
                'products.item_name',
                'purchase_items.qty',
                DB::raw('COALESCE(units.name, purchase_items.unit, "-") as unit'),
                'purchase_items.price',
                'purchase_items.item_discount',
                'purchase_items.line_total',
                'purchases.subtotal',
                'purchases.discount',
                'purchases.extra_cost',
                'purchases.net_amount',
                'purchases.paid_amount',
                'purchases.due_amount'
            );

        if ($startDate && $endDate) {
            $query->whereDate('purchases.purchase_date', '>=', $startDate)
                  ->whereDate('purchases.purchase_date', '<=', $endDate);
        } elseif ($startDate) {
            $query->whereDate('purchases.purchase_date', '>=', $startDate);
        } elseif ($endDate) {
            $query->whereDate('purchases.purchase_date', '<=', $endDate);
        }
        if ($productId && $productId !== 'all') {
            $query->where('purchase_items.product_id', $productId);
        }
        if ($vendorId && $vendorId !== 'all') {
            $query->where('purchases.vendor_id', $vendorId);
        }

        $results = $query->orderBy('purchases.purchase_date', 'asc')->get();

        // Attach returns to each row
        $rows = $results->map(function ($row) {
            $returns = DB::table('purchase_return_items')
                ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                ->join('products', 'products.id', '=', 'purchase_return_items.product_id')
                ->where('purchase_returns.purchase_id', DB::table('purchases')->where('invoice_no', $row->invoice_no)->value('id'))
                ->where('purchase_return_items.product_id', DB::table('products')->where('item_code', $row->item_code)->value('id'))
                ->select('products.item_name', 'purchase_return_items.qty', 'purchase_return_items.line_total')
                ->get();

            $row->returns = $returns;
            return $row;
        });

        // Add manual/outsourced purchases
        $manualQuery = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->leftJoin('vendors', 'sale_items.vendor_id', '=', 'vendors.id')
            ->where('sale_items.is_manual', 1)
            ->select(
                'sales.created_at as purchase_date',
                'sales.invoice_no',
                DB::raw('COALESCE(vendors.name, "Unknown Vendor") as vendor_name'),
                DB::raw('"MANUAL" as item_code'),
                DB::raw('CONCAT(sale_items.product_name, " (Outsourced)") as item_name'),
                'sale_items.qty',
                DB::raw('"pc" as unit'),
                'sale_items.purchase_price as price',
                DB::raw('0 as item_discount'),
                DB::raw('(sale_items.qty * sale_items.purchase_price) as line_total'),
                DB::raw('(sale_items.qty * sale_items.purchase_price) as subtotal'),
                DB::raw('0 as discount'),
                DB::raw('0 as extra_cost'),
                DB::raw('(sale_items.qty * sale_items.purchase_price) as net_amount'),
                DB::raw('(sale_items.qty * sale_items.purchase_price) as paid_amount'), // Assumed fully paid or handled in ledger
                DB::raw('0 as due_amount')
            );

        if ($startDate && $endDate) {
            $manualQuery->whereDate('sales.created_at', '>=', $startDate)
                        ->whereDate('sales.created_at', '<=', $endDate);
        } elseif ($startDate) {
            $manualQuery->whereDate('sales.created_at', '>=', $startDate);
        } elseif ($endDate) {
            $manualQuery->whereDate('sales.created_at', '<=', $endDate);
        }
        if ($vendorId && $vendorId !== 'all') {
            $manualQuery->where('sale_items.vendor_id', $vendorId);
        }

        if (!$productId || $productId === 'all') {
            $manualPurchases = $manualQuery->get();
            $manualPurchases = $manualPurchases->map(function ($row) {
                // Check if any returns exist for this manual product in this sale
                $returns = DB::table('sale_return_items')
                    ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
                    ->where('sale_returns.sale_id', DB::table('sales')->where('invoice_no', $row->invoice_no)->value('id'))
                    ->where('sale_return_items.is_manual', 1)
                    ->where('sale_return_items.product_name', str_replace(' (Outsourced)', '', $row->item_name))
                    ->select(
                        DB::raw('CONCAT(sale_return_items.product_name, " (Outsourced)") as item_name'),
                        'sale_return_items.qty',
                        DB::raw('(sale_return_items.qty * sale_return_items.purchase_price) as line_total')
                    )
                    ->get();
                $row->returns = $returns;
                return $row;
            });
            
            $rows = $rows->concat($manualPurchases);
        }

        // Sort by date after merging
        $rows = $rows->sortBy('purchase_date')->values();

        return response()->json([
            'data' => $rows
        ]);
    }

    public function sale_report()
    {
        $customers = \App\Models\Customer::orderBy('customer_name')->get();
        $salesOfficers = \App\Models\SalesOfficer::orderBy('name')->get();
        $minDate = \App\Models\Sale::min('created_at');
        $defaultStartDate = $minDate ? date('Y-m-d', strtotime($minDate)) : date('Y-01-01');
        return view('admin_panel.reporting.sale_report', compact('customers', 'salesOfficers', 'defaultStartDate'));
    }

    public function fetchsaleReport(Request $request)
    {
        if ($request->ajax()) {
            $start = $request->start_date;
            $end = $request->end_date;
            $customerId = $request->customer_id;
            $salesOfficerId = $request->sales_officer_id;

            // Use Eloquent to handle relations and new table structure
            $query = \App\Models\Sale::with(['customer_relation.salesOfficer', 'salesOfficer', 'items.product', 'returns']);

            if ($start && $end) {
                $query->whereDate('created_at', '>=', $start)
                      ->whereDate('created_at', '<=', $end);
            } elseif ($start) {
                $query->whereDate('created_at', '>=', $start);
            } elseif ($end) {
                $query->whereDate('created_at', '<=', $end);
            }

            if ($customerId && $customerId !== 'all') {
                $query->where('customer_id', $customerId);
            }

            $province = $request->province;
            $city = $request->city;
            $area = $request->area;

            if ($province && $province !== 'all') {
                $query->whereHas('customer_relation', function($q) use ($province) {
                    $q->where('province', $province);
                });
            }

            if ($city && $city !== 'all') {
                $query->whereHas('customer_relation', function($q) use ($city) {
                    $q->where('city', $city);
                });
            }

            if ($area && $area !== 'all') {
                $query->whereHas('customer_relation', function($q) use ($area) {
                    $q->where('area', $area);
                });
            }

            if ($salesOfficerId && $salesOfficerId !== 'all') {
                $query->where(function($q) use ($salesOfficerId) {
                    $q->where('sales_officer_id', $salesOfficerId)
                      ->orWhere(function($sub) use ($salesOfficerId) {
                          $sub->whereNull('sales_officer_id')
                              ->whereHas('customer_relation', function($custQ) use ($salesOfficerId) {
                                  $custQ->where('sales_officer_id', $salesOfficerId);
                              });
                      });
                });
            }

            $sales = $query->orderBy('created_at', 'desc')->get();

            // Transform to match the structure expected by the frontend (CSV strings)
            $transformed = $sales->map(function ($sale) {
                // Construct comma-separated strings for legacy frontend support
                $productNames = $sale->items->map(function ($item) {
                    if (!$item->product) {
                        return $item->is_manual ? ($item->product_name ?? 'Manual Product') . ' (Manual)' : 'Unknown';
                    }
                    
                    $vStr = '';
                    if (!empty($item->color)) {
                        $itemVariant = [];
                        $b64Decoded = base64_decode($item->color, true);
                        if ($b64Decoded !== false) {
                            $json = json_decode($b64Decoded, true);
                            if (is_array($json)) {
                                $itemVariant = $json;
                            }
                        }
                        if (empty($itemVariant)) {
                            $json = json_decode($item->color, true);
                            if (is_array($json)) {
                                $itemVariant = $json;
                            }
                        }

                        if (!empty($itemVariant)) {
                            $sizeVal = $itemVariant['size'] ?? ($itemVariant['size_val'] ?? '');
                            $colorVal = $itemVariant['color'] ?? ($itemVariant['color_val'] ?? '');
                            $vParts = array_filter([$sizeVal, $colorVal], fn($v) => !empty($v) && trim($v) !== '-');
                            if (count($vParts) > 0) {
                                $vStr = ' (' . implode(' | ', $vParts) . ')';
                            }
                        } else {
                            $vStr = (!empty($item->color) && trim($item->color) !== '-') ? ' (' . $item->color . ')' : '';
                        }
                    }
                    return $item->product->item_name . $vStr;
                })->implode(',');

                // Use SKU or Name as per preference, usually Name for reports
                $productCodes = $sale->items->map(function ($item) {
                    return $item->product ? $item->product->item_code : ($item->is_manual ? 'MANUAL' : '-');
                })->implode(',');

                // Use formatted string for display, and decimal for calculation
                $qtys = $sale->items->map(function ($item) {
                    $ppb = $item->product && $item->product->pieces_per_box > 0 ? (int) $item->product->pieces_per_box : 1;
                    $tp = (int) $item->total_pieces;
                    $mode = $item->product ? $item->product->size_mode : 'by_pieces';
                    
                    if ($mode == 'by_pieces') {
                        return $tp . ' Pcs';
                    } else {
                        $b = floor($tp / $ppb);
                        $l = $tp % $ppb;
                        $uom = $mode == 'by_bandal' ? 'Bndl' : (in_array($mode, ['by_cartons', 'by_bandal']) ? 'Ctn' : 'Box');
                        if ($b > 0 && $l > 0) {
                            return $b . ' ' . $uom . ' + ' . $l . ' Pcs';
                        } elseif ($b > 0) {
                            return $b . ' ' . $uom;
                        } else {
                            return $l . ' Pcs';
                        }
                    }
                })->implode(',');
                
                $qty_decimals = $sale->items->pluck('qty')->implode(',');
                $total_pieces_arr = $sale->items->pluck('total_pieces')->implode(',');
                $prices = $sale->items->pluck('price')->implode(','); // Unit Price
                $totals = $sale->items->pluck('total')->implode(','); // Line Total
                $isExchange = \Illuminate\Support\Str::startsWith($sale->reference ?? '', 'Exchange for');
                $netAmount = (float)$sale->total_net;

                if ($isExchange) {
                    $collected = (float)$sale->cash - (float)$sale->change;
                    if ($collected > 0) {
                        $netAmount = $collected;
                    } elseif ($collected <= 0) {
                        $refundPayment = \App\Models\CustomerPayment::where('note', 'Refund Paid for POS Exchange #'.$sale->invoice_no)->first();
                        if ($refundPayment) {
                            $netAmount = -(float)$refundPayment->amount;
                        } else {
                            $netAmount = 0;
                        }
                    }
                }

                return [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no ?? ('INVSLE-' . $sale->id),
                    'reference' => $sale->reference ?? '-',
                    'product' => $productNames,      // Names
                    'product_code' => $productCodes, // Codes
                    'brand' => '-',                  // Could extract from items if needed
                    'unit' => '-',                   // Could extract
                    'per_price' => $prices,
                    'per_discount' => 0,             
                    'qty' => $qtys,
                    'qty_decimal' => $qty_decimals,
                    'total_pieces' => $total_pieces_arr,
                    'per_total' => $totals,
                    'total_net' => $netAmount,
                    'created_at' => $sale->created_at ? $sale->created_at->format('Y-m-d h:i A') : '-',
                    'customer_name' => $sale->customer_relation ? $sale->customer_relation->customer_name : ($sale->walkin_name ?: 'Walk-in'),
                    'customer_province' => $sale->customer_relation->province ?? '',
                    'customer_city' => $sale->customer_relation->city ?? '',
                    'customer_area' => $sale->customer_relation->area ?? '',
                    'sales_officer_name' => $sale->salesOfficer->name ?? ($sale->customer_relation->salesOfficer->name ?? '-'),
                    'po_no' => $sale->po_no ?? '',
                    'po_date' => $sale->po_date ? \Carbon\Carbon::parse($sale->po_date)->format('d/m/Y') : '',
                    'returns' => $sale->returns->map(function($ret) {
                         // Robust return display handling both legacy strings and new relation items
                         $retItems = $ret->items;
                         if ($retItems && $retItems->count() > 0) {
                             $pNames = $retItems->map(function($i) {
                                 if ($i->is_manual) {
                                     return $i->product_name . ' (Manual)';
                                 }
                                 if (!$i->product) return 'Unknown';
                                 
                                 $vStr = '';
                                 if (!empty($i->color)) {
                                     $itemVariant = [];
                                     $b64Decoded = base64_decode($i->color, true);
                                     if ($b64Decoded !== false) {
                                         $json = json_decode($b64Decoded, true);
                                         if (is_array($json)) {
                                             $itemVariant = $json;
                                         }
                                     }
                                     if (empty($itemVariant)) {
                                         $json = json_decode($i->color, true);
                                         if (is_array($json)) {
                                             $itemVariant = $json;
                                         }
                                     }

                                     if (!empty($itemVariant)) {
                                         $sizeVal = $itemVariant['size'] ?? ($itemVariant['size_val'] ?? '');
                                         $colorVal = $itemVariant['color'] ?? ($itemVariant['color_val'] ?? '');
                                         $vParts = array_filter([$sizeVal, $colorVal], fn($v) => !empty($v) && trim($v) !== '-');
                                         if (count($vParts) > 0) {
                                             $vStr = ' (' . implode(' | ', $vParts) . ')';
                                         }
                                     }
                                 }
                                 return $i->product->item_name . $vStr;
                             })->implode(', ');
                             
                             $pQtys = $retItems->pluck('qty')->implode(', ');
                             $pTotal = $retItems->sum('line_total');
                         } else {
                             $pNames = $ret->product ?? '-';
                             $pQtys = $ret->qty ?? 0;
                             $pTotal = $ret->net_amount ?? 0;
                         }

                         return [
                            'product' => $pNames,
                            'qty' => $pQtys,
                            'per_total' => $pTotal
                         ];
                    })
                ];
            });

            // Calculate Expenses for date range
            $expenseQueryV1 = DB::table('expense_vouchers');
            $expenseQueryV2 = DB::table('voucher_masters')->where('voucher_type', 'expense');

            if ($start && $end) {
                $expenseQueryV1->whereDate('entry_date', '>=', $start)->whereDate('entry_date', '<=', $end);
                $expenseQueryV2->whereDate('date', '>=', $start)->whereDate('date', '<=', $end);
            } elseif ($start) {
                $expenseQueryV1->whereDate('entry_date', '>=', $start);
                $expenseQueryV2->whereDate('date', '>=', $start);
            } elseif ($end) {
                $expenseQueryV1->whereDate('entry_date', '<=', $end);
                $expenseQueryV2->whereDate('date', '<=', $end);
            }

            $totalExpenses = (float) $expenseQueryV1->sum('total_amount') + (float) $expenseQueryV2->sum('total_amount');

            // Calculate Total COGS for fetched sales
            $totalCogs = 0;
            foreach ($sales as $sale) {
                foreach ($sale->items as $item) {
                    $purchPrice = 0;
                    if (isset($item->purchase_price) && (float)$item->purchase_price > 0) {
                        $purchPrice = (float) $item->purchase_price;
                    } elseif ($item->product) {
                        $purchPrice = (float) ($item->product->purchase_price_per_piece ?? 0);
                    }
                    $totalCogs += ((float) $item->total_pieces) * $purchPrice;
                }
            }

            return response()->json([
                'sales' => $transformed,
                'summary' => [
                    'expenses' => $totalExpenses,
                    'cogs'     => $totalCogs,
                ]
            ]);
        }

        return view('admin_panel.reporting.sale_report');
    }

    public function customer_ledger_report()
    {
        $customers = DB::table('customers')->select('id', 'customer_name', 'zone', 'province', 'city', 'area')->get();
        $zones = \App\Models\Zone::orderBy('zone')->get();

        return view('admin_panel.reporting.customer_ledger_report', compact('customers', 'zones'));
    }

    public function fetch_customer_ledger(Request $request)
    {
        $customerId = $request->customer_id;
        $zoneId = $request->zone_id;
        $start = $request->start_date ?: '2000-01-01';
        $end = $request->end_date ?: date('Y-m-d');

        $balanceService = app(\App\Services\BalanceService::class);

        // If "all" or empty, fetch for ALL customers
        if (!$customerId || $customerId === 'all') {
            // Get all customers who have journal entries
            $customerIds = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->distinct()
                ->pluck('party_id')
                ->toArray();

            // Also include customers with opening balance
            $obCustomerIds = \App\Models\Customer::where('opening_balance', '>', 0)
                ->pluck('id')
                ->toArray();

            $allIds = array_unique(array_merge($customerIds, $obCustomerIds));

            // Apply zone filter if provided
            if ($zoneId) {
                // Filter allIds to only those customers who belong to the selected zone
                $validZoneIds = \App\Models\Customer::where('zone', $zoneId)->pluck('id')->toArray();
                $allIds = array_intersect($allIds, $validZoneIds);
            }

            $provLedger = $request->province;
            $cityLedger = $request->city;
            $areaLedger = $request->area;
            if (($provLedger && $provLedger !== 'all') || ($cityLedger && $cityLedger !== 'all') || ($areaLedger && $areaLedger !== 'all')) {
                $locQ = \App\Models\Customer::query();
                if ($provLedger && $provLedger !== 'all') $locQ->where('province', $provLedger);
                if ($cityLedger && $cityLedger !== 'all') $locQ->where('city', $cityLedger);
                if ($areaLedger && $areaLedger !== 'all') $locQ->where('area', $areaLedger);
                $validLocIds = $locQ->pluck('id')->toArray();
                $allIds = array_intersect($allIds, $validLocIds);
            }

            $allTransactions = [];
            $totalOpening = 0;
            $totalClosing = 0;

            foreach ($allIds as $cid) {
                $ledgerData = $balanceService->getCustomerLedger($cid, $start, $end);
                $customerName = $ledgerData['customer']->customer_name ?? 'Unknown';
                $totalOpening += $ledgerData['opening_balance'];

                foreach ($ledgerData['transactions'] as $row) {
                    $desc = $row['description'] ?? '';

                    $existingAgainst = '';
                    if (preg_match('/\s*(\(Against[^)]+\))/i', $desc, $m)) {
                        $existingAgainst = $m[1];
                        $desc = trim(str_replace($m[0], '', $desc));
                    }

                    // Try to find payment account name for receipt entries
                    $accountName = '';
                    if ($row['credit'] > 0 && ($row['source_type'] ?? null)) {
                        $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
                    }
                    if ($accountName) {
                        $desc .= ' [A/C: ' . $accountName . ']';
                    }

                    // Get invoice allocations if available
                    $allocData = $row['alloc_data'] ?? $balanceService->getVoucherAllocationsData($row['source_type'] ?? null, $row['source_id'] ?? null);
                    $againstSummary = !empty($allocData['summary']) ? $allocData['summary'] : $existingAgainst;
                    if (!empty($againstSummary) && !str_contains($desc, 'Against ')) {
                        $desc .= ' ' . $againstSummary;
                    }

                    $ref = '-';
                    $invoiceUrl = null;
                    $invoiceBadgesHtml = null;

                    if (!empty($allocData['ref_text'])) {
                        $ref = $allocData['ref_text'];
                        $invoiceUrl = $allocData['first_url'];
                        $invoiceBadgesHtml = $allocData['badges_html'];
                    } else {
                        if (preg_match('/Invoice #(\S+)/', $desc, $matches)) {
                            $ref = $matches[1];
                        } elseif (preg_match('/Sale Return #(\S+)/', $desc, $matches)) {
                            $ref = $matches[1];
                        } elseif (preg_match('/Receipt #(\S+)/', $desc, $matches)) {
                            $ref = $matches[1];
                        }

                        if (($row['source_type'] ?? '') === \App\Models\Sale::class || ($row['source_type'] ?? '') === 'Sale' || ($row['source_type'] ?? '') === 'SaleInvoice') {
                            if (!empty($row['source_id'])) {
                                $invoiceUrl = route('sales.invoice', $row['source_id']);
                            }
                        }
                        if (!$invoiceUrl && $ref !== '-') {
                            $saleId = \App\Models\Sale::where('invoice_no', $ref)->orWhere('id', $ref)->value('id');
                            if ($saleId) {
                                $invoiceUrl = route('sales.invoice', $saleId);
                            }
                        }
                    }

                    $entryDate = $row['date'];
                    if ($entryDate instanceof \Carbon\Carbon) {
                        $formattedDate = $entryDate->format('d-M-Y');
                        $sortDate = $entryDate->format('Y-m-d');
                    } else {
                        $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
                        $sortDate = \Carbon\Carbon::parse($entryDate)->format('Y-m-d');
                    }

                    $allTransactions[] = [
                        'sort_date' => $sortDate,
                        'date' => $formattedDate,
                        'invoice' => $ref,
                        'invoice_url' => $invoiceUrl,
                        'invoice_badges_html' => $invoiceBadgesHtml,
                        'invoices' => $allocData['invoices'] ?? [],
                        'description' => $desc,
                        'customer_name' => $customerName,
                        'debit' => $row['debit'] ?? 0,
                        'credit' => $row['credit'] ?? 0,
                        'balance' => $row['balance'] ?? 0,
                    ];
                }

                $totalClosing += $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'];
            }

            // Sort by date
            usort($allTransactions, function ($a, $b) {
                return strcmp($a['sort_date'], $b['sort_date']);
            });

            // Recalculate running balance across all
            $running = $totalOpening;
            foreach ($allTransactions as &$t) {
                $running += ($t['debit'] - $t['credit']);
                $t['balance'] = $running;
            }

            return response()->json([
                'customer' => (object)['customer_name' => 'All Customers'],
                'opening_balance' => $totalOpening,
                'closing_balance' => $totalClosing,
                'transactions' => $allTransactions,
                'report_period' => "$start to $end",
            ]);
        }

        // Single customer
        $customer = DB::table('customers')->where('id', $customerId)->first();
        if (!$customer) {
            return response()->json(['error' => 'Customer not found'], 400);
        }

        $ledgerData = $balanceService->getCustomerLedger($customerId, $start, $end);

        $transactions = collect($ledgerData['transactions'])->map(function ($row) use ($balanceService) {
            $desc = $row['description'] ?? '';

            $existingAgainst = '';
            if (preg_match('/\s*(\(Against[^)]+\))/i', $desc, $m)) {
                $existingAgainst = $m[1];
                $desc = trim(str_replace($m[0], '', $desc));
            }

            // Try to find payment account name for receipt entries
            $accountName = '';
            if ($row['credit'] > 0 && ($row['source_type'] ?? null)) {
                $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
            }
            if ($accountName) {
                $desc .= ' [A/C: ' . $accountName . ']';
            }

            // Get invoice allocations if available
            $allocData = $row['alloc_data'] ?? $balanceService->getVoucherAllocationsData($row['source_type'] ?? null, $row['source_id'] ?? null);
            $againstSummary = !empty($allocData['summary']) ? $allocData['summary'] : $existingAgainst;
            if (!empty($againstSummary) && !str_contains($desc, 'Against ')) {
                $desc .= ' ' . $againstSummary;
            }

            $ref = '-';
            $invoiceUrl = null;
            $invoiceBadgesHtml = null;

            if (!empty($allocData['ref_text'])) {
                $ref = $allocData['ref_text'];
                $invoiceUrl = $allocData['first_url'];
                $invoiceBadgesHtml = $allocData['badges_html'];
            } else {
                if (preg_match('/Invoice #(\S+)/', $desc, $matches)) {
                    $ref = $matches[1];
                } elseif (preg_match('/Sale Return #(\S+)/', $desc, $matches)) {
                    $ref = $matches[1];
                } elseif (preg_match('/Receipt #(\S+)/', $desc, $matches)) {
                    $ref = $matches[1];
                }

                if (($row['source_type'] ?? '') === \App\Models\Sale::class || ($row['source_type'] ?? '') === 'Sale' || ($row['source_type'] ?? '') === 'SaleInvoice') {
                    if (!empty($row['source_id'])) {
                        $invoiceUrl = route('sales.invoice', $row['source_id']);
                    }
                }
                if (!$invoiceUrl && $ref !== '-') {
                    $saleId = \App\Models\Sale::where('invoice_no', $ref)->orWhere('id', $ref)->value('id');
                    if ($saleId) {
                        $invoiceUrl = route('sales.invoice', $saleId);
                    }
                }
            }

            $entryDate = $row['date'];
            if ($entryDate instanceof \Carbon\Carbon) {
                $formattedDate = $entryDate->format('d-M-Y');
            } else {
                $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
            }

            return [
                'date' => $formattedDate,
                'invoice' => $ref,
                'invoice_url' => $invoiceUrl,
                'invoice_badges_html' => $invoiceBadgesHtml,
                'invoices' => $allocData['invoices'] ?? [],
                'description' => $desc,
                'debit' => $row['debit'] ?? 0,
                'credit' => $row['credit'] ?? 0,
                'balance' => $row['balance'] ?? 0,
            ];
        });

        // Check if customer is both (Customer & Vendor)
        $isBoth = ($customer->party_type ?? '') === 'both' || !empty($customer->linked_vendor_id);
        $linkedVendor = $isBoth ? \App\Models\Vendor::find($customer->linked_vendor_id) : null;
        $combinedLedger = null;
        if ($isBoth) {
            $combinedLedger = $balanceService->getCombinedPartyLedger($customer->id, $customer->linked_vendor_id, $start, $end);
        }

        return response()->json([
            'customer' => $customer,
            'is_both' => $isBoth,
            'linked_vendor' => $linkedVendor,
            'opening_balance' => $ledgerData['opening_balance'],
            'closing_balance' => $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'],
            'transactions' => $transactions,
            'combined_ledger' => $combinedLedger,
            'report_period' => "$start to $end",
        ]);
    }

    /**
     * Get the payment account name from voucher source
     */
    private function getPaymentAccountName($sourceType, $sourceId)
    {
        try {
            if ($sourceType === \App\Models\VoucherMaster::class && $sourceId) {
                // Look at VoucherDetail for the debit side (cash/bank account)
                $voucherDetail = \App\Models\VoucherDetail::where('voucher_master_id', $sourceId)
                    ->where('debit', '>', 0)
                    ->first();
                if ($voucherDetail && $voucherDetail->account_id) {
                    $account = \App\Models\Account::find($voucherDetail->account_id);
                    return $account ? $account->title : '';
                }
            } elseif ($sourceType === \App\Models\PaymentVoucher::class && $sourceId) {
                $pv = \App\Models\PaymentVoucher::find($sourceId);
                if ($pv && $pv->row_account_id) {
                    $account = \App\Models\Account::find($pv->row_account_id);
                    return $account ? $account->title : '';
                }
            }
        } catch (\Exception $e) {
            // Silently fail
        }
        return '';
    }

    public function vendor_ledger_report()
    {
        $vendors = DB::table('vendors')->select('id', 'name')->orderBy('name')->get();

        return view('admin_panel.reporting.vendor_ledger_report', compact('vendors'));
    }

    public function fetch_vendor_ledger(Request $request)
    {
        $vendorId = $request->vendor_id;
        $start = $request->start_date ?: '2000-01-01';
        $end = $request->end_date ?: date('Y-m-d');

        $balanceService = app(\App\Services\BalanceService::class);

        // If "all" or empty, fetch for ALL vendors
        if (!$vendorId || $vendorId === 'all') {
            $vendorIds = \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
                ->distinct()
                ->pluck('party_id')
                ->toArray();

            // Also include vendors with opening balance
            $obVendorIds = \App\Models\Vendor::where('opening_balance', '>', 0)
                ->pluck('id')
                ->toArray();

            $allIds = array_unique(array_merge($vendorIds, $obVendorIds));

            $allTransactions = [];
            $totalOpening = 0;
            $totalClosing = 0;

            foreach ($allIds as $vid) {
                $ledgerData = $balanceService->getVendorLedger($vid, $start, $end);
                $vendorName = $ledgerData['vendor']->name ?? 'Unknown';
                $totalOpening += $ledgerData['opening_balance'];

                foreach ($ledgerData['transactions'] as $row) {
                    $desc = $row['description'] ?? '';

                    $existingAgainst = '';
                    if (preg_match('/\s*(\(Against[^)]+\))/i', $desc, $m)) {
                        $existingAgainst = $m[1];
                        $desc = trim(str_replace($m[0], '', $desc));
                    }

                    $accountName = '';
                    if ($row['debit'] > 0 && ($row['source_type'] ?? null)) {
                        $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
                    }
                    if ($accountName) {
                        $desc .= ' [A/C: ' . $accountName . ']';
                    }

                    // Get purchase bill allocations if available
                    $allocData = $row['alloc_data'] ?? $balanceService->getVoucherAllocationsData($row['source_type'] ?? null, $row['source_id'] ?? null);
                    $againstSummary = !empty($allocData['summary']) ? $allocData['summary'] : $existingAgainst;
                    if (!empty($againstSummary) && !str_contains($desc, 'Against ')) {
                        $desc .= ' ' . $againstSummary;
                    }

                    $ref = '-';
                    $invoiceUrl = null;
                    $invoiceBadgesHtml = null;

                    if (!empty($allocData['ref_text'])) {
                        $ref = $allocData['ref_text'];
                        $invoiceUrl = $allocData['first_url'];
                        $invoiceBadgesHtml = $allocData['badges_html'];
                    } else {
                        if (preg_match('/PUR-(\S+)/', $desc, $matches)) {
                            $ref = 'PUR-' . $matches[1];
                        } elseif (preg_match('/Payment #(\S+)/', $desc, $matches)) {
                            $ref = $matches[1];
                        } elseif (preg_match('/Purchase #(\S+)/', $desc, $matches)) {
                            $ref = $matches[1];
                        }

                        if (($row['source_type'] ?? '') === \App\Models\Purchase::class || ($row['source_type'] ?? '') === 'Purchase') {
                            if (!empty($row['source_id'])) {
                                $invoiceUrl = route('purchase.invoice', $row['source_id']);
                            }
                        }
                        if (!$invoiceUrl && $ref !== '-') {
                            $purchaseId = \App\Models\Purchase::where('invoice_no', $ref)->orWhere('id', $ref)->value('id');
                            if ($purchaseId) {
                                $invoiceUrl = route('purchase.invoice', $purchaseId);
                            }
                        }
                    }

                    $entryDate = $row['date'];
                    if ($entryDate instanceof \Carbon\Carbon) {
                        $formattedDate = $entryDate->format('d-M-Y');
                        $sortDate = $entryDate->format('Y-m-d');
                    } else {
                        $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
                        $sortDate = \Carbon\Carbon::parse($entryDate)->format('Y-m-d');
                    }

                    $allTransactions[] = [
                        'sort_date'           => $sortDate,
                        'date'                => $formattedDate,
                        'invoice'             => $ref,
                        'invoice_no'          => $row['invoice_no'] ?? ($ref !== '-' ? $ref : null),
                        'invoice_url'         => $invoiceUrl,
                        'invoice_badges_html' => $invoiceBadgesHtml,
                        'invoices'            => $allocData['invoices'] ?? [],
                        'vendor_invoice_no'   => $row['vendor_invoice_no'] ?? null,
                        'source_type'         => $row['source_type'] ?? null,
                        'description'         => $desc,
                        'vendor_name'         => $vendorName,
                        'debit'               => $row['debit'] ?? 0,
                        'credit'              => $row['credit'] ?? 0,
                        'balance'             => $row['balance'] ?? 0,
                    ];
                }

                $totalClosing += $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'];
            }

            // Sort by date
            usort($allTransactions, function ($a, $b) {
                return strcmp($a['sort_date'], $b['sort_date']);
            });

            // Recalculate running balance across all
            $running = $totalOpening;
            foreach ($allTransactions as &$t) {
                $running += ($t['credit'] - $t['debit']);
                $t['balance'] = $running;
            }

            return response()->json([
                'vendor' => (object)['name' => 'All Vendors'],
                'opening_balance' => $totalOpening,
                'closing_balance' => $totalClosing,
                'transactions' => $allTransactions,
                'report_period' => "$start to $end",
            ]);
        }

        // Single vendor
        $vendor = DB::table('vendors')->where('id', $vendorId)->first();
        if (!$vendor) {
            return response()->json(['error' => 'Vendor not found'], 400);
        }

        $ledgerData = $balanceService->getVendorLedger($vendorId, $start, $end);

        $transactions = collect($ledgerData['transactions'])->map(function ($row) use ($balanceService) {
            $desc = $row['description'] ?? '';

            $existingAgainst = '';
            if (preg_match('/\s*(\(Against[^)]+\))/i', $desc, $m)) {
                $existingAgainst = $m[1];
                $desc = trim(str_replace($m[0], '', $desc));
            }

            $accountName = '';
            if ($row['debit'] > 0 && ($row['source_type'] ?? null)) {
                $accountName = $this->getPaymentAccountName($row['source_type'], $row['source_id']);
            }
            if ($accountName) {
                $desc .= ' [A/C: ' . $accountName . ']';
            }

            // Get purchase bill allocations if available
            $allocData = $row['alloc_data'] ?? $balanceService->getVoucherAllocationsData($row['source_type'] ?? null, $row['source_id'] ?? null);
            $againstSummary = !empty($allocData['summary']) ? $allocData['summary'] : $existingAgainst;
            if (!empty($againstSummary) && !str_contains($desc, 'Against ')) {
                $desc .= ' ' . $againstSummary;
            }

            $ref = '-';
            $invoiceUrl = null;
            $invoiceBadgesHtml = null;

            if (!empty($allocData['ref_text'])) {
                $ref = $allocData['ref_text'];
                $invoiceUrl = $allocData['first_url'];
                $invoiceBadgesHtml = $allocData['badges_html'];
            } else {
                if (preg_match('/PUR-(\S+)/', $desc, $matches)) {
                    $ref = 'PUR-' . $matches[1];
                } elseif (preg_match('/Payment #(\S+)/', $desc, $matches)) {
                    $ref = $matches[1];
                } elseif (preg_match('/Purchase #(\S+)/', $desc, $matches)) {
                    $ref = $matches[1];
                }

                if (($row['source_type'] ?? '') === \App\Models\Purchase::class || ($row['source_type'] ?? '') === 'Purchase') {
                    if (!empty($row['source_id'])) {
                        $invoiceUrl = route('purchase.invoice', $row['source_id']);
                    }
                }
                if (!$invoiceUrl && $ref !== '-') {
                    $purchaseId = \App\Models\Purchase::where('invoice_no', $ref)->orWhere('id', $ref)->value('id');
                    if ($purchaseId) {
                        $invoiceUrl = route('purchase.invoice', $purchaseId);
                    }
                }
            }

            $entryDate = $row['date'];
            if ($entryDate instanceof \Carbon\Carbon) {
                $formattedDate = $entryDate->format('d-M-Y');
            } else {
                $formattedDate = \Carbon\Carbon::parse($entryDate)->format('d-M-Y');
            }

            return [
                'date'                => $formattedDate,
                'invoice'             => $ref,
                'invoice_no'          => $row['invoice_no'] ?? ($ref !== '-' ? $ref : null),
                'invoice_url'         => $invoiceUrl,
                'invoice_badges_html' => $invoiceBadgesHtml,
                'invoices'            => $allocData['invoices'] ?? [],
                'vendor_invoice_no'   => $row['vendor_invoice_no'] ?? null,
                'source_type'         => $row['source_type'] ?? null,
                'description'         => $desc,
                'debit'               => $row['debit'] ?? 0,
                'credit'              => $row['credit'] ?? 0,
                'balance'             => $row['balance'] ?? 0,
            ];
        });

        // Check if vendor is both (Customer & Vendor)
        $isBoth = ($vendor->party_type ?? '') === 'both' || !empty($vendor->linked_customer_id);
        $linkedCustomer = $isBoth ? \App\Models\Customer::find($vendor->linked_customer_id) : null;
        $combinedLedger = null;
        if ($isBoth) {
            $combinedLedger = $balanceService->getCombinedPartyLedger($vendor->linked_customer_id, $vendor->id, $start, $end);
        }

        return response()->json([
            'vendor' => $vendor,
            'is_both' => $isBoth,
            'linked_customer' => $linkedCustomer,
            'opening_balance' => $ledgerData['opening_balance'],
            'closing_balance' => $ledgerData['closing_balance'] ?? $ledgerData['opening_balance'],
            'transactions' => $transactions,
            'combined_ledger' => $combinedLedger,
            'report_period' => "$start to $end",
        ]);
    }

    public function balance_sheet_report()
    {
        return view('admin_panel.reporting.balance_sheet');
    }

    public function fetch_balance_sheet(Request $request)
    {
        $date = $request->date ?: date('Y-m-d');
        // Let's get "As Of Date" balances. If a future date is given, it still acts up to that point.
        // We will include ending day inclusive so we add time OR use <=
        $dateEnd = $date . ' 23:59:59';

        $balanceService = app(\App\Services\BalanceService::class);

        // 1. Current Assets
        // Cash in Hand (head_id = 1)
        // Cash at Bank (Usually head_id = 1, we can split them if we can identify them, but we'll show all assets)
        $assetAccounts = DB::table('accounts')->whereIn('head_id', [1, 2])->get();
        $cashAccounts = [];
        $totalCashBank = 0;

        foreach ($assetAccounts as $acc) {
            // Need balance up to Date from Journal Entries
            $balance = DB::table('journal_entries')
                ->where('account_id', $acc->id)
                ->where('entry_date', '<=', $dateEnd)
                ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
                ->value('total') ?? 0;
            
            $computed_balance = $acc->opening_balance + $balance;
            if ($computed_balance != 0) {
                $cashAccounts[] = [
                    'name' => $acc->title,
                    'balance' => $computed_balance
                ];
                $totalCashBank += $computed_balance;
            }
        }

        // Account Receivables
        $custQuery = DB::table('customers');
            if ($request->province && $request->province !== 'all') $custQuery->where('province', $request->province);
            if ($request->city && $request->city !== 'all') $custQuery->where('city', $request->city);
            if ($request->area && $request->area !== 'all') $custQuery->where('area', $request->area);
            $customers = $custQuery->get();
        $totalReceivables = 0;
        foreach ($customers as $c) {
            $bal = $balanceService->getCustomerBalanceBeforeDate($c->id, $dateEnd);
            $totalReceivables += $bal;
        }

        // Stock in Trade
        // Current Stock = Initial + Purchased - Sold
        $products = DB::table('products')->get();
        $totalInventory = 0;
        foreach ($products as $p) {
            // Initial
            $initial = (float) DB::table('stock_movements')
                ->where('product_id', $p->id)
                ->whereIn('ref_type', ['INIT', 'OPENING_STOCK'])
                ->where('created_at', '<=', $dateEnd)
                ->sum('qty');

            // Purchased
            [$purchased, $purchaseAmount] = $this->getPurchasedQtyAndNetAmount($p->id, ['before' => $dateEnd]);

            // Sold
            $saleStats = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->where('sale_items.product_id', $p->id)
                ->where('sales.created_at', '<=', $dateEnd)
                ->selectRaw('COALESCE(SUM(sale_items.qty),0) as total_qty')
                ->first();

            $sold = (float) $saleStats->total_qty;

            $balance = $initial + $purchased - $sold;

            // Average Cost
            $productPurchPrice = 0;
            if ($p->size_mode === 'by_size') {
                $m2PerPiece = (float) ($p->pieces_per_m2 ?? 0);
                $purchPerM2 = (float) ($p->purchase_price_per_m2 ?? 0);
                $productPurchPrice = $m2PerPiece * $purchPerM2;
            } else {
                $productPurchPrice = (float) ($p->purchase_price_per_piece ?? 0);
            }

            $initialAmount = $initial * $productPurchPrice;
            $totalQtyIn = $initial + $purchased;
            $avgCost = $totalQtyIn > 0 ? ($initialAmount + $purchaseAmount) / $totalQtyIn : $productPurchPrice;

            if ($balance > 0) {
                $totalInventory += ($balance * $avgCost);
            }
        }

        $currentAssetsTotal = $totalCashBank + $totalReceivables + $totalInventory;
        $fixedAssetsTotal = 0; // if you have fixed assets head, you can add here
        $totalAssets = $currentAssetsTotal + $fixedAssetsTotal;

        // 2. Liabilities
        $vendQuery = DB::table('vendors');
            if ($request->province && $request->province !== 'all') $vendQuery->where('province', $request->province);
            if ($request->city && $request->city !== 'all') $vendQuery->where('city', $request->city);
            if ($request->area && $request->area !== 'all') $vendQuery->where('area', $request->area);
            $vendors = $vendQuery->get();
        $totalPayables = 0;
        foreach ($vendors as $v) {
            $bal = $balanceService->getVendorBalanceBeforeDate($v->id, $dateEnd);
            $totalPayables += $bal; // Vendor Balance (Cr is positive from BalanceService view)
        }
        $currentLiabilitiesTotal = $totalPayables;

        // 3. Owner's Equity (Equity = Assets - Liabilities)
        $equityTotal = $totalAssets - $currentLiabilitiesTotal;
        $totalLiabilitiesAndEquity = $currentLiabilitiesTotal + $equityTotal;

        return response()->json([
            'date' => date('d-M-Y', strtotime($date)),
            'assets' => [
                'cash_bank' => $cashAccounts,
                'total_cash_bank' => $totalCashBank,
                'receivables' => $totalReceivables,
                'inventory' => $totalInventory,
                'current_total' => $currentAssetsTotal,
                'fixed_total' => $fixedAssetsTotal,
                'total' => $totalAssets
            ],
            'liabilities' => [
                'payables' => $totalPayables,
                'current_total' => $currentLiabilitiesTotal,
                'equity' => $equityTotal,
                'total' => $totalLiabilitiesAndEquity
            ]
        ]);
    }

    public function recovery_report()
    {
        return view('admin_panel.reporting.recovery_report');
    }

    public function fetch_recovery(Request $request)
    {
        $startDate = $request->start_date ?: '2000-01-01';
        $endDate = $request->end_date ?: date('Y-m-d');
        $dateEndParams = $endDate . ' 23:59:59';
        
        $balanceService = app(\App\Services\BalanceService::class);
        $custQuery = DB::table('customers')->orderBy('customer_name');
        if ($request->province && $request->province !== 'all') {
            $custQuery->where('province', $request->province);
        }
        if ($request->city && $request->city !== 'all') {
            $custQuery->where('city', $request->city);
        }
        if ($request->area && $request->area !== 'all') {
            $custQuery->where('area', $request->area);
        }
        $customers = $custQuery->get();
        
        $rows = [];
        $totalOpening = 0;
        $totalSales = 0;
        $totalReceived = 0;
        $totalFinal = 0;

        foreach ($customers as $index => $c) {
            $initialOpening = (float) ($c->opening_balance ?? 0);

            $priorStats = DB::table('journal_entries')
                ->where('party_type', \App\Models\Customer::class)
                ->where('party_id', $c->id)
                ->where('entry_date', '<', $startDate)
                ->where('description', 'NOT LIKE', 'Opening Balance%')
                ->selectRaw('COALESCE(SUM(debit), 0) as debits, COALESCE(SUM(credit), 0) as credits')
                ->first();

            $priorDebits = (float) ($priorStats->debits ?? 0);
            $priorCredits = (float) ($priorStats->credits ?? 0);

            $opening = $initialOpening + $priorDebits - $priorCredits;

            $periodStats = DB::table('journal_entries')
                ->where('party_type', \App\Models\Customer::class)
                ->where('party_id', $c->id)
                ->whereBetween('entry_date', [$startDate, $dateEndParams])
                ->where('description', 'NOT LIKE', 'Opening Balance%')
                ->selectRaw('COALESCE(SUM(debit), 0) as debits, COALESCE(SUM(credit), 0) as credits')
                ->first();

            $sales = (float) ($periodStats->debits ?? 0);
            $received = (float) ($periodStats->credits ?? 0);

            $final = $opening + $sales - $received;

            if (abs($opening) > 0 || abs($sales) > 0 || abs($received) > 0 || abs($final) > 0) {
                $rows[] = [
                    'sr' => count($rows) + 1,
                    'party' => $c->customer_name,
                    'city' => $c->city ?? '',
                    'area' => $c->area ?? '',
                    'opening' => $opening,
                    'sales' => $sales,
                    'received' => $received,
                    'final' => $final
                ];

                $totalOpening += $opening;
                $totalSales += $sales;
                $totalReceived += $received;
                $totalFinal += $final;
            }
        }

        return response()->json([
            'date_range' => date('d-m-Y', strtotime($startDate)) . ' to ' . date('d-m-Y', strtotime($endDate)),
            'rows' => $rows,
            'totals' => [
                'opening' => $totalOpening,
                'sales' => $totalSales,
                'received' => $totalReceived,
                'final' => $totalFinal
            ]
        ]);
    }

    public function payable_report()
    {
        return view('admin_panel.reporting.payable_report');
    }

    public function fetch_payable(Request $request)
    {
        $startDate = $request->start_date ?: '2000-01-01';
        $endDate = $request->end_date ?: date('Y-m-d');
        $dateEndParams = $endDate . ' 23:59:59';
        
        $balanceService = app(\App\Services\BalanceService::class);
        $vendors = DB::table('vendors')->orderBy('name')->get();
        $apId = $balanceService->getAccountsPayableId();
        
        $rows = [];
        $totalOpening = 0;
        $totalPurchases = 0;
        $totalPaid = 0;
        $totalFinal = 0;

        foreach ($vendors as $v) {
            $initialOpening = (float) ($v->opening_balance ?? 0);

            $priorStats = DB::table('journal_entries')
                ->where('party_type', \App\Models\Vendor::class)
                ->where('party_id', $v->id)
                ->where('entry_date', '<', $startDate)
                ->where('description', 'NOT LIKE', 'Opening Balance%')
                ->selectRaw('COALESCE(SUM(credit), 0) as credits, COALESCE(SUM(debit), 0) as debits')
                ->first();

            $priorCredits = (float) ($priorStats->credits ?? 0);
            $priorDebits = (float) ($priorStats->debits ?? 0);

            $opening = $initialOpening + $priorCredits - $priorDebits;
            
            $purchasesRaw = DB::table('purchases')
                ->where('vendor_id', $v->id)
                ->where('status_purchase', 'approved')
                ->whereBetween('purchase_date', [$startDate, $endDate])
                ->sum('net_amount');

            $returnsRaw = DB::table('purchase_returns')
                ->where('vendor_id', $v->id)
                ->whereBetween('return_date', [$startDate, $endDate])
                ->sum('net_amount');
            
            $purchases = (float) $purchasesRaw - (float) $returnsRaw;

            $paid = (float) \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
                ->where('party_id', $v->id)
                ->where('account_id', $apId)
                ->whereBetween('entry_date', [$startDate, $dateEndParams])
                ->where('description', 'NOT LIKE', 'Opening Balance%')
                ->sum('debit');
            
            $final = $opening + $purchases - $paid;

            if (abs($opening) > 0.01 || abs($purchases) > 0.01 || abs($paid) > 0.01 || abs($final) > 0.01) {
                $rows[] = [
                    'sr' => count($rows) + 1,
                    'party' => $v->name,
                    'opening' => $opening,
                    'purchases' => $purchases,
                    'paid' => $paid,
                    'final' => $final
                ];

                $totalOpening += $opening;
                $totalPurchases += $purchases;
                $totalPaid += $paid;
                $totalFinal += $final;
            }
        }

        return response()->json([
            'date_range' => date('d-m-Y', strtotime($startDate)) . ' to ' . date('d-m-Y', strtotime($endDate)),
            'rows' => $rows,
            'totals' => [
                'opening' => $totalOpening,
                'purchases' => $totalPurchases,
                'paid' => $totalPaid,
                'final' => $totalFinal
            ]
        ]);
    }

    public function parties_balance_report()
    {
        $zones = \App\Models\Zone::orderBy('zone', 'asc')->get();
        return view('admin_panel.reporting.parties_balance_report', compact('zones'));
    }

    public function fetch_parties_balance(Request $request)
    {
        $reportType = $request->report_type ?: 'BOTH'; // RECEIVABLE, PAYABLE, BOTH
        $showZero = $request->show_zero == 'true';
        $searchParty = $request->party_name;
        $searchMobile = $request->mobile;
        $searchZone = $request->zone;
        $searchAddress = $request->address;
        
        $balanceService = app(\App\Services\BalanceService::class);
        $apId = $balanceService->getAccountsPayableId();
        
        $parties = [];
        
        // Fetch Customers in 1 Batch Query
        if ($reportType == 'BOTH' || $reportType == 'RECEIVABLE') {
            $custBalances = DB::table('journal_entries')
                ->where('party_type', \App\Models\Customer::class)
                ->selectRaw('party_id, COALESCE(SUM(debit) - SUM(credit), 0) as balance')
                ->groupBy('party_id')
                ->pluck('balance', 'party_id');

            $customers = DB::table('customers')
                ->leftJoin('zones', 'customers.zone', '=', 'zones.id')
                ->select('customers.*', 'zones.zone as zone_name')
                ->get();
            foreach ($customers as $c) {
                if ($searchParty && stripos($c->customer_name, $searchParty) === false) continue;
                if ($searchMobile && stripos($c->mobile, $searchMobile) === false) continue;
                if ($searchZone && (string)$c->zone !== (string)$searchZone) continue;
                if ($searchAddress && stripos($c->address, $searchAddress) === false) continue;
                if ($request->province && $request->province !== 'all' && (string)$c->province !== (string)$request->province) continue;
                if ($request->city && $request->city !== 'all' && (string)$c->city !== (string)$request->city) continue;
                if ($request->area && $request->area !== 'all' && (string)$c->area !== (string)$request->area) continue;
                
                $balance = (float) ($custBalances[$c->id] ?? 0);
                $parties[] = [
                    'code' => sprintf("C%04d", $c->id),
                    'title' => $c->customer_name,
                    'mobile' => $c->mobile,
                    'zone' => $c->zone_name,
                    'address' => $c->address,
                    'city' => $c->city ?? '',
                    'area' => $c->area ?? '',
                    'balance' => $balance,
                    'type' => 'customer'
                ];
            }
        }

        // Fetch Vendors in 1 Batch Query
        if (($reportType == 'BOTH' || $reportType == 'PAYABLE') && empty($searchZone)) {
            $vendorBalances = DB::table('journal_entries')
                ->where('party_type', \App\Models\Vendor::class)
                ->where('account_id', $apId)
                ->selectRaw('party_id, COALESCE(SUM(credit) - SUM(debit), 0) as balance')
                ->groupBy('party_id')
                ->pluck('balance', 'party_id');

            $vendQuery = DB::table('vendors');
            if ($request->province && $request->province !== 'all') $vendQuery->where('province', $request->province);
            if ($request->city && $request->city !== 'all') $vendQuery->where('city', $request->city);
            if ($request->area && $request->area !== 'all') $vendQuery->where('area', $request->area);
            $vendors = $vendQuery->get();
            foreach ($vendors as $v) {
                if ($searchParty && stripos($v->name, $searchParty) === false) continue;
                if ($searchMobile && stripos($v->phone, $searchMobile) === false) continue;
                if ($searchAddress && stripos($v->address, $searchAddress) === false) continue;
                if ($request->province && $request->province !== 'all' && (string)$v->province !== (string)$request->province) continue;
                if ($request->city && $request->city !== 'all' && (string)$v->city !== (string)$request->city) continue;
                if ($request->area && $request->area !== 'all' && (string)$v->area !== (string)$request->area) continue;
                
                $balance = (float) ($vendorBalances[$v->id] ?? 0);
                $parties[] = [
                    'code' => sprintf("V%04d", $v->id),
                    'title' => $v->name,
                    'mobile' => $v->phone,
                    'zone' => '-',
                    'address' => $v->address,
                    'city' => $v->city ?? '',
                    'area' => $v->area ?? '',
                    'balance' => -$balance,
                    'type' => 'vendor'
                ];
            }
        }

        $rows = [];
        $totalReceivable = 0;
        $totalPayable = 0;
        $sr = 1;

        foreach ($parties as $p) {
            $bal = $p['balance'];
            
            $receivable = 0;
            $payable = 0;

            if ($bal > 0) {
                $receivable = $bal;
            } elseif ($bal < 0) {
                $payable = abs($bal);
            }

            // Apply strict type filters if there's any overflow
            if ($reportType == 'RECEIVABLE' && $receivable == 0 && !$showZero) continue;
            if ($reportType == 'PAYABLE' && $payable == 0 && !$showZero) continue;
            
            if (!$showZero && $receivable == 0 && $payable == 0) continue;

            $rows[] = [
                'sr' => $sr++,
                'code' => $p['code'],
                'title' => $p['title'],
                'mobile' => $p['mobile'] ?? '-',
                'zone' => $p['zone'] ?? '-',
                'address' => $p['address'] ?? '-',
                'city' => $p['city'] ?? '',
                'area' => $p['area'] ?? '',
                'receivable' => $receivable,
                'payable' => $payable,
                'notes' => ''
            ];

            $totalReceivable += $receivable;
            $totalPayable += $payable;
        }

        return response()->json([
            'rows' => $rows,
            'totals' => [
                'receivable' => $totalReceivable,
                'payable' => $totalPayable
            ]
        ]);
    }
    public function aging_report()
    {
        return view('admin_panel.reporting.aging_report');
    }

    public function fetch_aging(Request $request)
    {
        $type     = $request->type ?: 'receivable'; // receivable | payable
        $asOfDate = $request->as_of_date ?: date('Y-m-d');
        $today    = \Carbon\Carbon::parse($asOfDate);

        $balanceService = app(\App\Services\BalanceService::class);

        $rows = [];
        $grandTotal     = 0;
        $grandCurrent   = 0;
        $grand15        = 0;
        $grand30        = 0;
        $grand45        = 0;
        $grand60        = 0;
        $grand75        = 0;
        $grand90plus    = 0;

        if ($type === 'receivable') {
            // Customer Aging: Each Sale invoice outstanding
            $custQuery = DB::table('customers');
            if ($request->province && $request->province !== 'all') $custQuery->where('province', $request->province);
            if ($request->city && $request->city !== 'all') $custQuery->where('city', $request->city);
            if ($request->area && $request->area !== 'all') $custQuery->where('area', $request->area);
            $customers = $custQuery->get();

            foreach ($customers as $c) {
                $totalBalance = $balanceService->getCustomerBalance($c->id);
                if ($totalBalance <= 0) continue; // skip if no balance

                // Get all sale invoices for this customer
                $invoices = DB::table('sales')
                    ->where('customer_id', $c->id)
                    ->where('created_at', '<=', $asOfDate . ' 23:59:59')
                    ->select('id', 'invoice_no', 'total_net', 'created_at')
                    ->orderBy('created_at')
                    ->get();

                // Distribute total balance across invoices by age (FIFO)
                $remaining = $totalBalance;

                $bucket_current = 0;
                $bucket_15 = 0;
                $bucket_30 = 0;
                $bucket_45 = 0;
                $bucket_60 = 0;
                $bucket_75 = 0;
                $bucket_90plus = 0;

                foreach ($invoices as $inv) {
                    if ($remaining <= 0) break;
                    $invAmt  = min((float) $inv->total_net, $remaining);
                    $days    = (int) \Carbon\Carbon::parse($inv->created_at)->diffInDays($today);
                    $remaining -= $invAmt;

                    if ($days == 0)      $bucket_current += $invAmt;
                    elseif ($days <= 15) $bucket_15      += $invAmt;
                    elseif ($days <= 30) $bucket_30      += $invAmt;
                    elseif ($days <= 45) $bucket_45      += $invAmt;
                    elseif ($days <= 60) $bucket_60      += $invAmt;
                    elseif ($days <= 75) $bucket_75      += $invAmt;
                    else                 $bucket_90plus  += $invAmt;
                }

                // Any remaining (from opening balance) goes to 90+
                if ($remaining > 0) $bucket_90plus += $remaining;

                $rows[] = [
                    'name'      => $c->customer_name,
                    'mobile'    => $c->mobile ?? '',
                    'total'     => $totalBalance,
                    'current'   => $bucket_current,
                    '15d'       => $bucket_15,
                    '30d'       => $bucket_30,
                    '45d'       => $bucket_45,
                    '60d'       => $bucket_60,
                    '75d'       => $bucket_75,
                    '90plus'    => $bucket_90plus,
                ];

                $grandTotal   += $totalBalance;
                $grandCurrent += $bucket_current;
                $grand15      += $bucket_15;
                $grand30      += $bucket_30;
                $grand45      += $bucket_45;
                $grand60      += $bucket_60;
                $grand75      += $bucket_75;
                $grand90plus  += $bucket_90plus;
            }
        } else {
            // Vendor Aging (Payable)
            $vendQuery = DB::table('vendors');
            if ($request->province && $request->province !== 'all') $vendQuery->where('province', $request->province);
            if ($request->city && $request->city !== 'all') $vendQuery->where('city', $request->city);
            if ($request->area && $request->area !== 'all') $vendQuery->where('area', $request->area);
            $vendors = $vendQuery->get();

            foreach ($vendors as $v) {
                $totalBalance = $balanceService->getVendorBalance($v->id);
                if ($totalBalance <= 0) continue; // we owe them

                $invoices = DB::table('purchases')
                    ->where('vendor_id', $v->id)
                    ->where('purchase_date', '<=', $asOfDate)
                    ->where('status_purchase', 'approved')
                    ->select('id', 'invoice_no', 'net_amount', 'purchase_date')
                    ->orderBy('purchase_date')
                    ->get();

                $remaining = $totalBalance;

                $bucket_current = 0;
                $bucket_15 = 0;
                $bucket_30 = 0;
                $bucket_45 = 0;
                $bucket_60 = 0;
                $bucket_75 = 0;
                $bucket_90plus = 0;

                foreach ($invoices as $inv) {
                    if ($remaining <= 0) break;
                    $invAmt  = min((float) $inv->net_amount, $remaining);
                    $days    = (int) \Carbon\Carbon::parse($inv->purchase_date)->diffInDays($today);
                    $remaining -= $invAmt;

                    if ($days == 0)      $bucket_current += $invAmt;
                    elseif ($days <= 15) $bucket_15      += $invAmt;
                    elseif ($days <= 30) $bucket_30      += $invAmt;
                    elseif ($days <= 45) $bucket_45      += $invAmt;
                    elseif ($days <= 60) $bucket_60      += $invAmt;
                    elseif ($days <= 75) $bucket_75      += $invAmt;
                    else                 $bucket_90plus  += $invAmt;
                }

                if ($remaining > 0) $bucket_90plus += $remaining;

                $rows[] = [
                    'name'      => $v->name,
                    'mobile'    => $v->phone ?? '',
                    'total'     => $totalBalance,
                    'current'   => $bucket_current,
                    '15d'       => $bucket_15,
                    '30d'       => $bucket_30,
                    '45d'       => $bucket_45,
                    '60d'       => $bucket_60,
                    '75d'       => $bucket_75,
                    '90plus'    => $bucket_90plus,
                ];

                $grandTotal   += $totalBalance;
                $grandCurrent += $bucket_current;
                $grand15      += $bucket_15;
                $grand30      += $bucket_30;
                $grand45      += $bucket_45;
                $grand60      += $bucket_60;
                $grand75      += $bucket_75;
                $grand90plus  += $bucket_90plus;
            }
        }

        return response()->json([
            'as_of_date' => $today->format('d-M-Y'),
            'rows'       => $rows,
            'totals'     => [
                'total'     => $grandTotal,
                'current'   => $grandCurrent,
                '15d'       => $grand15,
                '30d'       => $grand30,
                '45d'       => $grand45,
                '60d'       => $grand60,
                '75d'       => $grand75,
                '90plus'    => $grand90plus,
            ]
        ]);
    }
    public function executive_report()
    {
        return view('admin_panel.reporting.executive_report');
    }

    public function fetch_executive_report(Request $request)
    {
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        // Sales
        $salesToday = DB::table('sales')->whereDate('created_at', $today)->sum('total_net');
        $salesMonth = DB::table('sales')->whereBetween('created_at', [$startOfMonth . ' 00:00:00', $endOfMonth . ' 23:59:59'])->sum('total_net');

        // Purchases
        $purchasesToday = DB::table('purchases')->whereDate('purchase_date', $today)->sum('net_amount');
        $purchasesMonth = DB::table('purchases')->whereBetween('purchase_date', [$startOfMonth, $endOfMonth])->sum('net_amount');

        // Expenses
        $expensesTodayV1 = DB::table('expense_vouchers')->where('entry_date', $today)->sum('total_amount');
        $expensesTodayV2 = DB::table('voucher_masters')->where('voucher_type', 'expense')->where('date', $today)->sum('total_amount');
        $expensesToday = $expensesTodayV1 + $expensesTodayV2;

        $expensesMonthV1 = DB::table('expense_vouchers')->whereBetween('entry_date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $expensesMonthV2 = DB::table('voucher_masters')->where('voucher_type', 'expense')->whereBetween('date', [$startOfMonth, $endOfMonth])->sum('total_amount');
        $expensesMonth = $expensesMonthV1 + $expensesMonthV2;

        // Cash & Bank Balances
        $cashAccounts = DB::table('accounts')
            ->join('account_heads', 'accounts.head_id', '=', 'account_heads.id')
            ->where('account_heads.name', 'like', '%Cash%')
            ->select('accounts.title', 'accounts.current_balance')
            ->get();

        $bankAccounts = DB::table('accounts')
            ->join('account_heads', 'accounts.head_id', '=', 'account_heads.id')
            ->where('account_heads.name', 'like', '%Bank%')
            ->select('accounts.title', 'accounts.current_balance')
            ->get();

        $balanceService = app(\App\Services\BalanceService::class);
        
        // Receivables (Customers) in Single Query
        $totalReceivables = DB::table('journal_entries')
            ->where('party_type', \App\Models\Customer::class)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total') ?? 0;

        // Payables (Vendors) in Single Query
        $apId = $balanceService->getAccountsPayableId();
        $totalPayables = DB::table('journal_entries')
            ->where('party_type', \App\Models\Vendor::class)
            ->where('account_id', $apId)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as total')
            ->value('total') ?? 0;

        // Top 10 Customers by Profit
        $custQuery = DB::table('customers');
            if ($request->province && $request->province !== 'all') $custQuery->where('province', $request->province);
            if ($request->city && $request->city !== 'all') $custQuery->where('city', $request->city);
            if ($request->area && $request->area !== 'all') $custQuery->where('area', $request->area);
            $customers = $custQuery->get();
        $customerProfits = [];
        foreach ($customers as $c) {
            $saleStats = DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->where('sales.customer_id', $c->id)
                ->selectRaw('
                    SUM(sale_items.total) as revenue, 
                    SUM(sale_items.total_pieces * products.purchase_price_per_piece) as cogs
                ')
                ->first();

            $revenue = (float) ($saleStats->revenue ?? 0);
            $cogs = (float) ($saleStats->cogs ?? 0);
            $profit = $revenue - $cogs;

            if ($revenue > 0) {
                $customerProfits[] = [
                    'id' => $c->id,
                    'name' => $c->customer_name,
                    'profit' => $profit,
                    'revenue' => $revenue,
                    'balance' => $balanceService->getCustomerBalance($c->id)
                ];
            }
        }

        // Sort by profit descending
        usort($customerProfits, fn($a, $b) => $b['profit'] <=> $a['profit']);
        $topCustomers = array_slice($customerProfits, 0, 10);

        return response()->json([
            'sales' => [
                'today' => $salesToday,
                'month' => $salesMonth,
            ],
            'purchases' => [
                'today' => $purchasesToday,
                'month' => $purchasesMonth,
            ],
            'expenses' => [
                'today' => $expensesToday,
                'month' => $expensesMonth,
            ],
            'accounts' => [
                'cash' => $cashAccounts,
                'bank' => $bankAccounts,
            ],
            'receivables' => $totalReceivables,
            'payables' => $totalPayables,
            'top_customers' => $topCustomers,
        ]);
    }

    private function getPurchasedQtyAndNetAmount(int $productId, $dateFilter = [], $warehouseId = null): array
    {
        $query = DB::table('purchase_items')
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->where('purchase_items.product_id', $productId)
            ->whereIn('purchases.status_purchase', ['approved', 'posted', 'Returned', 'Partial']);

        if ($warehouseId && $warehouseId !== 'all') {
            $query->where('purchases.warehouse_id', $warehouseId);
        }

        if (is_array($dateFilter) && !empty($dateFilter)) {
            if (isset($dateFilter['before'])) {
                $query->where('purchases.purchase_date', '<=', $dateFilter['before']);
            }
            if (isset($dateFilter['from']) && !empty($dateFilter['from'])) {
                $query->whereDate('purchases.created_at', '>=', $dateFilter['from']);
            }
            if (isset($dateFilter['to']) && !empty($dateFilter['to'])) {
                $query->whereDate('purchases.created_at', '<=', $dateFilter['to']);
            }
        }

        $result = $query->select(DB::raw("
            COALESCE(SUM(
                CASE
                    WHEN LOWER(purchase_items.unit) IN ('carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl')
                    THEN purchase_items.qty * COALESCE(NULLIF(purchase_items.pieces_per_box, 0), 1)
                    WHEN LOWER(purchase_items.unit) IN ('gm', 'g')
                    THEN purchase_items.qty / 1000.0
                    ELSE purchase_items.qty
                END
            ), 0) as total_qty,
            COALESCE(SUM(
                CASE
                    WHEN COALESCE(purchases.subtotal, 0) > 0
                    THEN purchase_items.line_total / purchases.subtotal * purchases.net_amount
                    ELSE purchase_items.line_total
                END
            ), 0) as total_net_amount
        "))->first();

        return [(float) $result->total_qty, (float) $result->total_net_amount];
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
     * Match a stock adjustment note to a specific variant based on size and color.
     */
    private function matchAdjustmentToVariant($adjItem, $variant)
    {
        if (isset($adjItem->ref_type) && in_array($adjItem->ref_type, ['INIT', 'OPENING_STOCK'])) {
            return false;
        }

        $note = strtolower($adjItem->note ?? '');
        if (empty($note) || $note === 'initial stock' || str_contains($note, 'opening stock')) {
            return false;
        }

        $vSize  = strtolower(trim($variant['size'] ?? '-'));
        $vColor = strtolower(trim($variant['color'] ?? '-'));
        $vName  = strtolower(trim($variant['name'] ?? ''));

        $sizeMatch = true;
        if ($vSize !== '-' && !empty($vSize)) {
            $pattern = '/\b' . preg_quote($vSize, '/') . '\b/i';
            $sizeMatch = preg_match($pattern, $note) === 1;
        }

        $colorMatch = true;
        if ($vColor !== '-' && !empty($vColor)) {
            $pattern = '/\b' . preg_quote($vColor, '/') . '\b/i';
            $colorMatch = preg_match($pattern, $note) === 1;
        }

        return $sizeMatch && $colorMatch;
    }

    /**
     * Product Sale Customer Wise Report View
     */
    public function product_sale_customer_wise_report(Request $request)
    {
        $customers  = \App\Models\Customer::orderBy('customer_name')->get();
        $products   = \App\Models\Product::orderBy('item_name')->get();
        $categories = \App\Models\Category::orderBy('name')->get();
        $brands     = \App\Models\Brand::orderBy('name')->get();

        return view('admin_panel.reporting.product_sale_customer_wise_report', compact('customers', 'products', 'categories', 'brands'));
    }

    /**
     * Fetch Product Sale Customer Wise Report Data (AJAX with Multi-Select)
     */
    public function fetchProductSaleCustomerWise(Request $request)
    {
        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        // Normalize filters into arrays for Multi-Select support
        $customerIds = is_array($request->customer_id) ? $request->customer_id : ($request->customer_id && $request->customer_id !== 'all' ? [$request->customer_id] : []);
        $productIds  = is_array($request->product_id) ? $request->product_id : ($request->product_id && $request->product_id !== 'all' ? [$request->product_id] : []);
        $categoryIds = is_array($request->category_id) ? $request->category_id : ($request->category_id && $request->category_id !== 'all' ? [$request->category_id] : []);
        $brandIds    = is_array($request->brand_id) ? $request->brand_id : ($request->brand_id && $request->brand_id !== 'all' ? [$request->brand_id] : []);

        // Filter out 'all' values
        $customerIds = array_filter($customerIds, fn($v) => $v !== 'all' && !empty($v));
        $productIds  = array_filter($productIds, fn($v) => $v !== 'all' && !empty($v));
        $categoryIds = array_filter($categoryIds, fn($v) => $v !== 'all' && !empty($v));
        $brandIds    = array_filter($brandIds, fn($v) => $v !== 'all' && !empty($v));

        // 1. Query Sales Items
        $salesQuery = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'sales.customer_id',
                DB::raw("COALESCE(customers.customer_name, 'Walking Customer') as customer_name"),
                DB::raw("COALESCE(customers.customer_id, 'N/A') as customer_code"),
                'customers.mobile as customer_mobile',
                'customers.province as customer_province',
                'customers.city as customer_city',
                'customers.area as customer_area',
                'sale_items.product_id',
                DB::raw("COALESCE(products.item_code, 'MANUAL') as product_code"),
                DB::raw("COALESCE(sale_items.product_name, products.item_name) as product_name"),
                DB::raw("COALESCE(brands.name, '-') as brand_name"),
                DB::raw("COALESCE(categories.name, '-') as category_name"),
                DB::raw("SUM(sale_items.total_pieces) as sold_qty"),
                DB::raw("SUM(sale_items.total) as gross_amount"),
                DB::raw("COUNT(DISTINCT sales.id) as invoice_count")
            )
            ->whereIn('sales.sale_status', ['posted', 'completed', 'booked', 'draft']);

        if ($fromDate) {
            $salesQuery->whereDate('sales.created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $salesQuery->whereDate('sales.created_at', '<=', $toDate);
        }
        if (!empty($customerIds)) {
            $salesQuery->whereIn('sales.customer_id', $customerIds);
        }
        if (!empty($productIds)) {
            $salesQuery->whereIn('sale_items.product_id', $productIds);
        }
        if (!empty($categoryIds)) {
            $salesQuery->whereIn('products.category_id', $categoryIds);
        }
        if (!empty($brandIds)) {
            $salesQuery->whereIn('products.brand_id', $brandIds);
        }

        $pLoc = $request->province;
        $cLoc = $request->city;
        $aLoc = $request->area;

        if ($pLoc && $pLoc !== 'all') {
            $salesQuery->where('customers.province', $pLoc);
        }
        if ($cLoc && $cLoc !== 'all') {
            $salesQuery->where('customers.city', $cLoc);
        }
        if ($aLoc && $aLoc !== 'all') {
            $salesQuery->where('customers.area', $aLoc);
        }

        $salesData = $salesQuery->groupBy(
            'sales.customer_id',
            'customers.customer_name',
            'customers.customer_id',
            'customers.mobile',
            'customers.province',
            'customers.city',
            'customers.area',
            'sale_items.product_id',
            'products.item_code',
            'sale_items.product_name',
            'products.item_name',
            'brands.name',
            'categories.name'
        )->get();

        // 2. Query Returns Data
        $returnQuery = DB::table('sale_return_items')
            ->join('sale_returns', 'sale_return_items.sale_return_id', '=', 'sale_returns.id')
            ->select(
                'sale_returns.customer_id',
                'sale_return_items.product_id',
                DB::raw("SUM(sale_return_items.qty) as return_qty"),
                DB::raw("SUM(sale_return_items.line_total) as return_amount")
            )
            ->whereIn('sale_returns.status', ['approved', 'posted', 'completed']);

        if ($fromDate) {
            $returnQuery->whereDate('sale_returns.created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $returnQuery->whereDate('sale_returns.created_at', '<=', $toDate);
        }
        if (!empty($customerIds)) {
            $returnQuery->whereIn('sale_returns.customer_id', $customerIds);
        }
        if (!empty($productIds)) {
            $returnQuery->whereIn('sale_return_items.product_id', $productIds);
        }

        if (($pLoc && $pLoc !== 'all') || ($cLoc && $cLoc !== 'all') || ($aLoc && $aLoc !== 'all')) {
            $returnQuery->join('customers', 'sale_returns.customer_id', '=', 'customers.id');
            if ($pLoc && $pLoc !== 'all') $returnQuery->where('customers.province', $pLoc);
            if ($cLoc && $cLoc !== 'all') $returnQuery->where('customers.city', $cLoc);
            if ($aLoc && $aLoc !== 'all') $returnQuery->where('customers.area', $aLoc);
        }

        $returnsData = $returnQuery->groupBy('sale_returns.customer_id', 'sale_return_items.product_id')->get();

        // Build Lookup key for returns: "cust_id_prod_id"
        $returnsLookup = [];
        foreach ($returnsData as $r) {
            $key = ($r->customer_id ?? 0) . '_' . ($r->product_id ?? 0);
            $returnsLookup[$key] = [
                'return_qty' => (float) $r->return_qty,
                'return_amount' => (float) $r->return_amount,
            ];
        }

        // 3. Group by Customer
        $grouped = [];
        $totalInvoicesCount = 0;
        $totalSoldQty = 0;
        $totalReturnQty = 0;
        $totalNetQty = 0;
        $totalGrossAmount = 0;
        $totalReturnAmount = 0;
        $totalNetSaleAmount = 0;

        foreach ($salesData as $row) {
            $custId = $row->customer_id ?? 0;
            $custName = $row->customer_name ?: 'Walking Customer';

            if (!isset($grouped[$custId])) {
                $grouped[$custId] = [
                    'customer_id' => $custId,
                    'customer_name' => $custName,
                    'customer_code' => $row->customer_code,
                    'customer_mobile' => $row->customer_mobile ?: '-',
                    'customer_province' => $row->customer_province ?: '-',
                    'customer_city' => $row->customer_city ?: '-',
                    'customer_area' => $row->customer_area ?: '-',
                    'gross_amount' => 0,
                    'return_amount' => 0,
                    'net_amount' => 0,
                    'sold_qty' => 0,
                    'return_qty' => 0,
                    'net_qty' => 0,
                    'products' => [],
                ];
            }

            $prodId = $row->product_id ?? 0;
            $lookupKey = $custId . '_' . $prodId;
            $retQty = isset($returnsLookup[$lookupKey]) ? $returnsLookup[$lookupKey]['return_qty'] : 0;
            $retAmt = isset($returnsLookup[$lookupKey]) ? $returnsLookup[$lookupKey]['return_amount'] : 0;

            $soldQty = (float) $row->sold_qty;
            $grossAmt = (float) $row->gross_amount;
            $netQty = $soldQty - $retQty;
            $netAmt = $grossAmt - $retAmt;
            $avgPrice = $soldQty > 0 ? ($grossAmt / $soldQty) : 0;

            $grouped[$custId]['products'][] = [
                'product_id' => $prodId,
                'product_code' => $row->product_code,
                'product_name' => $row->product_name,
                'brand_name' => $row->brand_name,
                'category_name' => $row->category_name,
                'sold_qty' => $soldQty,
                'avg_price' => $avgPrice,
                'gross_amount' => $grossAmt,
                'return_qty' => $retQty,
                'return_amount' => $retAmt,
                'net_qty' => $netQty,
                'net_amount' => $netAmt,
            ];

            $grouped[$custId]['gross_amount'] += $grossAmt;
            $grouped[$custId]['return_amount'] += $retAmt;
            $grouped[$custId]['net_amount'] += $netAmt;
            $grouped[$custId]['sold_qty'] += $soldQty;
            $grouped[$custId]['return_qty'] += $retQty;
            $grouped[$custId]['net_qty'] += $netQty;

            $totalSoldQty += $soldQty;
            $totalReturnQty += $retQty;
            $totalNetQty += $netQty;
            $totalGrossAmount += $grossAmt;
            $totalReturnAmount += $retAmt;
            $totalNetSaleAmount += $netAmt;
            $totalInvoicesCount += (int) $row->invoice_count;
        }

        $totalCustomersCount = count($grouped);

        $summary = [
            'total_customers' => $totalCustomersCount,
            'total_invoices' => $totalInvoicesCount,
            'total_qty' => number_format($totalSoldQty, 2),
            'total_return_qty' => number_format($totalReturnQty, 2),
            'net_qty' => number_format($totalNetQty, 2),
            'gross_amount' => 'Rs ' . number_format($totalGrossAmount, 2),
            'return_amount' => 'Rs ' . number_format($totalReturnAmount, 2),
            'net_sale_amount' => 'Rs ' . number_format($totalNetSaleAmount, 2),
        ];

        return response()->json([
            'summary' => $summary,
            'customers' => array_values($grouped)
        ]);
    }

    /**
     * Sales Officer Wise Sale Report
     */
    public function sales_officer_sale_report(Request $request)
    {
        $salesOfficers = \App\Models\SalesOfficer::orderBy('name')->get();
        $customers = \App\Models\Customer::orderBy('customer_name')->get();
        return view('admin_panel.reporting.sales_officer_sale_report', compact('salesOfficers', 'customers'));
    }

    /**
     * Fetch Sales Officer Wise Sale Report (Detailed & Summary)
     */
    public function fetchSalesOfficerSaleReport(Request $request)
    {
        $start = $request->start_date;
        $end = $request->end_date;
        $salesOfficerId = $request->sales_officer_id;
        $customerId = $request->customer_id;

        $query = \App\Models\Sale::with(['customer_relation.salesOfficer', 'salesOfficer', 'items.product', 'returns.items'])
            ->whereNotIn('sale_status', ['cancelled']);

        $pSO = $request->province;
        $cSO = $request->city;
        $aSO = $request->area;
        if ($pSO && $pSO !== 'all') {
            $query->whereHas('customer_relation', fn($q) => $q->where('province', $pSO));
        }
        if ($cSO && $cSO !== 'all') {
            $query->whereHas('customer_relation', fn($q) => $q->where('city', $cSO));
        }
        if ($aSO && $aSO !== 'all') {
            $query->whereHas('customer_relation', fn($q) => $q->where('area', $aSO));
        }

        if (!empty($start)) {
            $query->whereDate('created_at', '>=', $start);
        }
        if (!empty($end)) {
            $query->whereDate('created_at', '<=', $end);
        }

        if (!empty($customerId) && $customerId !== 'all') {
            $query->where('customer_id', $customerId);
        }

        if (!empty($salesOfficerId) && $salesOfficerId !== 'all') {
            $query->where(function($q) use ($salesOfficerId) {
                $q->where('sales_officer_id', $salesOfficerId)
                  ->orWhere(function($sub) use ($salesOfficerId) {
                      $sub->where(function($nullQ) {
                          $nullQ->whereNull('sales_officer_id')
                                ->orWhere('sales_officer_id', 0)
                                ->orWhere('sales_officer_id', '');
                      })->whereHas('customer_relation', function($custQ) use ($salesOfficerId) {
                          $custQ->where('sales_officer_id', $salesOfficerId);
                      });
                  });
            });
        }

        $sales = $query->orderBy('created_at', 'desc')->get();

        // Group summary by Sales Officer
        $officerSummary = [];
        $transformed = [];

        foreach ($sales as $sale) {
            $officerObj = null;
            if (!empty($sale->sales_officer_id) && $sale->salesOfficer) {
                $officerObj = $sale->salesOfficer;
            } elseif ($sale->customer_relation && $sale->customer_relation->salesOfficer) {
                $officerObj = $sale->customer_relation->salesOfficer;
            }

            $officerId = $officerObj ? (int)$officerObj->id : 0;
            $officerName = $officerObj ? $officerObj->name : 'Direct / Unassigned';
            $commissionRate = $officerObj ? (float)($officerObj->commission_rate ?? 0) : 0.00;

            $gross = (float)($sale->total_gross > 0 ? $sale->total_gross : $sale->total_bill_amount);
            $inlineDisc = 0;
            $totalQty = 0;
            $itemsList = [];

            foreach ($sale->items as $it) {
                $inlineDisc += (float) ($it->discount_amount ?? 0);
                $itQty = (float) ($it->qty_box ?? $it->qty ?? $it->total_pieces ?? 0);
                $totalQty += $itQty;
                $pName = $it->product ? $it->product->item_name : ($it->is_manual ? $it->product_name : 'Item');
                $itemsList[] = $pName . ' (' . $itQty . ')';
            }

            $addDisc = (float) ($sale->total_extradiscount ?? 0);
            $totalDiscount = $inlineDisc + $addDisc;

            if ($gross <= 0) {
                $gross = (float) $sale->total_net + $totalDiscount;
            }

            $billNet = (float) ($sale->total_net ?? ($gross - $totalDiscount));

            // Return calculation
            $retAmount = 0;
            if ($sale->returns && $sale->returns->count() > 0) {
                $retAmount = (float) $sale->returns->sum('net_amount');
            }
            if ($retAmount <= 0) {
                $linkedReturn = \App\Models\SaleReturn::where('remarks', 'LIKE', '%Invoice #'.$sale->invoice_no.'%')->first();
                if ($linkedReturn) {
                    $retAmount = (float) ($linkedReturn->net_amount ?? $linkedReturn->items->sum('line_total'));
                }
            }

            // Net Realized Sale = Bill Net - Return Amount
            $netRealized = max(0, $billNet - $retAmount);

            // Commission amount on realized net sale
            $commAmount = ($netRealized * $commissionRate) / 100;

            if (!isset($officerSummary[$officerId])) {
                $officerSummary[$officerId] = [
                    'officer_id' => $officerId,
                    'officer_name' => $officerName,
                    'commission_rate' => $commissionRate,
                    'total_invoices' => 0,
                    'total_qty' => 0,
                    'total_gross' => 0,
                    'total_discount' => 0,
                    'total_returns' => 0,
                    'total_net' => 0,
                    'commission_amount' => 0,
                ];
            }

            $officerSummary[$officerId]['total_invoices'] += 1;
            $officerSummary[$officerId]['total_qty'] += $totalQty;
            $officerSummary[$officerId]['total_gross'] += $gross;
            $officerSummary[$officerId]['total_discount'] += $totalDiscount;
            $officerSummary[$officerId]['total_returns'] += $retAmount;
            $officerSummary[$officerId]['total_net'] += $netRealized;
            $officerSummary[$officerId]['commission_amount'] += $commAmount;

            $transformed[] = [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'created_at_formatted' => $sale->created_at ? $sale->created_at->format('d/m/Y h:i A') : '',
                'customer_name' => $sale->customer_relation ? $sale->customer_relation->customer_name : ($sale->walkin_name ?: 'Walk-in'),
                'customer_province' => $sale->customer_relation->province ?? '',
                'customer_city' => $sale->customer_relation->city ?? '',
                'customer_area' => $sale->customer_relation->area ?? '',
                'customer_code' => $sale->customer_relation->customer_id ?? '',
                'sales_officer_id' => $officerId,
                'sales_officer_name' => $officerName,
                'po_no' => $sale->po_no ?? '',
                'po_date' => $sale->po_date ? \Carbon\Carbon::parse($sale->po_date)->format('d/m/Y') : '',
                'reference' => $sale->reference ?? '',
                'status' => $sale->sale_status,
                'products' => implode(', ', $itemsList),
                'total_qty' => $totalQty,
                'gross_amount' => $gross,
                'discount' => $totalDiscount,
                'returns' => $retAmount,
                'net_amount' => $netRealized,
            ];
        }

        return response()->json([
            'sales' => $transformed,
            'summary' => array_values($officerSummary),
            'totals' => [
                'total_sales' => count($transformed),
                'total_qty' => array_sum(array_column($officerSummary, 'total_qty')),
                'total_gross' => array_sum(array_column($officerSummary, 'total_gross')),
                'total_discount' => array_sum(array_column($officerSummary, 'total_discount')),
                'total_returns' => array_sum(array_column($officerSummary, 'total_returns')),
                'total_net' => array_sum(array_column($officerSummary, 'total_net')),
                'total_commission' => array_sum(array_column($officerSummary, 'commission_amount')),
            ]
        ]);
    }

    /**
     * Sale Return Detailed Report View
     */
    public function sale_return_report(Request $request)
    {
        $customers  = \App\Models\Customer::orderBy('customer_name')->get();
        $products   = \App\Models\Product::orderBy('item_name')->get();
        $categories = \App\Models\Category::orderBy('name')->get();
        $brands     = \App\Models\Brand::orderBy('name')->get();
        $warehouses = \App\Models\Warehouse::orderBy('warehouse_name')->get();

        return view('admin_panel.reporting.sale_return_report', compact('customers', 'products', 'categories', 'brands', 'warehouses'));
    }

    /**
     * Fetch Sale Return Detailed Report Data (AJAX)
     */
    public function fetchSaleReturnReport(Request $request)
    {
        $fromDate = $request->from_date;
        $toDate   = $request->to_date;

        // Multi-select filters
        $customerIds = is_array($request->customer_id) ? $request->customer_id : ($request->customer_id && $request->customer_id !== 'all' ? [$request->customer_id] : []);
        $productIds  = is_array($request->product_id) ? $request->product_id : ($request->product_id && $request->product_id !== 'all' ? [$request->product_id] : []);
        $categoryIds = is_array($request->category_id) ? $request->category_id : ($request->category_id && $request->category_id !== 'all' ? [$request->category_id] : []);
        $brandIds    = is_array($request->brand_id) ? $request->brand_id : ($request->brand_id && $request->brand_id !== 'all' ? [$request->brand_id] : []);
        $warehouseId = $request->warehouse_id;

        $customerIds = array_values(array_filter($customerIds, fn($v) => $v !== 'all' && !empty($v)));
        $productIds  = array_values(array_filter($productIds, fn($v) => $v !== 'all' && !empty($v)));
        $categoryIds = array_values(array_filter($categoryIds, fn($v) => $v !== 'all' && !empty($v)));
        $brandIds    = array_values(array_filter($brandIds, fn($v) => $v !== 'all' && !empty($v)));

        $query = DB::table('sale_return_items as sri')
            ->join('sale_returns as sr', 'sri.sale_return_id', '=', 'sr.id')
            ->leftJoin('customers as c', 'sr.customer_id', '=', 'c.id')
            ->leftJoin('products as p', 'sri.product_id', '=', 'p.id')
            ->leftJoin('sales as s', 'sr.sale_id', '=', 's.id')
            ->leftJoin('warehouses as w', 'sri.warehouse_id', '=', 'w.id')
            ->leftJoin('brands as b', 'p.brand_id', '=', 'b.id')
            ->leftJoin('categories as cat', 'p.category_id', '=', 'cat.id')
            ->select(
                'sri.id as item_id',
                'sr.id as return_id',
                'sr.return_invoice',
                'sr.return_date',
                'sr.created_at as return_created_at',
                'sr.sale_id',
                's.invoice_no as original_invoice_no',
                'sr.customer_id',
                DB::raw("COALESCE(c.customer_name, 'Walking Customer') as customer_name"),
                DB::raw("COALESCE(c.customer_id, 'N/A') as customer_code"),
                'c.mobile as customer_mobile',
                'c.province as customer_province',
                'c.city as customer_city',
                'c.area as customer_area',
                'sri.product_id',
                'sri.is_manual',
                DB::raw("COALESCE(sri.product_name, p.item_name, 'Manual Item') as raw_product_name"),
                DB::raw("COALESCE(p.item_code, 'MANUAL') as product_code"),
                'sri.color as variant_data',
                'sri.qty as returned_qty',
                'sri.boxes as returned_boxes',
                'sri.loose_pieces as returned_loose',
                'sri.unit',
                'sri.price as unit_price',
                'sri.item_discount',
                'sri.line_total',
                'sr.remarks as return_remarks',
                'sr.status as return_status',
                DB::raw("COALESCE(w.warehouse_name, '-') as warehouse_name"),
                DB::raw("COALESCE(b.name, '-') as brand_name"),
                DB::raw("COALESCE(cat.name, '-') as category_name")
            );

        if ($fromDate) {
            $query->whereDate(DB::raw("COALESCE(sr.return_date, sr.created_at)"), '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate(DB::raw("COALESCE(sr.return_date, sr.created_at)"), '<=', $toDate);
        }
        if (!empty($customerIds)) {
            $query->whereIn('sr.customer_id', $customerIds);
        }
        if (!empty($productIds)) {
            $query->whereIn('sri.product_id', $productIds);
        }
        if (!empty($categoryIds)) {
            $query->whereIn('p.category_id', $categoryIds);
        }
        if (!empty($brandIds)) {
            $query->whereIn('p.brand_id', $brandIds);
        }
        if ($warehouseId && $warehouseId !== 'all') {
            $query->where(function($q) use ($warehouseId) {
                $q->where('sri.warehouse_id', $warehouseId)
                  ->orWhere('sr.warehouse_id', $warehouseId);
            });
        }

        $pLoc = $request->province;
        $cLoc = $request->city;
        $aLoc = $request->area;

        if ($pLoc && $pLoc !== 'all') {
            $query->where('c.province', $pLoc);
        }
        if ($cLoc && $cLoc !== 'all') {
            $query->where('c.city', $cLoc);
        }
        if ($aLoc && $aLoc !== 'all') {
            $query->where('c.area', $aLoc);
        }

        $records = $query->orderBy(DB::raw("COALESCE(sr.return_date, sr.created_at)"), 'desc')
                         ->orderBy('sr.id', 'desc')
                         ->get();

        $detailedItems = [];
        $customerGroups = [];
        $productGroups = [];

        $totalPieces = 0;
        $totalBoxes  = 0;
        $totalAmount = 0;
        $distinctInvoices  = [];
        $distinctCustomers = [];
        $distinctProducts  = [];

        foreach ($records as $r) {
            $returnDateFormatted = \Carbon\Carbon::parse($r->return_date ?: $r->return_created_at)->format('d M Y');
            $qtyPcs = (float) $r->returned_qty;
            $boxes  = (float) $r->returned_boxes;
            $loose  = (float) $r->returned_loose;
            $price  = (float) $r->unit_price;
            $disc   = (float) $r->item_discount;
            $total  = (float) $r->line_total;

            $totalPieces += $qtyPcs;
            $totalBoxes  += $boxes;
            $totalAmount += $total;

            $distinctInvoices[$r->return_id] = true;
            if ($r->customer_id) $distinctCustomers[$r->customer_id] = true;
            $prodKey = $r->product_id ?: ('m_' . md5($r->raw_product_name));
            $distinctProducts[$prodKey] = true;

            // Clean Variant Label Helper (no ugly empty separators)
            $variantLabel = '';
            if (!empty($r->variant_data)) {
                $b64 = base64_decode($r->variant_data, true);
                $vJson = ($b64 !== false && is_string($b64)) ? json_decode($b64, true) : json_decode($r->variant_data, true);
                if (is_array($vJson)) {
                    $vSize = $vJson['size'] ?? ($vJson['size_val'] ?? '');
                    $vColor = $vJson['color'] ?? ($vJson['color_val'] ?? '');
                    $vParts = array_filter([$vSize, $vColor], fn($v) => !empty($v) && trim($v) !== '-');
                    if (!empty($vParts)) {
                        $variantLabel = implode(' | ', $vParts);
                    }
                } elseif (is_string($r->variant_data) && trim($r->variant_data) !== '-' && trim($r->variant_data) !== '') {
                    $variantLabel = trim($r->variant_data);
                }
            }

            $displayProductName = $r->raw_product_name . ($variantLabel ? " ({$variantLabel})" : '');

            // 1. Detailed Row
            $detailedItems[] = [
                'item_id' => $r->item_id,
                'return_id' => $r->return_id,
                'return_invoice' => $r->return_invoice,
                'return_date' => $returnDateFormatted,
                'sale_id' => $r->sale_id,
                'original_invoice' => $r->original_invoice_no ?: '-',
                'customer_id' => $r->customer_id,
                'customer_code' => $r->customer_code,
                'customer_name' => $r->customer_name,
                'customer_mobile' => $r->customer_mobile ?: '-',
                'location' => trim(implode(', ', array_filter([$r->customer_area, $r->customer_city, $r->customer_province]))),
                'product_id' => $r->product_id,
                'product_code' => $r->product_code,
                'product_name' => $displayProductName,
                'raw_name' => $r->raw_product_name,
                'variant_label' => $variantLabel,
                'brand_name' => $r->brand_name,
                'category_name' => $r->category_name,
                'warehouse_name' => $r->warehouse_name,
                'qty_pcs' => $qtyPcs,
                'boxes' => $boxes,
                'loose' => $loose,
                'unit' => $r->unit ?: 'Pcs',
                'unit_price' => $price,
                'item_discount' => $disc,
                'line_total' => $total,
                'remarks' => $r->return_remarks ?: '-',
                'status' => $r->return_status ?: 'completed',
            ];

            // 2. Customer Aggregates
            $cId = $r->customer_id ?: 0;
            if (!isset($customerGroups[$cId])) {
                $customerGroups[$cId] = [
                    'customer_id' => $cId,
                    'customer_code' => $r->customer_code,
                    'customer_name' => $r->customer_name,
                    'customer_mobile' => $r->customer_mobile ?: '-',
                    'location' => trim(implode(', ', array_filter([$r->customer_area, $r->customer_city, $r->customer_province]))),
                    'invoices' => [],
                    'total_invoices_count' => 0,
                    'total_pieces' => 0,
                    'total_boxes' => 0,
                    'total_amount' => 0,
                    'items' => [],
                ];
            }
            $customerGroups[$cId]['invoices'][$r->return_id] = true;
            $customerGroups[$cId]['total_pieces'] += $qtyPcs;
            $customerGroups[$cId]['total_boxes']  += $boxes;
            $customerGroups[$cId]['total_amount'] += $total;
            $customerGroups[$cId]['items'][] = [
                'return_invoice' => $r->return_invoice,
                'return_date' => $returnDateFormatted,
                'product_name' => $displayProductName,
                'qty_pcs' => $qtyPcs,
                'unit_price' => $price,
                'line_total' => $total,
                'warehouse' => $r->warehouse_name,
                'remarks' => $r->return_remarks ?: '-'
            ];

            // 3. Product Aggregates
            $pKey = $r->product_id ? ($r->product_id . '_' . $variantLabel) : ('m_' . md5($displayProductName));
            if (!isset($productGroups[$pKey])) {
                $productGroups[$pKey] = [
                    'product_id' => $r->product_id,
                    'product_code' => $r->product_code,
                    'product_name' => $displayProductName,
                    'base_name' => $r->raw_product_name,
                    'variant_label' => $variantLabel,
                    'brand_name' => $r->brand_name,
                    'category_name' => $r->category_name,
                    'invoices' => [],
                    'total_invoices_count' => 0,
                    'total_pieces' => 0,
                    'total_boxes' => 0,
                    'total_amount' => 0,
                    'customers' => [],
                ];
            }
            $productGroups[$pKey]['invoices'][$r->return_id] = true;
            $productGroups[$pKey]['total_pieces'] += $qtyPcs;
            $productGroups[$pKey]['total_boxes']  += $boxes;
            $productGroups[$pKey]['total_amount'] += $total;

            $custName = $r->customer_name;
            if (!isset($productGroups[$pKey]['customers'][$custName])) {
                $productGroups[$pKey]['customers'][$custName] = 0;
            }
            $productGroups[$pKey]['customers'][$custName] += $qtyPcs;
        }

        // Finalize Customer Summaries
        $customerSummaryList = [];
        foreach ($customerGroups as $cg) {
            $cg['total_invoices_count'] = count($cg['invoices']);
            unset($cg['invoices']);
            $customerSummaryList[] = $cg;
        }
        usort($customerSummaryList, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        // Finalize Product Summaries
        $productSummaryList = [];
        foreach ($productGroups as $pg) {
            $pg['total_invoices_count'] = count($pg['invoices']);
            unset($pg['invoices']);
            $pg['avg_price'] = $pg['total_pieces'] > 0 ? round($pg['total_amount'] / $pg['total_pieces'], 2) : 0;

            // Transform customer list to formatted string
            $custArr = [];
            foreach ($pg['customers'] as $cName => $cQty) {
                $custArr[] = "{$cName} ({$cQty})";
            }
            $pg['customers_display'] = implode(', ', $custArr);
            unset($pg['customers']);

            $productSummaryList[] = $pg;
        }
        usort($productSummaryList, fn($a, $b) => $b['total_amount'] <=> $a['total_amount']);

        return response()->json([
            'success' => true,
            'detailed_items' => $detailedItems,
            'customer_summary' => $customerSummaryList,
            'product_summary' => $productSummaryList,
            'summary' => [
                'total_returns' => count($distinctInvoices),
                'total_customers' => count($distinctCustomers),
                'total_products' => count($distinctProducts),
                'total_pieces' => number_format($totalPieces, 2),
                'total_boxes' => number_format($totalBoxes, 2),
                'total_amount' => number_format($totalAmount, 2),
                'raw_total_amount' => $totalAmount,
            ]
        ]);
    }

}
