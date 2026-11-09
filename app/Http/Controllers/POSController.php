<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Customer;
use App\Models\Product;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class POSController extends Controller
{
    public function index(Request $request = null)
    {
        // 1. Fetch all active products
        $products = Product::where('is_active', true)->with('warehouseStocks')->get();

        $posProducts = [];
        foreach ($products as $p) {
            $ppb = $p->pieces_per_box > 0 ? $p->pieces_per_box : 1;

            $variants = [];
            if ($p->color) {
                try {
                    $parsed = is_string($p->color) ? json_decode($p->color, true) : $p->color;
                    if (is_array($parsed) && count($parsed) > 0 && isset($parsed[0]['name'])) {
                        $variants = $parsed;
                    }
                } catch (\Exception $e) {}
            }

            if (count($variants) > 0) {
                // Fetch all sales, returns, purchases, and purchase returns for this product to distribute
                $salesList = DB::table('sale_items')
                    ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sale_items.product_id', $p->id)
                    ->whereIn('sales.sale_status', ['posted', 'returned'])
                    ->select('sale_items.total_pieces', 'sale_items.color')
                    ->get();

                // Fetch confirmed web sales
                $webSalesList = DB::table('ecommerce_order_items as eoi')
                    ->join('ecommerce_orders as eo', 'eo.id', '=', 'eoi.ecommerce_order_id')
                    ->where('eoi.product_id', $p->id)
                    ->where('eo.is_stock_deducted', 1)
                    ->select('eoi.quantity as total_pieces', 'eoi.color', 'eoi.size')
                    ->get();

                $salesListArray = $salesList->toArray();
                foreach ($webSalesList as $wItem) {
                    $salesListArray[] = (object) [
                        'total_pieces' => $wItem->total_pieces,
                        'color' => json_encode([
                            'color' => $wItem->color ?: '-',
                            'size' => $wItem->size ?: '-'
                        ])
                    ];
                }
                $salesList = collect($salesListArray);

                $returnsList = DB::table('sale_return_items as sri')
                    ->join('sale_returns as sr', 'sr.id', '=', 'sri.sale_return_id')
                    ->where('sri.product_id', $p->id)
                    ->select('sri.qty', 'sri.color', 'sr.sale_id')
                    ->get();

                $saleIds = $returnsList->pluck('sale_id')->unique()->toArray();
                $saleItemsMap = [];
                if (!empty($saleIds)) {
                    $siList = DB::table('sale_items')
                        ->whereIn('sale_id', $saleIds)
                        ->where('product_id', $p->id)
                        ->select('sale_id', 'color')
                        ->get();
                    foreach ($siList as $si) {
                        $saleItemsMap[$si->sale_id][] = $si->color;
                    }
                }

                $purchasesList = DB::table('purchase_items as pi')
                    ->join('purchases as pur', 'pur.id', '=', 'pi.purchase_id')
                    ->where('pi.product_id', $p->id)
                    ->whereIn('pur.status_purchase', ['approved', 'Returned', 'Partial'])
                    ->select('pi.qty', 'pi.unit', 'pi.pieces_per_box', 'pi.boxes_qty', 'pi.loose_qty', 'pi.color')
                    ->get();

                $purchaseReturnsList = DB::table('purchase_return_items as pri')
                    ->where('pri.product_id', $p->id)
                    ->select('pri.qty', 'pri.color')
                    ->get();

                $variantItems = [];
                $totalStockPieces = 0;

                foreach ($variants as $v) {
                    $size = (isset($v['size']) && $v['size'] !== '-') ? " {$v['size']}" : '';
                    $color = (isset($v['color']) && $v['color'] !== '-') ? " ({$v['color']})" : '';
                    $vName = ($v['name'] ?? $p->item_name) . $size . $color;
                    
                    $initial = (float) ($v['stock'] ?? 0);

                    // Calculate Purchased variant qty
                    $purchased = 0;
                    foreach ($purchasesList as $pItem) {
                        if ($this->matchSaleItemToVariant($pItem, $v)) {
                            $pUnit = strtolower(trim($pItem->unit ?? ''));
                            $itemPPB = (float) ($pItem->pieces_per_box > 0 ? $pItem->pieces_per_box : ($p->pieces_per_box ?? 1));
                            if ($itemPPB <= 0) $itemPPB = 1;

                            if (in_array($pUnit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl'])) {
                                if (isset($pItem->boxes_qty) && ($pItem->boxes_qty > 0 || $pItem->loose_qty > 0)) {
                                    $pPieces = (((int) $pItem->boxes_qty) * $itemPPB) + ((int) $pItem->loose_qty);
                                } else {
                                    [$b, $l] = \App\Http\Controllers\PurchaseController::parseCartonQty($pItem->qty);
                                    $pPieces = ($b * $itemPPB) + $l;
                                }
                            } elseif (in_array($pUnit, ['gm', 'g'])) {
                                $pPieces = ((float) $pItem->qty) / 1000.0;
                            } else {
                                $pPieces = (float) $pItem->qty;
                            }

                            $purchased += $pPieces;
                        }
                    }

                    // Calculate Purchase Returned variant qty
                    $pReturned = 0;
                    foreach ($purchaseReturnsList as $prItem) {
                        if ($this->matchSaleItemToVariant($prItem, $v)) {
                            $pReturned += (float) $prItem->qty;
                        }
                    }
                    
                    // Calculate Sold variant qty
                    $sold = 0;
                    foreach ($salesList as $sItem) {
                        if ($this->matchSaleItemToVariant($sItem, $v)) {
                            $sold += (float) $sItem->total_pieces;
                        }
                    }

                    // Calculate Returned variant qty
                    $returnedQty = 0;
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
                            $returnedQty += (float) $rItem->qty;
                        }
                    }

                    if (isset($v['conv_factor']) && $p->size_mode === 'by_kg') {
                        $factor = (float) $v['conv_factor'];
                        $factor = $factor > 0 ? $factor : 1;
                        if ($factor == 1) {
                            $vBalance = max(0, $totalStockPieces);
                        } else {
                            $vBalance = (int) floor(max(0, $totalStockPieces) / $factor);
                        }
                    } else {
                        $vBalance = max(0, $initial + $purchased - $sold + $returnedQty - $pReturned);
                        $totalStockPieces += $vBalance;
                    }

                    $vStockDisplay = $vBalance;
                    if (isset($v['conv_factor']) && $p->size_mode === 'by_kg') {
                        $factor = (float) $v['conv_factor'];
                        if ($factor == 1) {
                            if ($vBalance < 0.001) {
                                $vStockDisplay = "0 Kg (0 Gm)";
                            } elseif ($vBalance < 1 && $vBalance > 0) {
                                $gmVal = (int) round($vBalance * 1000);
                                $vStockDisplay = "{$vBalance} Kg ({$gmVal} Gm)";
                            } else {
                                $vStockDisplay = "{$vBalance} Kg";
                            }
                        } else {
                            $pcsCount = (int) floor($vBalance);
                            $vStockDisplay = "{$pcsCount}";
                        }
                    } elseif ((in_array($p->size_mode, ['by_cartons', 'by_bandal']) || $p->size_mode === 'by_size') && $ppb > 1) {
                        $vBoxes = floor($vBalance / $ppb);
                        $vLoose = $vBalance % $ppb;
                        $vStockDisplay = $vLoose > 0 ? "$vBoxes.$vLoose" : $vBoxes;
                    }

                    $v['current_stock'] = $vStockDisplay;
                    $variantJson = json_encode($v);

                    $variantItems[] = [
                        'id' => $p->id . '|variant|' . base64_encode($variantJson),
                        'name' => $vName,
                        'size_val' => $v['size'] ?? '-',
                        'color_val' => $v['color'] ?? '-',
                        'price' => $v['sale_price'] ?? $p->sale_price_per_piece ?? 0,
                        'wholesale_price' => $v['wholesale_price'] ?? $p->wholesale_price ?? 0,
                        'weight_per_piece' => $v['weight_per_piece'] ?? $p->weight_per_piece ?? 0,
                        'stock_pieces' => $vBalance,
                        'stock' => $vStockDisplay,
                        'variant_data' => base64_encode($variantJson)
                    ];
                }

                $totalStockDisplay = $totalStockPieces;
                if ((in_array($p->size_mode, ['by_cartons', 'by_bandal']) || $p->size_mode === 'by_size') && $ppb > 1) {
                    $boxes = floor($totalStockPieces / $ppb);
                    $loose = $totalStockPieces % $ppb;
                    $totalStockDisplay = $loose > 0 ? "$boxes.$loose" : $boxes;
                }

                $posProducts[] = [
                    'id' => $p->id,
                    'name' => $p->item_name,
                    'sku' => $p->item_code ?? '',
                    'stock' => $totalStockDisplay,
                    'stock_pieces' => $totalStockPieces,
                    'size_mode' => $p->size_mode,
                    'pieces_per_box' => $ppb,
                    'price' => $p->sale_price_per_piece ?? 0,
                    'wholesale_price' => $p->wholesale_price ?? 0,
                    'weight_per_piece' => $p->weight_per_piece ?? 0,
                    'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
                    'variants' => $variantItems,
                ];

            } else {
                $stockPieces = (float) ($p->warehouseStocks->sum('total_pieces') ?? 0);
                $stockDisplay = $stockPieces;
                if ((in_array($p->size_mode, ['by_cartons', 'by_bandal']) || $p->size_mode === 'by_size') && $ppb > 1) {
                    $boxes = floor($stockPieces / $ppb);
                    $loose = $stockPieces % $ppb;
                    $stockDisplay = $loose > 0 ? "$boxes.$loose" : $boxes;
                }

                $posProducts[] = [
                    'id' => $p->id,
                    'name' => $p->item_name,
                    'sku' => $p->item_code ?? '',
                    'stock' => $stockDisplay,
                    'stock_pieces' => $stockPieces,
                    'size_mode' => $p->size_mode,
                    'pieces_per_box' => $ppb,
                    'price' => $p->sale_price_per_piece ?? 0,
                    'wholesale_price' => $p->wholesale_price ?? 0,
                    'weight_per_piece' => $p->weight_per_piece ?? 0,
                    'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
                    'variants' => [],
                ];
            }
        }

        // 2. Fetch all customers
        $customers = Customer::orderBy('customer_name')->get();

        // 3. Fetch all Cash/Bank Accounts
        $cashAndBankHeads = AccountHead::whereIn('name', ['Cash', 'Bank'])->pluck('id');
        $accounts = Account::whereIn('head_id', $cashAndBankHeads)->where('status', 1)->orderBy('title')->get();

        // 4. Fetch all Vendors with balances (Instant Batch Query)
        $balanceService = app(\App\Services\BalanceService::class);
        $apId = $balanceService->getAccountsPayableId();
        $vendorBalances = \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('account_id', $apId)
            ->selectRaw('party_id, COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->groupBy('party_id')
            ->pluck('balance', 'party_id');

        $vendors = \App\Models\Vendor::orderBy('name')->get()->map(function($vendor) use ($vendorBalances) {
            $vendor->balance = (float) ($vendorBalances[$vendor->id] ?? 0);
            return $vendor;
        });

        // 5. Check if editing existing sale
        $editId = request()->query('edit_id');
        $editSaleData = null;
        if ($editId) {
            $sale = \App\Models\Sale::with('items.product')->find($editId);
            if ($sale) {
                $items = [];
                foreach ($sale->items as $item) {
                    $p = $item->product;
                    $sizeMode = $item->size_mode ?? ($p ? $p->size_mode : 'by_pieces');
                    $ppb = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : ($p ? ($p->pieces_per_box > 0 ? $p->pieces_per_box : 1) : 1));

                    $variantData = '';
                    $itemName = $p ? $p->item_name : 'Item';
                    if (!empty($item->color)) {
                        $decoded = base64_decode($item->color, true);
                        $vData = ($decoded !== false) ? json_decode($decoded, true) : json_decode($item->color, true);
                        if (!empty($vData) && is_array($vData)) {
                            $variantData = base64_encode(json_encode($vData));
                            $vColorName = $vData['color'] ?? '';
                            $vSizeName = $vData['size'] ?? '';
                            $vParts = [];
                            if ($vSizeName && $vSizeName !== '-') $vParts[] = $vSizeName;
                            if ($vColorName && $vColorName !== '-') $vParts[] = $vColorName;
                            if (!empty($vParts)) $itemName .= ' ' . implode(' | ', $vParts);
                        } else {
                            if (!empty($item->color) && trim($item->color) !== '-') { $itemName .= ' (' . $item->color . ')'; }
                        }
                    }

                    $itemId = $item->product_id;
                    if ($variantData) {
                        $itemId .= '|variant|' . $variantData;
                    }

                    $priceVal = (float) ($item->price ?? ($item->price_per_piece ?? 0));
                    $totalVal = (float) ($item->total ?? ($item->total_price ?? 0));
                    $discVal = (float) ($item->discount_amount ?? 0);
                    $qtyVal = (float) ($item->total_pieces > 0 ? $item->total_pieces : $item->qty);

                    $items[] = [
                        'id' => $itemId,
                        'product_id' => $item->product_id,
                        'name' => $itemName,
                        'price' => $priceVal,
                        'qty' => $qtyVal,
                        'size_mode' => $sizeMode,
                        'pieces_per_box' => $ppb,
                        'variant_data' => $variantData,
                        'item_discount' => $discVal,
                        'line_total' => $totalVal
                    ];
                }

                $paymentAccountId = null;
                $cashBankAccIds = $accounts->pluck('id')->toArray();
                if (!empty($cashBankAccIds)) {
                    $paymentEntry = \App\Models\JournalEntry::whereIn('account_id', $cashBankAccIds)
                        ->where(function($q) use ($sale) {
                            $q->where('description', 'like', '%Sale #' . $sale->invoice_no . '%')
                              ->orWhere('description', 'like', '%Sale %' . $sale->id . '%');
                        })
                        ->first();
                    if ($paymentEntry) {
                        $paymentAccountId = $paymentEntry->account_id;
                    }
                }
                if (!$paymentAccountId) {
                    $paymentAccountId = $accounts->first()->id ?? null;
                }

                $paidAmount = 0;
                if (!empty($sale->paid_amount) && (float)$sale->paid_amount > 0) {
                    $paidAmount = (float)$sale->paid_amount;
                } elseif (!empty($sale->cash) && (float)$sale->cash > 0) {
                    $paidAmount = (float) ($sale->cash - max(0, (float)$sale->change));
                } else {
                    $journalPaid = \App\Models\JournalEntry::whereIn('account_id', $cashBankAccIds)
                        ->where(function($q) use ($sale) {
                            $q->where('description', 'like', '%' . $sale->invoice_no . '%')
                              ->orWhere('description', 'like', '%Sale %' . $sale->id . '%');
                        })
                        ->sum('debit');
                    if ($journalPaid > 0) {
                        $paidAmount = (float) $journalPaid;
                    } else if ($sale->total_net > 0 && ($sale->sale_status === 'posted' || $sale->sale_status === null)) {
                        $paidAmount = (float) $sale->total_net;
                    }
                }

                $editSaleData = [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'customer_id' => $sale->customer_id,
                    'note' => $sale->note ?? '',
                    'discount' => (float) $sale->total_extradiscount,
                    'extra_cost' => (float) $sale->extra_cost,
                    'paid_amount' => $paidAmount,
                    'payment_account_id' => $paymentAccountId,
                    'items' => $items
                ];
            }
        }

        return view('admin_panel.pos.index', compact('posProducts', 'customers', 'accounts', 'vendors', 'editSaleData'));
    }

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

        // Compare name, color and size
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

    public function searchInvoice(Request $request)
    {
        $term = trim($request->input('invoice_no'));
        if (!$term) {
            return response()->json(['error' => 'Please enter an invoice number.'], 422);
        }

        $sale = \App\Models\Sale::with(['items.product'])
            ->where(function($q) use ($term) {
                $q->where('invoice_no', $term)
                  ->orWhere('id', $term);
            })
            ->whereIn('sale_status', ['posted', 'returned'])
            ->first();

        if (!$sale) {
            return response()->json(['error' => 'Invoice not found or is not posted.'], 404);
        }

        // Calculate already returned quantities
        $pastReturns = \App\Models\SaleReturn::where('sale_id', $sale->id)->with('items')->get();
        $returnedQtyMap = []; // Key: product_id + '_' + size + '_' + color
        foreach ($pastReturns as $sr) {
            foreach ($sr->items as $srItem) {
                $variant = [];
                if (!empty($srItem->color)) {
                    $b64Decoded = base64_decode($srItem->color, true);
                    $variant = $b64Decoded ? json_decode($b64Decoded, true) : json_decode($srItem->color, true);
                }
                $size = strtolower(trim($variant['size'] ?? ($variant['size_val'] ?? '-')));
                $color = strtolower(trim($variant['color'] ?? ($variant['color_val'] ?? '-')));
                $size = strtolower(trim($variant['size'] ?? ($variant['size_val'] ?? '-')));
                $color = strtolower(trim($variant['color'] ?? ($variant['color_val'] ?? '-')));
                
                // For past returns of manual items, product_id is null, so group by product_name
                if ($srItem->is_manual) {
                    $key = 'MANUAL_' . strtolower(trim($srItem->product_name));
                } else {
                    $key = $srItem->product_id . '_' . $size . '_' . $color;
                }

                if (!isset($returnedQtyMap[$key])) {
                    $returnedQtyMap[$key] = 0;
                }
                $returnedQtyMap[$key] += $srItem->qty; // total pieces returned
            }
        }

        $items = [];
        foreach ($sale->items as $item) {
            $variant = [];
            if (!empty($item->color)) {
                $b64Decoded = base64_decode($item->color, true);
                $variant = $b64Decoded ? json_decode($b64Decoded, true) : json_decode($item->color, true);
            }
            $size = strtolower(trim($variant['size'] ?? ($variant['size_val'] ?? '-')));
            $color = strtolower(trim($variant['color'] ?? ($variant['color_val'] ?? '-')));
            
            if ($item->is_manual) {
                $key = 'MANUAL_' . strtolower(trim($item->product_name));
            } else {
                $key = $item->product_id . '_' . $size . '_' . $color;
            }

            $alreadyReturned = $returnedQtyMap[$key] ?? 0;
            $maxReturnable = max(0, (float)$item->total_pieces - $alreadyReturned);

            if ($maxReturnable <= 0) {
                continue;
            }

            // Calculate net unit price (net of items discounts and overall invoice discount share)
            $lineTotal = (float)$item->total;
            $discountShare = 0;
            if ($sale->total_bill_amount > 0 && $sale->total_extradiscount > 0) {
                $proportion = $lineTotal / (float)$sale->total_bill_amount;
                $discountShare = $sale->total_extradiscount * $proportion;
            }
            
            $netTotal = max(0, $lineTotal - $discountShare);
            $netUnitPrice = $item->total_pieces > 0 ? ($netTotal / $item->total_pieces) : 0;

            $items[] = [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'is_manual' => $item->is_manual ? 1 : 0,
                'product_name' => $item->product_name,
                'sku' => $item->product->item_code ?? '',
                'size' => $variant['size'] ?? ($variant['size_val'] ?? '-'),
                'color' => $variant['color'] ?? ($variant['color_val'] ?? '-'),
                'variant_data' => $item->color,
                'qty_sold' => $item->total_pieces,
                'already_returned' => $alreadyReturned,
                'max_returnable' => $maxReturnable,
                'price_sold' => $item->price_per_piece ?? $item->price ?? 0,
                'net_unit_price' => round($netUnitPrice, 2),
                'discount_share_per_piece' => round($discountShare / ($item->total_pieces > 0 ? $item->total_pieces : 1), 2),
            ];
        }

        return response()->json([
            'sale_id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'customer_name' => $sale->walkin_name ?: ($sale->customer_relation->customer_name ?? 'Walking Customer'),
            'customer_id' => $sale->customer_id,
            'items' => $items
        ]);
    }
}
