<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountHead;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\PurchaseController;

class PurchasePOSController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where("is_active", true)->with("warehouseStocks")->get();

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

                    $vBalance = max(0, $initial + $purchased - $sold + $returnedQty - $pReturned);
                    $totalStockPieces += $vBalance;

                    $vStockDisplay = $vBalance;
                    if ((in_array($p->size_mode, ['by_cartons', 'by_bandal']) || $p->size_mode === 'by_size') && $ppb > 1) {
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
                        'price' => $v['purch_price'] ?? $p->purchase_price_per_piece ?? 0,
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
                    'price' => $p->purchase_price_per_piece ?? 0,
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
                    'price' => $p->purchase_price_per_piece ?? 0,
                    'wholesale_price' => $p->wholesale_price ?? 0,
                    'weight_per_piece' => $p->weight_per_piece ?? 0,
                    'image' => $p->image ? asset('uploads/products/'.$p->image) : null,
                    'variants' => [],
                ];
            }
        }

        $cashAndBankHeads = AccountHead::whereIn("name", ["Cash", "Bank"])->pluck("id");
        $accounts = Account::whereIn("head_id", $cashAndBankHeads)->where("status", 1)->orderBy("title")->get();

        $balanceService = app(\App\Services\BalanceService::class);
        $apId = $balanceService->getAccountsPayableId();
        $vendorBalances = \App\Models\JournalEntry::where('party_type', Vendor::class)
            ->where('account_id', $apId)
            ->selectRaw('party_id, COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->groupBy('party_id')
            ->pluck('balance', 'party_id');

        $vendors = Vendor::orderBy("name")->get()->map(function($vendor) use ($vendorBalances) {
            $vendor->balance = (float) ($vendorBalances[$vendor->id] ?? 0);
            return $vendor;
        });

        $customers = collect();

        // Check if editing existing purchase
        $editId = $request->query('edit_id');
        $editPurchaseData = null;
        if ($editId) {
            $purchase = Purchase::with('items.product')->find($editId);
            if ($purchase) {
                $items = [];
                foreach ($purchase->items as $item) {
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

                    $items[] = [
                        'id' => $itemId,
                        'product_id' => $item->product_id,
                        'name' => $itemName,
                        'price' => (float) $item->price,
                        'qty' => (float) $item->qty,
                        'size_mode' => $sizeMode,
                        'pieces_per_box' => $ppb,
                        'variant_data' => $variantData,
                        'item_discount' => (float) $item->item_discount,
                        'line_total' => (float) $item->line_total
                    ];
                }

                // Fetch payment account from JournalEntry for this purchase
                $paymentAccountId = null;
                $cashBankAccIds = $accounts->pluck('id')->toArray();
                if (!empty($cashBankAccIds)) {
                    $paymentEntry = \App\Models\JournalEntry::whereIn('account_id', $cashBankAccIds)
                        ->where(function($q) use ($purchase) {
                            $q->where('description', 'like', '%Purchase #' . $purchase->invoice_no . '%')
                              ->orWhere('description', 'like', '%Purchase %' . $purchase->id . '%');
                        })
                        ->first();
                    if ($paymentEntry) {
                        $paymentAccountId = $paymentEntry->account_id;
                    }
                }
                $paidAmount = (float) ($purchase->paid_amount ?? 0);
                if ($paidAmount <= 0) {
                    $journalPaid = \App\Models\JournalEntry::whereIn('account_id', $cashBankAccIds)
                        ->where(function($q) use ($purchase) {
                            $q->where('description', 'like', '%' . $purchase->invoice_no . '%')
                              ->orWhere('description', 'like', '%Purchase %' . $purchase->id . '%');
                        })
                        ->sum('credit');
                    if ($journalPaid > 0) {
                        $paidAmount = (float) $journalPaid;
                    } elseif ($purchase->total_net > 0 && strtolower($purchase->status_purchase) === 'approved') {
                        $paidAmount = (float) $purchase->total_net;
                    }
                }

                $editPurchaseData = [
                    'id' => $purchase->id,
                    'invoice_no' => $purchase->invoice_no,
                    'purchase_order_no' => $purchase->purchase_order_no ?? '',
                    'vendor_id' => $purchase->vendor_id,
                    'warehouse_id' => $purchase->warehouse_id,
                    'note' => $purchase->note ?? '',
                    'discount' => (float) $purchase->additional_discount,
                    'extra_cost' => (float) $purchase->extra_cost,
                    'paid_amount' => $paidAmount,
                    'payment_account_id' => $paymentAccountId,
                    'items' => $items
                ];
            }
        }

        return view("admin_panel.purchase_pos.index", compact("posProducts", "customers", "accounts", "vendors", "editPurchaseData"));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        
        $data["vendor_id"] = $request->input("Vendor") ?? $request->input("customer_id");
        $data["payment_amount"] = $request->input("payment_amount");
        $data["payment_account_id"] = $request->input("payment_account_id");
        $data["action"] = "post";
        $data["purchase_order_no"] = $request->input("purchase_order_no");
        
        // Map POS cart fields to Purchase fields
        $data["price"] = $request->input("price_per_piece");
        if (isset($data["warehouse_id"]) && is_array($data["warehouse_id"])) {
            $data["warehouse_id"] = $data["warehouse_id"][0] ?? 1;
        } else {
            $data["warehouse_id"] = 1;
        }

        $productIds = (array) $request->input("product_id", []);
        $data["unit"] = array_fill(0, count($productIds), "pieces");
        
        $itemDiscs = (array) $request->input("item_disc", []);
        $prices = (array) $data["price"];
        $qtys = (array) $request->input("qty", []);
        $itemDiscountPercentages = [];
        
        foreach ($productIds as $i => $pid) {
            $absDisc = (float) ($itemDiscs[$i] ?? 0);
            $gross = ((float) ($qtys[$i] ?? 0)) * ((float) ($prices[$i] ?? 0));
            $pct = $gross > 0 ? ($absDisc / $gross) * 100 : 0;
            $itemDiscountPercentages[] = $pct;
        }
        $data["item_discount"] = $itemDiscountPercentages;
        $data["loose_qty"] = $qtys;

        $newRequest = new Request($data);
        $newRequest->headers->set("X-Requested-With", "XMLHttpRequest");

        $purchaseController = app(PurchaseController::class);

        if ($request->filled('edit_id')) {
            $editId = $request->input('edit_id');
            $response = $purchaseController->update($newRequest, $editId);
            $pur = \App\Models\Purchase::find($editId);
            $invNo = ($pur && $pur->invoice_no) ? " #{$pur->invoice_no}" : " #{$editId}";
            return response()->json([
                "ok" => true,
                "message" => "Purchase Invoice{$invNo} Updated Successfully!",
                "invoice_url" => route('purchase.invoice', $editId),
            ]);
        }

        $response = $purchaseController->store($newRequest);

        $responseData = $response->getData(true);
        if (isset($responseData["success"]) && $responseData["success"]) {
            return response()->json([
                "ok" => true,
                "message" => "Purchase Processed Successfully!",
                "invoice_url" => $responseData["invoice_url"] ?? null,
            ]);
        }

        $errorMsg = $responseData["message"] ?? "Failed to process purchase.";
        if (isset($responseData["errors"])) {
            $errorMsg .= " | Details: " . json_encode($responseData["errors"]);
        }

        return response()->json([
            "ok" => false,
            "message" => $errorMsg,
        ]);
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
}
