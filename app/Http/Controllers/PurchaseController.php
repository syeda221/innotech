<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Inwardgatepass;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Stock;
use App\Models\Vendor;
use App\Models\VendorLedger;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /** Keep stocks table in sync for a (branch,warehouse,product) */
    /** Keep warehouse_stocks table in sync for a (warehouse,product) */
    public static function parseCartonQty($qty)
    {
        $qtyStr = (string) $qty;
        if (is_numeric($qtyStr)) {
            $qtyStr = rtrim(rtrim(sprintf('%.6f', (float) $qtyStr), '0'), '.');
        }
        $qtyStr = trim($qtyStr);
        if (str_starts_with($qtyStr, '.')) {
            $qtyStr = '0' . $qtyStr;
        }
        if (strpos($qtyStr, '.') !== false) {
            $parts = explode('.', $qtyStr);
            $boxes = (int) ($parts[0] ?? 0);
            $loose = (int) ($parts[1] ?? 0);
        } else {
            $boxes = (int) ($qtyStr ?: 0);
            $loose = 0;
        }
        return [$boxes, $loose];
    }

    private function upsertStocks(int $productId, float $qtyPiecesDelta, int $branchId, int $warehouseId): void
    {
        // We ignore $branchId as WarehouseStock is warehouse-specific
        $stock = \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        // Get Product Master Data for Box Calculation
        $product = Product::find($productId);
        $ppb = $product->pieces_per_box > 0 ? $product->pieces_per_box : 1;

        if ($stock) {
            // ✅ Add new pieces directly to existing balance to prevent precision/sync loss
            $stock->total_pieces += $qtyPiecesDelta;
            
            // Recalculate Boxes for display only
            $stock->quantity = $stock->total_pieces / $ppb;
            
            $stock->save();
        } else {
            \App\Models\WarehouseStock::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'total_pieces' => $qtyPiecesDelta,
                'quantity' => $qtyPiecesDelta / $ppb, // Initial Box Qty
                'price' => 0, // Should be fetched from Product or Purchase Item? 
            ]);
        }
    }

    public function index(Request $request)
    {
        $query = Purchase::with(['branch', 'warehouse', 'vendor', 'items', 'returns']);

        if ($request->has('status') && $request->status != 'all') {
            $query->where('status_purchase', $request->status);
        }

        // Apply Date Filters
        if ($request->filled('from_date')) {
            $query->whereDate('purchase_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('purchase_date', '<=', $request->to_date);
        }

        // Apply Mobile Number Filter
        if ($request->filled('mobile_no')) {
            $query->whereHas('vendor', function ($q) use ($request) {
                $q->where('mobile', 'like', "%{$request->mobile_no}%");
            });
        }

        // Apply Bill No (Invoice No / ID / Vendor Inv#) Filter
        if ($request->filled('bill_no')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_no', 'like', "%{$request->bill_no}%")
                  ->orWhere('purchase_order_no', 'like', "%{$request->bill_no}%")
                  ->orWhere('id', 'like', "%{$request->bill_no}%");
            });
        }

        // Apply M.Bill # (Note) Filter
        if ($request->filled('reference')) {
            $query->where('note', 'like', "%{$request->reference}%");
        }

        // Apply Vendor Filter
        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Order By
        $orderBy = $request->input('order_by', 'purchase_date');
        if ($orderBy === 'invoice_no') {
            $query->orderBy('invoice_no', 'desc');
        } else {
            $query->orderBy('purchase_date', 'desc');
        }

        $Purchase = $query->get();
        
        // Calculate updated amounts after returns
        $Purchase->each(function ($purchase) {
            // Calculate total returned amount for this purchase
            $totalReturned = $purchase->returns->sum('net_amount');
            
            // Store calculated values as attributes
            $purchase->total_returned = $totalReturned;
            $purchase->updated_net_amount = max(0, $purchase->net_amount - $totalReturned);
            $purchase->updated_due_amount = max(0, $purchase->due_amount - $totalReturned);
            
            // Check if fully returned
            $purchase->is_fully_returned = $purchase->net_amount > 0 && $totalReturned >= $purchase->net_amount;
            $purchase->has_partial_return = $totalReturned > 0 && $totalReturned < $purchase->net_amount;
        });

        // If AJAX request, return only table rows partial
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin_panel.purchase.partials.purchase_table_body', compact('Purchase'))->render()
            ]);
        }

        // Load all vendors for filter dropdown
        $vendors = Vendor::orderBy('name')->get();

        $purchaseGstEnabled = (bool) \App\Models\Setting::get('purchase_gst_enabled', '1');
        $purchaseGstPercent = (float) \App\Models\Setting::get('purchase_gst_percent', '18.00');

        return view('admin_panel.purchase.index', compact('Purchase', 'vendors', 'purchaseGstEnabled', 'purchaseGstPercent'));
    }

    public function addBill($gatepassId)
    {
        // Fetch the gatepass along with its related items and products
        $gatepass = InwardGatepass::with('items.product')->findOrFail($gatepassId);

        // Pass the gatepass data to the view
        return view('admin_panel.inward.add_bill', compact('gatepass'));
    }

    public function add_purchase()
    {
        // $userId = Auth::id();
        $Purchase = Purchase::get();
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::all();
        $allProducts = \App\Models\Product::where('is_active', true)
            ->withSum('warehouseStocks', 'total_pieces')
            ->orderBy('item_name')
            ->get();

        // Filter accounts to only show Cash (1) and Bank (2) heads to prevent logic errors
        $accounts = \App\Models\Account::whereHas('head', function($q) {
            $q->whereIn('name', ['Cash', 'Bank']);
        })->where('status', 1)->orderBy('title')->get();

        $lastInvoice = Purchase::latest('id')->value('invoice_no');
        $nextInvoice = $lastInvoice
            ? 'PUR-'.str_pad(((int) preg_replace('/[^0-9]/', '', $lastInvoice)) + 1, 3, '0', STR_PAD_LEFT)
            : 'PUR-001';

        // Return new V2 view
        $purchaseGstEnabled = (bool) \App\Models\Setting::get('purchase_gst_enabled', '1');
        $defaultGstPercent = (float) \App\Models\Setting::get('purchase_gst_percent', '18.00');

        return view('admin_panel.purchase.add_purchase_v2', compact('Vendor', 'Warehouse', 'Purchase', 'accounts', 'nextInvoice', 'allProducts', 'purchaseGstEnabled', 'defaultGstPercent'));
    }
    public function quickCreate()
    {
        $Vendor = Vendor::get();
        $Warehouse = Warehouse::get();
        $accounts = \App\Models\Account::whereHas('head', function($q) {
            $q->whereIn('name', ['Cash', 'Bank']);
        })->where('status', 1)->orderBy('title')->get();
        
        $categories = \App\Models\Category::get();

        $balanceService = app(\App\Services\BalanceService::class);
        $apId = $balanceService->getAccountsPayableId();
        $vendorBalances = \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('account_id', $apId)
            ->selectRaw('party_id, COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->groupBy('party_id')
            ->pluck('balance', 'party_id')
            ->toArray();

        return view('admin_panel.purchase.quick_create', compact('Vendor', 'Warehouse', 'accounts', 'categories', 'vendorBalances'));
    }

    public function quickStore(Request $request)
    {
        try {
            $validated = $request->validate([
                'vendor_id' => 'nullable',
                'new_vendor_name' => 'nullable|string',
                'opening_balance' => 'nullable|numeric',
                'category_id' => 'required',
                'sub_category_id' => 'nullable',
                'base_product_name' => 'required|string',
                'purchase_order_no' => 'nullable|string|max:255',
                'payment_account_id' => 'nullable|exists:accounts,id',
                'payment_amount' => 'nullable|numeric|min:0',
                
                'variant_size' => 'required|array|min:1',
                'variant_color' => 'required|array|min:1',
                'qty' => 'required|array|min:1',
                'qty.*' => 'required|numeric|min:1',
                'purchase_price' => 'required|array',
                'purchase_price.*' => 'required|numeric|min:0',
                'sale_price' => 'required|array',
                'sale_price.*' => 'required|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'errors' => $e->errors(), 'message' => 'Validation Error'], 422);
            }
            throw $e;
        }

        $purchase = DB::transaction(function () use ($validated, $request) {
            // 1. Resolve Vendor
            $vendorId = $validated['vendor_id'];
            if (!$vendorId && !empty($validated['new_vendor_name'])) {
                $vendor = Vendor::create([
                    'name' => $validated['new_vendor_name'],
                    'status' => 1
                ]);
                $vendorId = $vendor->id;

                if (!empty($validated['opening_balance']) && $validated['opening_balance'] != 0) {
                    \App\Models\VendorLedger::create([
                        'vendor_id' => $vendorId,
                        'admin_or_user_id' => auth()->id() ?? 1,
                        'previous_balance' => 0,
                        'opening_balance' => 0,
                        'closing_balance' => (float)$validated['opening_balance']
                    ]);
                }
            }

            // 2. Create Header
            $lastInvoice = Purchase::latest('id')->value('invoice_no');
            $nextInvoice = $lastInvoice
                ? 'PUR-'.str_pad(((int) preg_replace('/[^0-9]/', '', $lastInvoice)) + 1, 3, '0', STR_PAD_LEFT)
                : 'PUR-001';

            $purchase = Purchase::create([
                'branch_id' => 1,
                'warehouse_id' => (int) ($request->warehouse_id ?: 1),
                'vendor_id' => $vendorId,
                'purchase_date' => now(),
                'invoice_no' => $nextInvoice,
                'purchase_order_no' => $validated['purchase_order_no'] ?? null,
                'note' => 'Quick Purchase: ' . $validated['base_product_name'],
                'subtotal' => 0,
                'discount' => 0,
                'extra_cost' => 0,
                'net_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'status_purchase' => 'approved',
            ]);

            $subtotal = 0;
            $baseName = $validated['base_product_name'];
            $sizes = $request->variant_size;
            $colors = $request->variant_color;
            $qtys = $validated['qty'];
            $pPrices = $validated['purchase_price'];
            $sPrices = $validated['sale_price'];

            // 3. Build Variant JSON array for single Product
            $variantsArray = [];
            foreach ($sizes as $i => $sizeStr) {
                $qty = (float) $qtys[$i];
                if ($qty <= 0) continue;
                
                $colorStr = $colors[$i];
                
                $variantsArray[] = [
                    'name' => $baseName, // Ensure name is set for POS display
                    'size' => $sizeStr,
                    'color' => $colorStr,
                    'sale_price' => (float) $sPrices[$i],
                    'wholesale_price' => (float) $sPrices[$i],
                    'purch_price' => (float) $pPrices[$i],
                    'weight_per_piece' => 0,
                    'stock' => 0
                ];
            }

            // Create ONE master Product
            $lastProduct = Product::orderBy('id', 'desc')->first();
            $nextCode = $lastProduct ? ('ITEM-'.str_pad($lastProduct->id + 1, 4, '0', STR_PAD_LEFT)) : 'ITEM-0001';

            $product = Product::create([
                'item_name' => $baseName,
                'category_id' => $validated['category_id'] ?? null,
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'color' => json_encode($variantsArray), // Store variants here!
                'item_code' => $nextCode,
                'purchase_price_per_piece' => (float) ($pPrices[0] ?? 0),
                'sale_price_per_piece' => (float) ($sPrices[0] ?? 0),
                'size_mode' => 'pieces',
                'total_m2' => 0,
                'is_active' => 1
            ]);

            // 4. Create Purchase Items linking to the single master Product
            foreach ($sizes as $i => $sizeStr) {
                $qty = (float) $qtys[$i];
                $pPrice = (float) $pPrices[$i];
                if ($qty <= 0) continue;

                $colorStr = $colors[$i];
                $lineTotal = $qty * $pPrice;

                // Create Purchase Item
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id, // All point to the same master product
                    'price' => $pPrice,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                    // Store variant info in color field for the PurchaseItem as JSON
                    'color' => json_encode(['size' => $sizeStr, 'color' => $colorStr]), 
                    'size_mode' => 'pieces',
                    'pieces_per_box' => 1,
                ]);

                $subtotal += $lineTotal;
            }

            $purchase->update([
                'subtotal' => $subtotal,
                'net_amount' => $subtotal,
                'due_amount' => $subtotal,
            ]);

            // 4. Approve (Stock + Ledgers)
            $purchase->load('items');
            $this->approvePurchase($purchase);

            // 5. Payment
            if (!empty($validated['payment_account_id']) && $validated['payment_amount'] > 0) {
                try {
                    $transactionService = app(\App\Services\TransactionService::class);
                    $transactionService->createPaymentForPurchase(
                        $purchase,
                        [$validated['payment_account_id']],
                        [$validated['payment_amount']]
                    );
                } catch (\Exception $e) {}
            }

            return $purchase;
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Quick Purchase saved successfully!',
                'redirect_url' => route('Purchase.home'),
            ]);
        }

        return redirect()->route('Purchase.home')->with('success', 'Quick Purchase saved successfully!');
    }

    private function approvePurchase(Purchase $purchase)
    {
        // 1. Stock Movements & Warehouse Stock
        // We need to re-iterate items because we need product_id and qty
        // But the previous logic used $validated arrays which might process duplicates or specific logic.
        // However, since the PurchaseItems are already saved in DB for 'draft' or 'new',
        // we should rely on the SAVED items for approval to ensure consistency.

        $branchId = $purchase->branch_id;
        $warehouseId = $purchase->warehouse_id;

        // Check for Gatepass link (if linked, no stock movement needed usually, logic from store method)
        $hasGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

        if (! $hasGatepass) {
            $movRows = [];
            foreach ($purchase->items as $item) {
                $itemWarehouseId = (int) ($item->warehouse_id ?: $warehouseId);
                // Determine conversion factor and unit
                $convFactor = 1;
                $unit = strtolower(trim($item->unit ?? ''));
                if (!empty($item->color)) {
                    $itemColor = $item->color;
                    $b64Decoded = base64_decode($itemColor, true);
                    $json = $b64Decoded !== false ? json_decode($b64Decoded, true) : null;
                    if (!is_array($json)) {
                        $json = json_decode($itemColor, true);
                    }
                    if (is_array($json)) {
                        if (isset($json['conv_factor']) && (float)$json['conv_factor'] > 0) {
                            $convFactor = (float) $json['conv_factor'];
                        }
                        if (empty($unit) && isset($json['unit'])) {
                            $unit = strtolower(trim($json['unit']));
                        }
                    }
                }

                $ppb = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : ($item->product->pieces_per_box ?? 1));
                if ($ppb <= 0) $ppb = 1;

                $isPiece = in_array($unit, ['pcs', 'pc', 'piece']);
                $isCarton = in_array($unit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (!$isPiece && in_array($item->size_mode, ['by_cartons', 'by_bandal']));

                if ($unit === 'gm' || $unit === 'g' || $unit === 'gram' || $unit === 'grams') {
                    $baseQty = ((float) $item->qty) / 1000.0;
                } elseif ($isPiece) {
                    // Pieces purchased: qty is directly pieces
                    $baseQty = (float) $item->qty;
                } elseif ($isCarton) {
                    // Full carton / carton.loose purchased: convert cartons to pieces for warehouse_stocks and stock_movements
                    if ($item->boxes_qty > 0 || $item->loose_qty > 0) {
                        $boxes = (int) $item->boxes_qty;
                        $loose = (int) $item->loose_qty;
                    } else {
                        [$boxes, $loose] = self::parseCartonQty($item->qty);
                    }
                    $baseQty = ($boxes * $ppb) + $loose;
                } else {
                    $baseQty = ((float) $item->qty) * $convFactor;
                }

                // movements (+)
                $movRows[] = [
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $baseQty,
                    'ref_type' => 'PURCHASE',
                    'ref_id' => $purchase->id,
                    'note' => 'Purchase Confirmed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                // stocks
                $this->upsertStocks($item->product_id, +$baseQty, $branchId, $itemWarehouseId);

                // Process Batch Creation/Stock
                $batchId = null;
                if (!empty($item->batch_no)) {
                    $batch = \App\Models\ProductBatch::firstOrCreate([
                        'product_id' => $item->product_id,
                        'warehouse_id' => $itemWarehouseId,
                        'batch_no' => $item->batch_no,
                    ], [
                        'cost_price' => $item->price,
                        'mfg_date' => $item->mfg_date,
                        'expiry_date' => $item->exp_date,
                        'qty' => 0,
                    ]);
                    if (!empty($item->mfg_date) && empty($batch->mfg_date)) {
                        $batch->mfg_date = $item->mfg_date;
                    }
                    if (!empty($item->exp_date) && empty($batch->expiry_date)) {
                        $batch->expiry_date = $item->exp_date;
                    }
                    $batch->increment('qty', $baseQty);
                    $batch->save();
                    $batchId = $batch->id;
                    $item->update(['batch_id' => $batchId]);

                    // Set product nature to batch if not set
                    $pRec = \App\Models\Product::find($item->product_id);
                    if ($pRec && empty($pRec->tracking_type)) {
                        $pRec->update(['tracking_type' => 'batch']);
                    }
                } elseif (!empty($item->serials) && count((array)$item->serials) > 0) {
                    // Set product nature to serial if not set
                    $pRec = \App\Models\Product::find($item->product_id);
                    if ($pRec && empty($pRec->tracking_type)) {
                        $pRec->update(['tracking_type' => 'serial']);
                    }
                } else {
                    // Lock product nature to simple if not set
                    $pRec = \App\Models\Product::find($item->product_id);
                    if ($pRec && empty($pRec->tracking_type)) {
                        $pRec->update(['tracking_type' => 'simple']);
                    }
                }

                // Process Serial / IMEI Numbers Creation
                $serialsList = is_array($item->serials) ? $item->serials : (is_string($item->serials) ? json_decode($item->serials, true) : []);
                if (is_array($serialsList) && count($serialsList) > 0) {
                    foreach ($serialsList as $sNum) {
                        $sNum = trim($sNum);
                        if ($sNum !== '') {
                            \App\Models\ProductSerial::updateOrCreate([
                                'product_id' => $item->product_id,
                                'serial_number' => $sNum,
                            ], [
                                'warehouse_id' => $itemWarehouseId,
                                'batch_id' => $batchId,
                                'status' => 'available',
                                'cost_price' => $item->price,
                            ]);
                        }
                    }
                }
            }

            if (! empty($movRows)) {
                DB::table('stock_movements')->insert($movRows);
            }
        }

        // 2. Vendor Ledger
        $netAmount = $purchase->net_amount;
        $prevClosing = \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)
            ->value('closing_balance') ?? 0;

        \App\Models\VendorLedger::updateOrCreate(
            ['vendor_id' => $purchase->vendor_id],
            [
                'vendor_id' => $purchase->vendor_id,
                'admin_or_user_id' => auth()->id() ?? 1,
                'previous_balance' => $prevClosing,
                'opening_balance' => $prevClosing,
                'closing_balance' => $prevClosing + $netAmount,
            ]
        );

        // 3. Accounting
        try {
            $transactionService = app(\App\Services\TransactionService::class);

            // A. Create Purchase Voucher
            $transactionService->createPurchaseVoucher($purchase);

            // B. Record Payment (This part is tricky if payments were passed solely in Request)
            // If payments were saved in a temp table or if we re-collect them, good.
            // BUT: distinct feature request "Confirm Purchase" usually implies later approval.
            // If payments were part of the initial 'store' request, they are lost if we didn't save them.
            // The user said "confirm purchase... don't create vouchers... just save...".
            // So if 'Draft', we did NOT run accounting. The payment inputs were ignored?
            // If we want to support payments on Confirm, we would need to store them or ask again.
            // For now, we will Assume no immediate payments on 'Draft -> Confirm' via separate button,
            // UNLESS we are in the immediate 'store' flow where Request data is available.

            // To handle both cases (immediate approve vs later approve), we can pass optional payment data.
            // But strict signature: approvePurchase(Purchase $purchase, array $paymentData = [])

        } catch (\Exception $e) {
            \Log::error('Purchase Accounting Error: '.$e->getMessage());
        }
    }

    public function confirm($id)
    {
        DB::transaction(function () use ($id) {
            $purchase = Purchase::with('items')->findOrFail($id);
            $oldNetAmount = $purchase->net_amount;

            if ($purchase->status_purchase !== 'draft') {
                return; // already approved or invalid state
            }

            // Run approval logic
            $this->approvePurchase($purchase);

            // Update status
            $purchase->update(['status_purchase' => 'approved']);
        });

        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Purchase confirmed successfully.',
                'invoice_url' => route('purchase.invoice', $id),
                'redirect_url' => route('Purchase.home'),
            ]);
        }

        return redirect()->back()->with('success', 'Purchase confirmed successfully.');
    }

    public function bulkAdditionalDiscount(Request $request)
    {
        $request->validate([
            'purchase_ids' => 'required|array',
            'purchase_ids.*' => 'exists:purchases,id',
            'discount_value' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0',
            'discount_type' => 'nullable|in:percentage,fixed',
        ]);

        $purchaseIds = $request->purchase_ids;
        $discountType = $request->input('discount_type', 'percentage');
        $val = (float) ($request->filled('discount_value') ? $request->discount_value : $request->discount_percentage);

        try {
            DB::transaction(function () use ($purchaseIds, $discountType, $val) {
                foreach ($purchaseIds as $id) {
                    $purchase = Purchase::findOrFail($id);
                    
                    // Original net amount is the current net_amount + current additional_discount
                    $originalNet = (float)$purchase->net_amount + (float)$purchase->additional_discount;
                    
                    if ($discountType === 'fixed') {
                        $newAdditionalDiscount = min($originalNet, round($val, 2));
                    } else {
                        $newAdditionalDiscount = round($originalNet * ($val / 100), 2);
                    }
                    
                    $oldAdditionalDiscount = (float)$purchase->additional_discount;
                    $diff = $newAdditionalDiscount - $oldAdditionalDiscount;

                    if ($diff != 0) {
                        // Update purchase record
                        $purchase->additional_discount = $newAdditionalDiscount;
                        $purchase->net_amount = max(0, $purchase->net_amount - $diff);
                        $purchase->due_amount = max(0, $purchase->due_amount - $diff);
                        $purchase->save();

                        // If approved, update ledger & journal entries
                        if ($purchase->status_purchase === 'approved') {
                            // 1. Adjust legacy VendorLedger closing balance
                            $vendorLedger = \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)->first();
                            if ($vendorLedger) {
                                $vendorLedger->closing_balance -= $diff;
                                $vendorLedger->save();
                            }

                            // 2. Adjust Journal Vouchers
                            $voucher = \App\Models\VoucherMaster::where('remarks', "Purchase Voucher #{$purchase->invoice_no}")->first();
                            if ($voucher) {
                                $voucher->total_amount = max(0, $voucher->total_amount - $diff);
                                $voucher->save();

                                $balanceService = app(\App\Services\BalanceService::class);
                                $expenseAccountId = $balanceService->getPurchaseExpenseId();
                                $apAccountId = $balanceService->getAccountsPayableId();

                                // Update debit for expense
                                \App\Models\JournalEntry::where('source_type', \App\Models\VoucherMaster::class)
                                    ->where('source_id', $voucher->id)
                                    ->where('account_id', $expenseAccountId)
                                    ->update(['debit' => $purchase->net_amount]);

                                // Update credit for accounts payable
                                \App\Models\JournalEntry::where('source_type', \App\Models\VoucherMaster::class)
                                    ->where('source_id', $voucher->id)
                                    ->where('account_id', $apAccountId)
                                    ->update(['credit' => $purchase->net_amount]);
                            }
                        }
                    }
                }
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Additional discount percentage applied successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $gatepassId = null)
    {
        // (A) Gatepass fetch if provided
        $gatepass = null;
        if ($gatepassId) {
            $gatepass = \App\Models\InwardGatepass::with('purchase')->findOrFail($gatepassId);
            if ($gatepass->purchase) {
                if ($request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Gatepass already has a bill.'], 422);
                }

                return back()->with('error', 'This gatepass already has an associated bill.');
            }
        }

        // (B) Validation
        try {
            $validated = $request->validate([
                'invoice_no' => 'nullable|string',
                'purchase_order_no' => 'nullable|string|max:255',
                'vendor_id' => 'required|exists:vendors,id',
                'purchase_date' => 'nullable|date',
                'branch_id' => 'nullable|exists:branches,id',
                'warehouse_id' => 'required',
                'header_warehouse_id' => 'nullable',
                'note' => 'nullable|string',
                'discount' => 'nullable|numeric|min:0',
                'extra_cost' => 'nullable|numeric|min:0',
                'product_id' => 'array',
                'product_id.*' => 'nullable|exists:products,id',
                'qty' => 'array',
                'qty.*' => 'nullable|required_with:product_id.*|numeric|min:0.01',
                'price' => 'array',
                'price.*' => 'nullable|required_with:product_id.*|numeric|min:0',
                'price_per_carton' => 'array',
                'price_per_carton.*' => 'nullable|numeric|min:0',
                'unit' => 'array',
                'unit.*' => 'nullable|required_with:product_id.*|string',
                'item_discount' => 'nullable|array',
                'item_discount.*' => 'nullable|numeric|min:0',
                'gst_percent' => 'nullable|array',
                'gst_percent.*' => 'nullable|numeric|min:0|max:100',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'active' => false, 'errors' => $e->errors(), 'message' => 'Validation Error'], 422);
            }
            throw $e;
        }

        // Wrap in transaction... to allow returning $purchase outside
        $purchase = DB::transaction(function () use ($validated, $request, $gatepass) {

            // invoice number
            $lastInvoice = Purchase::latest('id')->value('invoice_no');
            $nextInvoice = $lastInvoice
                ? 'PUR-'.str_pad(((int) preg_replace('/[^0-9]/', '', $lastInvoice)) + 1, 3, '0', STR_PAD_LEFT)
                : 'PUR-001';

            $user = auth()->user();
            $canSelectWarehouse = $user && (
                (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
                (method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('admin') || $user->hasRole('superadmin'))) ||
                $user->can('warehouse.view')
            );
            $userDefaultWhId = (int) ($user?->warehouse_id ?: 1);

            $branchId = (int) ($validated['branch_id'] ?? 1);
            $headerWhId = $canSelectWarehouse
                ? (int) ($request->header_warehouse_id ?: (is_numeric($request->warehouse_id) ? $request->warehouse_id : (is_array($request->warehouse_id) ? ($request->warehouse_id[0] ?? $userDefaultWhId) : $userDefaultWhId)))
                : $userDefaultWhId;
            $warehouseId = $headerWhId;

            $rowWhIds = ($canSelectWarehouse && is_array($request->warehouse_id))
                ? $request->warehouse_id
                : array_fill(0, count($validated['product_id'] ?? []), $userDefaultWhId);

            // Status Logic
            $status = ($request->action === 'save_only') ? 'draft' : 'approved';

            // create header
            $purchase = Purchase::create([
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'vendor_id' => $validated['vendor_id'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? now(),
                'invoice_no' => $validated['invoice_no'] ?? $nextInvoice,
                'purchase_order_no' => $validated['purchase_order_no'] ?? null,
                'note' => $validated['note'] ?? null,
                'subtotal' => 0,
                'discount' => 0,
                'extra_cost' => 0,
                'net_amount' => 0,
                'paid_amount' => 0,
                'due_amount' => 0,
                'status_purchase' => $status,
            ]);

            $subtotal = 0;
            $pids = $validated['product_id'] ?? [];
            $qtys = $validated['qty'] ?? [];
            $prices = $validated['price'] ?? [];
            $cartonPrices = $validated['price_per_carton'] ?? [];
            $units = $validated['unit'] ?? [];
            $itemDiscs = $validated['item_discount'] ?? [];
            $gstPercents = $request->gst_percent ?? [];
            $totalExclusiveGst = 0;
            $totalGst = 0;

            // Snapshot fields
            $sizeModes = $request->size_mode ?? [];
            $ppbs = $request->pieces_per_box ?? [];
            $ppm2 = $request->pieces_per_m2 ?? [];
            $boxesQtys = $request->boxes_qty ?? [];
            $looseQtys = $request->loose_qty ?? [];
            $lengths = $request->length ?? [];
            $widths = $request->width ?? [];

            foreach ($pids as $i => $pid) {
                $pid = (int) ($pid ?? 0);
                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);
                if (! $pid || $qty <= 0 || $price < 0) {
                    continue;
                }

                $discPercent = (float) ($itemDiscs[$i] ?? 0);
                $unit = $units[$i] ?? null;
                $u = strtolower($unit ?? '');
                $curPPB = (float) ($ppbs[$i] ?? 1);
                if ($curPPB <= 0) {
                    $prod = Product::find($pid);
                    $curPPB = (float) ($prod->pieces_per_box ?? 1);
                }
                if ($curPPB <= 0) $curPPB = 1;

                // Calculate Line Total matching Frontend Logic
                $curSizeMode = $sizeModes[$i] ?? null;
                $curPPM2 = (float) ($ppm2[$i] ?? 0); // This is actually m2_per_piece if by_size
                $rawQtyStr = (string) ($qtys[$i] ?? '0');
                $isPiece = in_array($u, ['pcs', 'pc', 'piece']);
                $isCarton = in_array($u, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (!$isPiece && in_array($curSizeMode, ['by_cartons', 'by_bandal']));

                if ($curSizeMode === 'by_size') {
                    // Frontend: pieces_per_m2 * totalPieces * price
                    $grossTotal = $curPPM2 * $qty * $price;
                } elseif ($u === 'gm' || $u === 'g') {
                    $grossTotal = ($qty / 1000.0) * $price;
                } elseif ($isPiece) {
                    $grossTotal = $qty * $price;
                } elseif ($isCarton) {
                    [$boxes, $loose] = self::parseCartonQty($rawQtyStr);
                    $piecePrice = $curPPB > 0 ? ($price / $curPPB) : $price;
                    $grossTotal = ($boxes * $price) + ($loose * $piecePrice);
                } else {
                    $grossTotal = $qty * $price;
                }

                // Calculate absolute discount from percentage
                $discAmount = $grossTotal * ($discPercent / 100);
                $exclusiveGstAmount = max(0, $grossTotal - $discAmount);
                $gstPercent = isset($gstPercents[$i]) && is_numeric($gstPercents[$i]) ? (float)$gstPercents[$i] : 0;
                $gstAmount = round($exclusiveGstAmount * ($gstPercent / 100), 2);
                $lineTotal = round($exclusiveGstAmount + $gstAmount, 2);

                // Snapshots
                $bQty = (float) ($boxesQtys[$i] ?? 0);
                $lQty = (float) ($looseQtys[$i] ?? 0);
                if ($isPiece) {
                    $bQty = $curPPB > 1 ? floor($qty / $curPPB) : 0;
                    $lQty = $qty;
                } elseif ($isCarton) {
                    [$bQty, $lQty] = self::parseCartonQty($rawQtyStr);
                }

                $batchNos = $request->batch_no ?? [];
                $serialsInputs = $request->serials ?? [];
                $mfgDates = $request->mfg_date ?? [];
                $expDates = $request->exp_date ?? [];

                $itemBatchNo = !empty($batchNos[$i]) ? trim($batchNos[$i]) : null;
                $itemMfgDate = !empty($mfgDates[$i]) ? trim($mfgDates[$i]) : null;
                $itemExpDate = !empty($expDates[$i]) ? trim($expDates[$i]) : null;

                $itemSerialsRaw = $serialsInputs[$i] ?? null;
                $itemSerialsParsed = null;
                if (!empty($itemSerialsRaw)) {
                    $itemSerialsParsed = is_array($itemSerialsRaw) ? $itemSerialsRaw : json_decode($itemSerialsRaw, true);
                }

                $itemWh = !empty($rowWhIds[$i]) ? (int)$rowWhIds[$i] : $warehouseId;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'warehouse_id' => $itemWh,
                    'product_id' => $pid,
                    'batch_no' => $itemBatchNo,
                    'serials' => $itemSerialsParsed,
                    'mfg_date' => $itemMfgDate,
                    'exp_date' => $itemExpDate,
                    'unit' => $unit,
                    'price' => $price,
                    'item_discount' => $discAmount, // Store calculated amount
                    'gst_percent' => $gstPercent,
                    'gst_amount' => $gstAmount,
                    'exclusive_gst_amount' => $exclusiveGstAmount,
                    'qty' => $qty,
                    'line_total' => $lineTotal, // Fixed logic
                    'color' => $request->color[$i] ?? null, // Save variant details

                    // Snapshots
                    'size_mode' => $sizeModes[$i] ?? null,
                    'pieces_per_box' => $curPPB,
                    'pieces_per_m2' => $ppm2[$i] ?? 0,
                    'boxes_qty' => $bQty,
                    'loose_qty' => $lQty,
                    'length' => $lengths[$i] ?? null,
                    'width' => $widths[$i] ?? null,
                ]);

                $subtotal += $lineTotal;
                $totalExclusiveGst += $exclusiveGstAmount;
                $totalGst += $gstAmount;
            }

            // totals
            $discount = (float) ($request->discount ?? 0);
            $extraCost = (float) ($request->extra_cost ?? 0);
            $netAmount = ($subtotal - $discount) + $extraCost;

            $purchase->update([
                'subtotal' => $subtotal,
                'total_exclusive_gst' => $totalExclusiveGst,
                'total_gst' => $totalGst,
                'discount' => $discount,
                'additional_discount' => $discount,
                'extra_cost' => $extraCost,
                'net_amount' => $netAmount,
                'due_amount' => $netAmount,
            ]);

            // Check if payment was submitted (array or scalar)
            $paymentAccountIds = (array) ($request->input('payment_account_id') ?? []);
            $paymentAmounts = (array) ($request->input('payment_amount') ?? []);

            $singlePaid = (float) ($request->input('paid_amount') ?? $request->input('payment_amount_single') ?? 0);
            if ($singlePaid > 0 && empty(array_filter($paymentAmounts, fn($v) => (float)$v > 0))) {
                $paymentAmounts = [$singlePaid];
            }

            $totalPaymentEntered = 0;
            foreach ($paymentAmounts as $pAmt) {
                $totalPaymentEntered += (float) $pAmt;
            }

            // If NOT draft OR if payment is provided, run full approval & record payment
            if ($status === 'approved' || $totalPaymentEntered > 0) {
                if ($purchase->status_purchase !== 'approved') {
                    $purchase->update(['status_purchase' => 'approved']);
                }

                $purchase->load('items');
                $this->approvePurchase($purchase); // Basic Stock + Ledger + Voucher

                // Record Payment to Vendor
                if ($totalPaymentEntered > 0) {
                    try {
                        $transactionService = app(\App\Services\TransactionService::class);

                        // Find default Cash/Bank account fallback if account is missing
                        $defaultAccount = \App\Models\Account::whereHas('head', function($q) {
                            $q->whereIn('name', ['Cash', 'Bank']);
                        })->where('status', 1)->first() ?? \App\Models\Account::where('status', 1)->first();

                        $cleanedAccounts = [];
                        $cleanedAmounts = [];
                        foreach ($paymentAmounts as $idx => $amt) {
                            $amt = (float) $amt;
                            if ($amt > 0) {
                                $accId = $paymentAccountIds[$idx] ?? null;
                                if (empty($accId) && $defaultAccount) {
                                    $accId = $defaultAccount->id;
                                }
                                if (!empty($accId)) {
                                    $cleanedAccounts[] = $accId;
                                    $cleanedAmounts[] = $amt;
                                }
                            }
                        }

                        if (!empty($cleanedAccounts) && !empty($cleanedAmounts)) {
                            $transactionService->createPaymentForPurchase(
                                $purchase,
                                $cleanedAccounts,
                                $cleanedAmounts
                            );
                        }
                    } catch (\Throwable $e) {
                        \Log::error('Purchase Payment Processing Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                    }
                }
            }

            // link gatepass -> purchase (and keep status)
            if ($gatepass) {
                $gatepass->purchase_id = $purchase->id;
                $gatepass->status = 'linked';
                $gatepass->save();
            }

            return $purchase;
        });

        $invNo = ($purchase && $purchase->invoice_no) ? " #{$purchase->invoice_no}" : " #{$purchase->id}";
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Purchase Invoice{$invNo} saved successfully.",
                'invoice_url' => route('purchase.invoice', $purchase->id),
                'redirect_url' => route('Purchase.home'),
            ]);
        }

        return redirect()->route('Purchase.home')->with('success', "Purchase Invoice{$invNo} saved successfully.");
    }

    // public function store(Request $request)
    // {
    //     // ✅ Validation
    //     $validated = $request->validate([
    //         'invoice_no'     => 'nullable|string',
    //         'vendor_id'      => 'nullable|exists:vendors,id',
    //         'purchase_date'  => 'nullable|date',
    //         'warehouse_id'   => 'nullable|exists:warehouses,id',
    //         'note'           => 'nullable|string',
    //         'discount'       => 'nullable|numeric|min:0',
    //         'extra_cost'     => 'nullable|numeric|min:0',

    //         // Purchase Items
    //         'product_id'       => 'nullable|array',
    //         'product_id.*'     => 'nullable|exists:products,id',
    //         'qty'              => 'nullable|array',
    //         'qty.*'            => 'nullable|numeric|min:1',
    //         'price'            => 'nullable|array',
    //         'price.*'          => 'nullable|numeric|min:0',
    //         'unit'             => 'nullable|array',
    //         'unit.*'           => 'nullable|string',
    //         'item_discount'    => 'nullable|array',
    //         'item_discount.*'  => 'nullable|numeric|min:0',
    //     ]);

    //     DB::transaction(function () use ($validated, $request) {

    //         // 🧾 Generate Next Invoice No
    //         $lastInvoice = Purchase::latest()->value('invoice_no');
    //         $nextInvoice = $lastInvoice
    //             ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    //             : 'INV-00001';

    //         // ✍️ Create Purchase with temporary values
    //         $purchase = Purchase::create([
    //             'branch_id'     => auth()->user()->id,
    //             'warehouse_id'  => $validated['warehouse_id'],
    //             'vendor_id'     => $validated['vendor_id'] ?? null,
    //             'purchase_date' => $validated['purchase_date'] ?? now(),
    //             'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    //             'note'          => $validated['note'] ?? null,
    //             'subtotal'      => 0,
    //             'discount'      => 0,
    //             'extra_cost'    => 0,
    //             'net_amount'    => 0,
    //             'paid_amount'   => 0,
    //             'due_amount'    => 0,
    //         ]);

    //         $subtotal = 0;

    //         // 🧾 Purchase Items
    //         $productIds = $validated['product_id'] ?? [];
    //         foreach ($productIds as $index => $productId) {
    //             $qty   = $validated['qty'][$index] ?? null;
    //             $price = $validated['price'][$index] ?? null;

    //             if (empty($productId) || empty($qty) || empty($price)) {
    //                 continue;
    //             }

    //             $disc = $validated['item_discount'][$index] ?? 0; // ✅ Correct name
    //             $unit = $validated['unit'][$index] ?? null;

    //             $lineTotal = ($price * $qty) - $disc;

    //             PurchaseItem::create([
    //                 'purchase_id'   => $purchase->id,
    //                 'product_id'    => $productId,
    //                 'unit'          => $unit,
    //                 'price'         => $price,
    //                 'item_discount' => $disc,
    //                 'qty'           => $qty,
    //                 'line_total'    => $lineTotal,
    //             ]);

    //             $subtotal += $lineTotal;

    //             // 📦 Update Stock
    //             $stock = Stock::where('branch_id', auth()->user()->id)
    //                 ->where('warehouse_id', $validated['warehouse_id'])
    //                 ->where('product_id', $productId)
    //                 ->first();

    //             if ($stock) {
    //                 $stock->qty += $qty;
    //                 $stock->save();
    //             } else {
    //                 Stock::create([
    //                     'branch_id'     => auth()->user()->id,
    //                     'warehouse_id'  => $validated['warehouse_id'],
    //                     'product_id'    => $productId,
    //                     'qty'           => $qty,
    //                 ]);
    //             }
    //         }

    //         // 💵 Final Calculations (use values from request safely)
    //         $discount   = $request->discount ?? 0;
    //         $extraCost  = $request->extra_cost ?? 0;
    //         $netAmount  = ($subtotal - $discount) + $extraCost;

    //         $purchase->update([
    //             'subtotal'    => $subtotal,
    //             'discount'    => $discount,
    //             'extra_cost'  => $extraCost,
    //             'net_amount'  => $netAmount,
    //             'due_amount'  => $netAmount,
    //         ]);

    //         // 📘 Vendor Ledger Update
    //         $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    //             ->value('closing_balance') ?? 0;

    //         $newClosingBalance = $previousBalance + $netAmount;

    //         VendorLedger::updateOrCreate(
    //             ['vendor_id' => $validated['vendor_id']],
    //             [
    //                 'vendor_id'         => $validated['vendor_id'],
    //                 'admin_or_user_id'  => auth()->id(),
    //                 'previous_balance'  => $subtotal,
    //                 'closing_balance'   => $newClosingBalance,
    //             ]
    //         );
    //     });

    //     return back()->with('success', 'Purchase saved successfully!');
    // }

    // public function store(Request $request)
    // {

    //         $validated = $request->validate([
    //             'invoice_no'     => 'nullable|string',
    //             'vendor_id'      => 'nullable|exists:vendors,id',
    //             // 'branch_id'      => 'required|exists:branches,id',
    //             'purchase_date'  => 'nullable|date',
    //             'warehouse_id'   => 'nullable|exists:warehouses,id',
    //             'note'           => 'nullable|string',
    //     'discount'       => 'nullable|numeric|min:0',
    //     'extra_cost'     => 'nullable|numeric|min:0',

    //             // Purchase Items
    //             'product_id'     => 'nullable|array',
    //             'product_id.*'   => 'nullable|exists:products,id',
    //             'qty'            => 'nullable|array',
    //             'qty.*'          => 'nullable|numeric|min:1',
    //             'price'          => 'nullable|array',
    //             'price.*'        => 'nullable|numeric|min:0',
    //             'unit'           => 'nullable|array',
    //             'unit.*'         => 'nullable|string',
    //             'item_discount'  => 'nullable|array',
    //             'item_discount.*'=> 'nullable|numeric|min:0',
    //         ]);
    // DB::transaction(function () use ($validated) {

    //     $lastInvoice = Purchase::latest()->value('invoice_no');

    //     $nextInvoice = $lastInvoice
    //         ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    //         : 'INV-00001';

    //     // 1️⃣ Create purchase
    //     $purchase = Purchase::create([
    //         'branch_id'     => Auth()->user()->id,
    //         'warehouse_id'  => $validated['warehouse_id'],
    //         'vendor_id'     => $validated['vendor_id'] ?? null,
    //         'purchase_date' => $validated['purchase_date'] ?? now(),
    //         'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    //         'note'          => $validated['note'] ?? null,
    //         'subtotal'      => $validated['subtotal'] ?? 0,
    //         'discount'      => $validated['discount'] ?? 0,
    //         'extra_cost'    => $validated['extra_cost'] ?? 0,
    //         'net_amount'    => $validated['net_amount'] ?? 0,
    //         'paid_amount'   => 0,
    //         'due_amount'    => 0,

    //     ]);

    //     $subtotal = 0;

    //     // 2️⃣ Loop & filter rows
    //     $productIds = $validated['product_id'] ?? [];
    //     foreach ($productIds as $index => $productId) {
    //         $qty   = $validated['qty'][$index] ?? null;
    //         $price = $validated['price'][$index] ?? null;

    //         // Skip row if any essential field is empty
    //         if (empty($productId) || empty($qty) || empty($price)) {
    //             continue;
    //         }

    //         $disc = $validated['item_disc'][$index] ?? 0;
    //         $unit = $validated['unit'][$index] ?? null;

    //         $lineTotal = ($price * $qty) - $disc;

    //         // Save item
    //         PurchaseItem::create([
    //             'purchase_id'   => $purchase->id,
    //             'product_id'    => $productId,
    //             'unit'          => $unit,
    //             'price'         => $price,
    //             'item_discount' => $disc,
    //             'qty'           => $qty,
    //             'line_total'    => $lineTotal,
    //         ]);

    //         $subtotal += $lineTotal;

    //         // 3️⃣ Update stock
    //         $stock = Stock::where('branch_id', Auth()->user()->id)
    //             ->where('warehouse_id', $validated['warehouse_id'])
    //             ->where('product_id', $productId)
    //             ->first();

    //         if ($stock) {
    //             $stock->qty += $qty;
    //             $stock->save();
    //         } else {
    //             Stock::create([
    //                 'branch_id'     => Auth()->user()->id,
    //                 'warehouse_id'  => $validated['warehouse_id'],
    //                 'product_id'    => $productId,
    //                 'qty'           => $qty,
    //             ]);
    //         }
    //     }

    //     // 4️⃣ Update totals
    //     $purchase->update([
    //         'subtotal'    => $subtotal,
    //         'net_amount'  => $subtotal,
    //         'due_amount'  => $subtotal,
    //     ]);

    //     // 5️⃣ Vendor ledger
    //     $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    //         ->value('closing_balance') ?? 0;

    //     $newClosingBalance = $previousBalance + $subtotal;

    //     VendorLedger::updateOrCreate(
    //         ['vendor_id' => $validated['vendor_id']],
    //         [
    //             'vendor_id' => $validated['vendor_id'],
    //             'admin_or_user_id' => Auth::id(),
    //             'previous_balance' => $subtotal,
    //             'closing_balance' => $newClosingBalance,
    //         ]
    //     );

    // });

    // // DB::transaction(function () use ($validated) {

    // // $lastInvoice = Purchase::latest()->value('invoice_no');

    // // // Agar last invoice mila to +1 karo, warna start karo INV-00001
    // // $nextInvoice = $lastInvoice
    // //     ? 'INV-' . str_pad(((int) filter_var($lastInvoice, FILTER_SANITIZE_NUMBER_INT)) + 1, 5, '0', STR_PAD_LEFT)
    // //     : 'INV-00001';

    // //     // 1️⃣ Save main Purchase
    // //     $purchase = Purchase::create([

    // //         'branch_id'     => Auth()->user()->id,
    // //         'warehouse_id'  => $validated['warehouse_id'],
    // //         'vendor_id'     => $validated['vendor_id'] ?? null,
    // //         'purchase_date' => $validated['purchase_date'] ?? now(),
    // //         'invoice_no'    => $validated['invoice_no'] ?? $nextInvoice,
    // //         'note'          => $validated['note'] ?? null,
    // //         'subtotal'      => 0,
    // //         'discount'      => 0,
    // //         'extra_cost'    => 0,
    // //         'net_amount'    => 0,
    // //         'paid_amount'   => 0,
    // //         'due_amount'    => 0,
    // //     ]);

    // //     $subtotal = 0;

    // //     // 2️⃣ Loop purchase items
    // //     foreach ($validated['product_id'] as $index => $productId) {
    // //         $qty     = $validated['qty'][$index];
    // //         $price   = $validated['price'][$index];
    // //         $disc    = $validated['item_discount'][$index] ?? 0;
    // //         $lineTotal = ($price * $qty) - $disc;

    // //         // Save purchase item
    // //         PurchaseItem::create([
    // //             'purchase_id'   => $purchase->id,
    // //             'product_id'    => $productId,
    // //             'unit'          => $validated['unit'][$index] ?? null,
    // //             'price'         => $price,
    // //             'item_discount' => $disc,
    // //             'qty'           => $qty,
    // //             'line_total'    => $lineTotal,
    // //         ]);

    // //         $subtotal += $lineTotal;

    // //         // 3️⃣ Update stock
    // //         $stock = Stock::where('branch_id',  Auth()->user()->id,)
    // //             ->where('warehouse_id', $validated['warehouse_id'])
    // //             ->where('product_id', $productId)
    // //             ->first();

    // //         if ($stock) {
    // //             $stock->qty += $qty;
    // //             $stock->save();
    // //         } else {
    // //             Stock::create([
    // //                 'branch_id'     => Auth()->user()->id,
    // //                 'warehouse_id'  => $validated['warehouse_id'],
    // //                 'product_id'    => $productId,
    // //                 'qty'           => $qty,
    // //             ]);
    // //         }
    // //     }

    // //     // 4️⃣ Update totals in purchase
    // //     $purchase->update([
    // //         'subtotal'    => $subtotal,
    // //         'net_amount'  => $subtotal,
    // //         'due_amount'  => $subtotal,
    // //     ]);

    // //     $previousBalance = VendorLedger::where('vendor_id', $validated['vendor_id'])
    // //         ->value('closing_balance') ?? 0; // If no previous balance, start from 0
    // //     // Calculate new balances

    // //     $newPreviousBalance = $subtotal;

    // //     $newClosingBalance = $previousBalance + $subtotal;
    // //     $userId = Auth::id();

    // //     // Update or create distributor ledger
    // //     VendorLedger::updateOrCreate(
    // //         ['vendor_id' => $validated['vendor_id']],
    // //         [
    // //             'vendor_id' => $validated['vendor_id'],
    // //             'admin_or_user_id' => $userId,
    // //             'previous_balance' => $newPreviousBalance,
    // //             'closing_balance' => $newClosingBalance,
    // //         ]
    // //     );

    // });

    //     return redirect()->back()->with('success', 'Purchase saved successfully!');
    // }

    public function edit($id)
    {
        $purchase = Purchase::with('items.product')->findOrFail($id);

        $Vendor = Vendor::all();
        $Warehouse = Warehouse::all();
        // Filter accounts to only show Cash (1) and Bank (2) heads
        $accounts = \App\Models\Account::whereHas('head', function($q) {
            $q->whereIn('name', ['Cash', 'Bank']);
        })->where('status', 1)->orderBy('title')->get();

        // Fetch existing payment voucher for this purchase
        $paymentVoucher = \App\Models\VoucherMaster::with('details.account')
            ->where('voucher_type', \App\Models\VoucherMaster::TYPE_PAYMENT)
            ->where('remarks', "Auto-Payment for Purchase #{$purchase->invoice_no}")
            ->first();

        $existingPayments = collect();
        if ($paymentVoucher) {
            $existingPayments = $paymentVoucher->details->where('credit', '>', 0);
        }

        // Fallback: If no voucher details found, but purchase has paid_amount > 0
        if ($existingPayments->isEmpty() && (float) $purchase->paid_amount > 0) {
            $balanceService = app(\App\Services\BalanceService::class);
            $cashAccId = $balanceService->getCashAccountId();
            $existingPayments = collect([
                (object)[
                    'account_id' => $cashAccId,
                    'credit' => (float) $purchase->paid_amount
                ]
            ]);
        }

        $allProducts = \App\Models\Product::where('is_active', true)
            ->withSum('warehouseStocks', 'total_pieces')
            ->orderBy('item_name')
            ->get();

        $purchaseGstEnabled = (bool) \App\Models\Setting::get('purchase_gst_enabled', '1');
        $defaultGstPercent = (float) \App\Models\Setting::get('purchase_gst_percent', '18.00');

        return view('admin_panel.purchase.edit', compact('purchase', 'Vendor', 'Warehouse', 'accounts', 'existingPayments', 'allProducts', 'purchaseGstEnabled', 'defaultGstPercent'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'invoice_no' => 'nullable|string',
            'purchase_order_no' => 'nullable|string|max:255',
            'vendor_id' => 'nullable|exists:vendors,id',
            'purchase_date' => 'nullable|date',
            'branch_id' => 'nullable|exists:branches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'note' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'extra_cost' => 'nullable|numeric|min:0',

            'product_id' => 'array',
            'product_id.*' => 'nullable|string',
            'qty' => 'array',
            'qty.*' => 'nullable|required_with:product_id.*|numeric|min:0',
            'price' => 'array',
            'price.*' => 'nullable|required_with:product_id.*|numeric|min:0',
            'unit' => 'nullable|array',
            'item_discount' => 'nullable|array',
            'item_discount.*' => 'nullable|numeric|min:0',
            'payment_account_id' => 'nullable|array',
            'payment_amount' => 'nullable|array',
        ]);

        $purchase = DB::transaction(function () use ($validated, $request, $id) {
            $purchase = Purchase::with(['items', 'vendor'])->findOrFail($id);
            $oldNetAmount = (float) $purchase->net_amount;
            $oldPaidAmount = (float) $purchase->paid_amount;
            $oldVendorId = $purchase->vendor_id;
            $user = auth()->user();
            $canSelectWarehouse = $user && (
                (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
                (method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('admin') || $user->hasRole('superadmin'))) ||
                $user->can('warehouse.view')
            );
            $userDefaultWhId = (int) ($user?->warehouse_id ?: 1);

            $oldWarehouseId = (int) $purchase->warehouse_id;
            $oldBranchId = (int) ($purchase->branch_id ?? 1);

            $headerWhId = $canSelectWarehouse
                ? (int) ($request->header_warehouse_id ?: (is_numeric($request->warehouse_id) ? $request->warehouse_id : ($purchase->warehouse_id ?? $userDefaultWhId)))
                : $userDefaultWhId;
            $warehouseId = $headerWhId;
            $branchId = (int) ($validated['branch_id'] ?? $purchase->branch_id ?? 1);

            $rowWhIds = ($canSelectWarehouse && is_array($request->warehouse_id))
                ? $request->warehouse_id
                : array_fill(0, count($validated['product_id'] ?? []), $userDefaultWhId);

            // Map old totals per product for Stock Delta Logic (in base pieces)
            $oldMap = $purchase->items->groupBy('product_id')->map(function ($items) {
                return (float) $items->sum(function ($item) {
                    $ppb = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : ($item->product->pieces_per_box ?? 1));
                    if ($ppb <= 0) $ppb = 1;
                    $u = strtolower($item->unit ?? '');
                    if ($u === 'carton' || $u === 'ctn' || $u === 'box' || (in_array($item->size_mode, ['by_cartons', 'by_bandal']))) {
                        if ($item->boxes_qty > 0 || $item->loose_qty > 0) {
                            $boxes = (int) $item->boxes_qty;
                            $loose = (int) $item->loose_qty;
                        } else {
                            [$boxes, $loose] = self::parseCartonQty($item->qty);
                        }
                        return ($boxes * $ppb) + $loose;
                    } elseif (in_array($u, ['gm', 'g', 'gram', 'grams'])) {
                        return (float)$item->qty / 1000.0;
                    }
                    return (float)$item->qty;
                });
            });

            // Delete old items
            $purchase->items()->delete();

            $subtotal = 0;
            $newMap = collect();

            // Arrays from request
            $pids = $validated['product_id'] ?? [];
            $qtys = $validated['qty'] ?? [];
            $prices = $validated['price'] ?? [];
            $units = $validated['unit'] ?? [];
            $itemDiscs = $validated['item_discount'] ?? [];
            $gstPercents = $request->gst_percent ?? [];
            $totalExclusiveGst = 0;
            $totalGst = 0;

            // Snapshot fields (Raw Request)
            $sizeModes = $request->size_mode ?? [];
            $ppbs = $request->pieces_per_box ?? [];
            $ppm2 = $request->pieces_per_m2 ?? [];
            $boxesQtys = $request->boxes_qty ?? [];
            $looseQtys = $request->loose_qty ?? [];
            $lengths = $request->length ?? [];
            $widths = $request->width ?? [];
            $colors = $request->color ?? [];

            foreach ($pids as $i => $rawPid) {
                // If variant ID was passed as "id|variant|data"
                $pid = $rawPid;
                if (is_string($rawPid) && str_contains($rawPid, '|variant|')) {
                    $parts = explode('|variant|', $rawPid);
                    $pid = (int) $parts[0];
                    if (empty($colors[$i]) && isset($parts[1])) {
                        $colors[$i] = $parts[1];
                    }
                } else {
                    $pid = (int) ($pid ?? 0);
                }

                $qty = (float) ($qtys[$i] ?? 0);
                $price = (float) ($prices[$i] ?? 0);

                if (! $pid || $qty <= 0) {
                    continue;
                }

                $discPercent = (float) ($itemDiscs[$i] ?? 0);
                $unit = $units[$i] ?? null;

                $u = strtolower($unit ?? '');
                $curPPB = (float) ($ppbs[$i] ?? 1);
                if ($curPPB <= 0) {
                    $prod = Product::find($pid);
                    $curPPB = (float) ($prod->pieces_per_box ?? 1);
                }
                if ($curPPB <= 0) $curPPB = 1;

                $curSizeMode = $sizeModes[$i] ?? null;
                $curPPM2 = (float) ($ppm2[$i] ?? 0);
                $rawQtyStr = (string) ($qtys[$i] ?? '0');
                $isPiece = in_array($u, ['pcs', 'pc', 'piece']);
                $isCarton = in_array($u, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (!$isPiece && in_array($curSizeMode, ['by_cartons', 'by_bandal']));

                if ($curSizeMode === 'by_size') {
                    $grossTotal = $curPPM2 * $qty * $price;
                } elseif (in_array($u, ['gm', 'g'])) {
                    $grossTotal = ($qty / 1000.0) * $price;
                } elseif ($isPiece) {
                    $grossTotal = $qty * $price;
                } elseif ($isCarton) {
                    [$boxes, $loose] = self::parseCartonQty($rawQtyStr);
                    $piecePrice = $curPPB > 0 ? ($price / $curPPB) : $price;
                    $grossTotal = ($boxes * $price) + ($loose * $piecePrice);
                } else {
                    $grossTotal = $qty * $price;
                }

                // Calculate absolute discount from percentage
                $discAmount = $grossTotal * ($discPercent / 100);
                $exclusiveGstAmount = max(0, $grossTotal - $discAmount);
                $gstPercent = isset($gstPercents[$i]) && is_numeric($gstPercents[$i]) ? (float)$gstPercents[$i] : 0;
                $gstAmount = round($exclusiveGstAmount * ($gstPercent / 100), 2);
                $lineTotal = round($exclusiveGstAmount + $gstAmount, 2);

                if ($isPiece) {
                    $baseQty = $qty;
                } elseif ($isCarton) {
                    [$boxes, $loose] = self::parseCartonQty($rawQtyStr);
                    $baseQty = ($boxes * $curPPB) + $loose;
                } elseif (in_array($u, ['gm', 'g', 'gram', 'grams'])) {
                    $baseQty = $qty / 1000.0;
                } else {
                    $baseQty = $qty;
                }

                $bQty = (float) ($boxesQtys[$i] ?? 0);
                $lQty = (float) ($looseQtys[$i] ?? 0);
                if ($isPiece) {
                    $bQty = $curPPB > 1 ? floor($qty / $curPPB) : 0;
                    $lQty = $qty;
                } elseif ($isCarton) {
                    [$bQty, $lQty] = self::parseCartonQty($rawQtyStr);
                }

                $batchNos = $request->batch_no ?? [];
                $serialsInputs = $request->serials ?? [];
                $mfgDates = $request->mfg_date ?? [];
                $expDates = $request->exp_date ?? [];

                $itemBatchNo = !empty($batchNos[$i]) ? trim($batchNos[$i]) : null;
                $itemMfgDate = !empty($mfgDates[$i]) ? trim($mfgDates[$i]) : null;
                $itemExpDate = !empty($expDates[$i]) ? trim($expDates[$i]) : null;

                $itemSerialsRaw = $serialsInputs[$i] ?? null;
                $itemSerialsParsed = null;
                if (!empty($itemSerialsRaw)) {
                    $itemSerialsParsed = is_array($itemSerialsRaw) ? $itemSerialsRaw : json_decode($itemSerialsRaw, true);
                }

                $itemWh = !empty($rowWhIds[$i]) ? (int)$rowWhIds[$i] : $warehouseId;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'warehouse_id' => $itemWh,
                    'product_id' => $pid,
                    'batch_no' => $itemBatchNo,
                    'serials' => $itemSerialsParsed,
                    'mfg_date' => $itemMfgDate,
                    'exp_date' => $itemExpDate,
                    'unit' => $unit,
                    'price' => $price,
                    'item_discount' => $discAmount,
                    'gst_percent' => $gstPercent,
                    'gst_amount' => $gstAmount,
                    'exclusive_gst_amount' => $exclusiveGstAmount,
                    'qty' => $qty,
                    'line_total' => $lineTotal,
                    'color' => $colors[$i] ?? null,

                    // Snapshots
                    'size_mode' => $sizeModes[$i] ?? null,
                    'pieces_per_box' => $curPPB,
                    'pieces_per_m2' => $ppm2[$i] ?? 0,
                    'boxes_qty' => $bQty,
                    'loose_qty' => $lQty,
                    'length' => $lengths[$i] ?? null,
                    'width' => $widths[$i] ?? null,
                ]);

                $subtotal += $lineTotal;
                $totalExclusiveGst += $exclusiveGstAmount;
                $totalGst += $gstAmount;
                $newMap[$pid] = ($newMap[$pid] ?? 0) + $baseQty;
            }

            // Totals calculation
            $discount = (float) ($request->discount ?? 0);
            $extraCost = (float) ($request->extra_cost ?? 0);
            $netAmount = ($subtotal - $discount) + $extraCost;

            // Payments calculation from request
            $paymentAccountIds = $request->input('payment_account_id', []);
            $paymentAmounts = $request->input('payment_amount', []);
            $newPaidAmount = 0;
            $validPaymentAccounts = [];
            $validPaymentAmounts = [];

            if (is_array($paymentAccountIds)) {
                foreach ($paymentAccountIds as $idx => $accId) {
                    $amt = (float) ($paymentAmounts[$idx] ?? 0);
                    if (!empty($accId) && $amt > 0) {
                        $newPaidAmount += $amt;
                        $validPaymentAccounts[] = $accId;
                        $validPaymentAmounts[] = $amt;
                    }
                }
            }

            $dueAmount = max(0, $netAmount - $newPaidAmount);

            // Header update
            $purchase->update([
                'vendor_id' => $validated['vendor_id'] ?? $purchase->vendor_id,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'purchase_date' => $validated['purchase_date'] ?? $purchase->purchase_date,
                'invoice_no' => $validated['invoice_no'] ?? $purchase->invoice_no,
                'purchase_order_no' => $validated['purchase_order_no'] ?? $purchase->purchase_order_no,
                'note' => $validated['note'] ?? $purchase->note,
                'subtotal' => $subtotal,
                'total_exclusive_gst' => $totalExclusiveGst,
                'total_gst' => $totalGst,
                'discount' => $discount,
                'additional_discount' => $discount,
                'extra_cost' => $extraCost,
                'net_amount' => $netAmount,
                'paid_amount' => $newPaidAmount,
                'due_amount' => $dueAmount,
            ]);

            // Stock movements & Warehouse stock updates
            $isLinkedToGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

            if (! $isLinkedToGatepass) {
                $movs = [];
                $now = now();

                if ($oldWarehouseId !== $warehouseId) {
                    // Rollback from old warehouse
                    foreach ($oldMap as $pid => $oldQ) {
                        if ($oldQ > 0) {
                            $this->upsertStocks((int) $pid, -$oldQ, $oldBranchId, $oldWarehouseId);
                        }
                    }
                    // Add to new warehouse
                    foreach ($newMap as $pid => $newQ) {
                        if ($newQ > 0) {
                            $this->upsertStocks((int) $pid, +$newQ, $branchId, $warehouseId);
                            $movs[] = [
                                'product_id' => (int) $pid,
                                'type' => 'in',
                                'qty' => $newQ,
                                'ref_type' => 'PURCHASE_EDIT',
                                'ref_id' => $purchase->id,
                                'note' => 'Purchase warehouse change delta',
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }
                } else {
                    $all = $oldMap->keys()->merge($newMap->keys())->unique();
                    foreach ($all as $pid) {
                        $oldQ = (float) ($oldMap[$pid] ?? 0);
                        $newQ = (float) ($newMap[$pid] ?? 0);
                        $delta = $newQ - $oldQ;
                        if ($delta == 0) {
                            continue;
                        }

                        $type = $delta > 0 ? 'in' : 'out';
                        $qty = abs($delta);

                        $movs[] = [
                            'product_id' => (int) $pid,
                            'type' => $type,
                            'qty' => $qty,
                            'ref_type' => 'PURCHASE_EDIT',
                            'ref_id' => $purchase->id,
                            'note' => 'Purchase edit delta',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $this->upsertStocks((int) $pid, ($type === 'in' ? +$qty : -$qty), $branchId, $warehouseId);
                    }
                }

                if (! empty($movs)) {
                    DB::table('stock_movements')->insert($movs);
                }
            }

            // Accounting & General Ledger Updates
            $balanceService = app(\App\Services\BalanceService::class);
            $voucherService = app(\App\Services\VoucherService::class);
            $transactionService = app(\App\Services\TransactionService::class);
            $expenseAccountId = $balanceService->getPurchaseExpenseId();
            $apAccountId = $balanceService->getAccountsPayableId();

            // 1. Purchase Voucher (Journal Voucher)
            $purchaseVoucher = \App\Models\VoucherMaster::where('remarks', "Purchase Voucher #{$purchase->invoice_no}")->first();
            if ($purchaseVoucher) {
                $purchaseVoucher->total_amount = $netAmount;
                $purchaseVoucher->date = $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d');
                $purchaseVoucher->party_id = $purchase->vendor_id;
                $purchaseVoucher->status = \App\Models\VoucherMaster::STATUS_DRAFT;
                $purchaseVoucher->save();

                // Clear old details and journal entries
                $purchaseVoucher->journalEntries()->delete();
                $purchaseVoucher->details()->delete();

                // Recreate details
                \App\Models\VoucherDetail::create([
                    'voucher_master_id' => $purchaseVoucher->id,
                    'account_id' => $expenseAccountId,
                    'debit' => $netAmount,
                    'credit' => 0,
                    'narration' => "Purchase Invoice #{$purchase->invoice_no}",
                ]);
                \App\Models\VoucherDetail::create([
                    'voucher_master_id' => $purchaseVoucher->id,
                    'account_id' => $apAccountId,
                    'debit' => 0,
                    'credit' => $netAmount,
                    'narration' => "Payable to Vendor " . ($purchase->vendor->name ?? ''),
                ]);

                $voucherService->postVoucher($purchaseVoucher);
            } else {
                $transactionService->createPurchaseVoucher($purchase);
            }

            // 2. Payment Voucher (Payment Voucher)
            $paymentVoucher = \App\Models\VoucherMaster::where('remarks', "Auto-Payment for Purchase #{$purchase->invoice_no}")->first();
            if (! empty($validPaymentAccounts)) {
                if ($paymentVoucher) {
                    $paymentVoucher->journalEntries()->delete();
                    $paymentVoucher->details()->delete();
                    $paymentVoucher->total_amount = $newPaidAmount;
                    $paymentVoucher->date = $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->format('Y-m-d') : now()->format('Y-m-d');
                    $paymentVoucher->party_id = $purchase->vendor_id;
                    $paymentVoucher->status = \App\Models\VoucherMaster::STATUS_DRAFT;
                    $paymentVoucher->save();

                    foreach ($validPaymentAccounts as $pIdx => $pAccId) {
                        $pAmt = (float) $validPaymentAmounts[$pIdx];
                        \App\Models\VoucherDetail::create([
                            'voucher_master_id' => $paymentVoucher->id,
                            'account_id' => $pAccId,
                            'debit' => 0,
                            'credit' => $pAmt,
                            'narration' => "Payment for Purchase #{$purchase->invoice_no}",
                        ]);
                    }

                    \App\Models\VoucherDetail::create([
                        'voucher_master_id' => $paymentVoucher->id,
                        'account_id' => $apAccountId,
                        'debit' => $newPaidAmount,
                        'credit' => 0,
                        'narration' => "Payment to Vendor " . ($purchase->vendor->name ?? ''),
                    ]);

                    $voucherService->postVoucher($paymentVoucher);
                } else {
                    $transactionService->createPaymentForPurchase($purchase, $validPaymentAccounts, $validPaymentAmounts);
                }
            } else {
                if ($paymentVoucher) {
                    $paymentVoucher->journalEntries()->delete();
                    $paymentVoucher->details()->delete();
                    $paymentVoucher->delete();
                }
            }

            // 3. Synchronize Vendor Ledger closing balance
            if ($purchase->vendor_id) {
                $currentVendorBalance = $balanceService->getVendorBalance($purchase->vendor_id);
                $vendorLedger = \App\Models\VendorLedger::where('vendor_id', $purchase->vendor_id)->orderBy('id', 'desc')->first();
                if ($vendorLedger) {
                    $vendorLedger->closing_balance = $currentVendorBalance;
                    $vendorLedger->save();
                } else {
                    \App\Models\VendorLedger::create([
                        'vendor_id' => $purchase->vendor_id,
                        'admin_or_user_id' => auth()->id() ?? 1,
                        'previous_balance' => 0,
                        'opening_balance' => 0,
                        'closing_balance' => $currentVendorBalance,
                    ]);
                }
            }

            // If vendor was changed, sync old vendor balance as well
            if ($oldVendorId && $oldVendorId != $purchase->vendor_id) {
                $oldVendorBal = $balanceService->getVendorBalance($oldVendorId);
                $oldLedger = \App\Models\VendorLedger::where('vendor_id', $oldVendorId)->orderBy('id', 'desc')->first();
                if ($oldLedger) {
                    $oldLedger->closing_balance = $oldVendorBal;
                    $oldLedger->save();
                }
            }

            return $purchase;
        });

        $invNo = ($purchase && $purchase->invoice_no) ? " #{$purchase->invoice_no}" : " #{$id}";
        return redirect()->route('Purchase.home')->with('success', "Purchase Invoice{$invNo} updated successfully!");
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $purchase = Purchase::with('items')->findOrFail($id);
            $oldNetAmount = $purchase->net_amount;

            $branchId = (int) ($purchase->branch_id ?? 1);
            $warehouseId = (int) ($purchase->warehouse_id);

            // linked to gatepass? then NO stock changes
            $isLinkedToGatepass = \App\Models\InwardGatepass::where('purchase_id', $purchase->id)->exists();

            if (! $isLinkedToGatepass) {
                $movs = [];
                $now = now();

                foreach ($purchase->items as $it) {
                    $pid = (int) $it->product_id;
                    $qty = (float) $it->qty;

                    $movs[] = [
                        'product_id' => $pid,
                        'type' => 'out',
                        'qty' => $qty,
                        'ref_type' => 'PURCHASE_DELETE',
                        'ref_id' => $purchase->id,
                        'note' => 'Delete purchase (reverse)',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    // stocks rollback
                    $this->upsertStocks($pid, -$qty, $branchId, $warehouseId);
                }

                if (! empty($movs)) {
                    DB::table('stock_movements')->insert($movs);
                }
            }

            $purchase->items()->delete();
            $purchase->delete();
        });

        return redirect()->back()->with('success', 'Purchase deleted successfully.');
    }

    public function Invoice($id)
    {
        $purchase = Purchase::with(['items.product', 'vendor', 'warehouse'])->findOrFail($id);
        
        $balanceService = app(\App\Services\BalanceService::class);
        $vendor_balance = $balanceService->getVendorBalance($purchase->vendor_id);

        $previousBalance = 0;
        if ($purchase->vendor_id) {
            $voucher = \App\Models\VoucherMaster::where('remarks', "Purchase Voucher #{$purchase->invoice_no}")
                ->orderByRaw("ABS(TIMESTAMPDIFF(SECOND, created_at, '{$purchase->created_at}'))")
                ->first();
            $journalEntry = null;
            if ($voucher) {
                $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\VoucherMaster::class)
                    ->where('source_id', $voucher->id)
                    ->where('party_type', \App\Models\Vendor::class)
                    ->where('party_id', $purchase->vendor_id)
                    ->first();
            }

            if ($journalEntry) {
                $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
                    ->where('party_id', $purchase->vendor_id)
                    ->where('id', '<', $journalEntry->id)
                    ->sum(\Illuminate\Support\Facades\DB::raw('credit - debit'));
            } else {
                $previousBalance = $balanceService->getVendorBalanceBeforeDate($purchase->vendor_id, $purchase->created_at->format('Y-m-d H:i:s'));
            }
        }
        $currentBalance = $previousBalance + $purchase->net_amount - $purchase->paid_amount;

        return view('admin_panel.purchase.Invoice', compact('purchase', 'vendor_balance', 'previousBalance', 'currentBalance'));
    }

    public function receipt($id)
    {
        $purchase = Purchase::with(['items.product', 'vendor'])->findOrFail($id);

        $balanceService = app(\App\Services\BalanceService::class);
        $previousBalance = 0;
        if ($purchase->vendor_id) {
            $voucher = \App\Models\VoucherMaster::where('remarks', "Purchase Voucher #{$purchase->invoice_no}")
                ->orderByRaw("ABS(TIMESTAMPDIFF(SECOND, created_at, '{$purchase->created_at}'))")
                ->first();
            $journalEntry = null;
            if ($voucher) {
                $journalEntry = \App\Models\JournalEntry::where('source_type', \App\Models\VoucherMaster::class)
                    ->where('source_id', $voucher->id)
                    ->where('party_type', \App\Models\Vendor::class)
                    ->where('party_id', $purchase->vendor_id)
                    ->first();
            }

            if ($journalEntry) {
                $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Vendor::class)
                    ->where('party_id', $purchase->vendor_id)
                    ->where('id', '<', $journalEntry->id)
                    ->sum(\Illuminate\Support\Facades\DB::raw('credit - debit'));
            } else {
                $previousBalance = $balanceService->getVendorBalanceBeforeDate($purchase->vendor_id, $purchase->created_at->format('Y-m-d H:i:s'));
            }
        }
        $currentBalance = $previousBalance + $purchase->net_amount - $purchase->paid_amount;

        return view('admin_panel.purchase.receipt', compact('purchase', 'previousBalance', 'currentBalance'));
    }

    // purchase_reutun

    public function showReturnForm($id)
    {
        $purchase = Purchase::with(['vendor', 'warehouse', 'items.product'])->findOrFail($id);
        $accounts = \App\Models\Account::whereHas('head', function($q) {
            $q->whereIn('name', ['Cash', 'Bank']);
        })->where('status', 1)->orderBy('title')->get();
        // Identify max returnable qty: Purchase Qty - Already Returned Qty
        // 1. Get all previous returns for this purchase
        $pastReturns = \App\Models\PurchaseReturn::where('purchase_id', $id)
            ->with('items')
            ->get();
        
        $returnedQtyMap = [];
        foreach ($pastReturns as $pr) {
            foreach ($pr->items as $prItem) {
                $key = $prItem->product_id . '_' . ($prItem->color ?? '');
                if (!isset($returnedQtyMap[$key])) {
                    $returnedQtyMap[$key] = 0;
                }
                $returnedQtyMap[$key] += $prItem->qty;
            }
        }
        
        $purchaseItems = [];
        $hasReturnableItems = false;
        
        foreach ($purchase->items as $item) {
            $key = $item->product_id . '_' . ($item->color ?? '');
            $alreadyReturned = (float) ($returnedQtyMap[$key] ?? 0);
            
            $baseProductName = optional($item->product)->item_name ?? 'Unknown Product';
            $variantNameDisplay = '';
            $variantDetails = [];
            $vPpb = null;

            if (!empty($item->color)) {
                $decoded = base64_decode($item->color, true);
                $vData = ($decoded !== false) ? json_decode($decoded, true) : null;
                if (empty($vData) || !is_array($vData)) {
                    $vData = json_decode($item->color, true);
                }
                if (!empty($vData) && is_array($vData)) {
                    $vName = trim($vData['name'] ?? ($vData['variant_name'] ?? ''));
                    $vColorName = trim($vData['color'] ?? '');
                    $vSizeName = trim($vData['size'] ?? '');

                    if ($vName !== '' && strcasecmp($vName, $baseProductName) !== 0) {
                        $variantNameDisplay = $vName;
                    }

                    if ($vSizeName !== '' && $vSizeName !== '-') {
                        $variantDetails[] = 'Size: ' . $vSizeName;
                    }
                    if ($vColorName !== '' && $vColorName !== '-') {
                        $variantDetails[] = 'Color: ' . $vColorName;
                    }

                    if (isset($vData['conv_factor']) && (float)$vData['conv_factor'] > 0) {
                        $vPpb = (float)$vData['conv_factor'];
                    } elseif (isset($vData['size']) && is_numeric($vData['size']) && (float)$vData['size'] > 0) {
                        $vPpb = (float)$vData['size'];
                    }
                } elseif (is_string($item->color) && trim($item->color) !== '' && trim($item->color) !== '-') {
                    $variantDetails[] = trim($item->color);
                }
            }

            if ($variantNameDisplay !== '') {
                if (stripos($variantNameDisplay, $baseProductName) !== false) {
                    $itemName = $variantNameDisplay;
                } else {
                    $itemName = $baseProductName . ' — ' . $variantNameDisplay;
                }
            } else {
                $itemName = $baseProductName;
            }

            if (!empty($variantDetails)) {
                $itemName .= ' (' . implode(' | ', $variantDetails) . ')';
            }

            // Accurate PPB for Variant / Product
            $ppb = (float) ($item->pieces_per_box > 0 ? $item->pieces_per_box : ($vPpb ?? ($item->product->pieces_per_box ?? 1)));
            if ($vPpb && $vPpb > 0) {
                $ppb = $vPpb;
            }
            if ($ppb <= 0) $ppb = 1;

            $unit = strtolower($item->unit ?? '');
            $sizeMode = $item->size_mode ?? optional($item->product)->size_mode ?? 'by_pieces';
            $isCarton = in_array($unit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (in_array($sizeMode, ['by_cartons', 'by_bandal']));

            // Calculate Total Purchased in Pieces
            if ($isCarton) {
                if (isset($item->boxes_qty) && ($item->boxes_qty > 0 || $item->loose_qty > 0)) {
                    $b = (int) $item->boxes_qty;
                    $l = (int) $item->loose_qty;
                } else {
                    [$b, $l] = self::parseCartonQty($item->qty);
                }
                $totalPurchasedPieces = ($b * $ppb) + $l;
                $purchasedBoxDisplay = $b . ($l > 0 ? '.' . $l : '');
            } else {
                $totalPurchasedPieces = (float) $item->qty;
                if ($ppb > 1) {
                    $b = floor($totalPurchasedPieces / $ppb);
                    $l = $totalPurchasedPieces % $ppb;
                    $purchasedBoxDisplay = $b . ($l > 0 ? '.' . $l : '');
                } else {
                    $purchasedBoxDisplay = (string) $totalPurchasedPieces;
                }
            }

            $remainingPieces = max(0, $totalPurchasedPieces - $alreadyReturned);
            if ($remainingPieces > 0) {
                $hasReturnableItems = true;
            }

            // Calculate remaining Box.Piece display
            if ($ppb > 1) {
                $remB = floor($remainingPieces / $ppb);
                $remL = $remainingPieces % $ppb;
                $remainingBoxDisplay = $remB . ($remL > 0 ? '.' . $remL : '');
            } else {
                $remainingBoxDisplay = (string) $remainingPieces;
            }
            
            $purchaseItems[] = [
                'product_id' => $item->product_id,
                'item_name' => $itemName,
                'brand' => optional(optional($item->product)->brand)->name ?? '',
                'item_code' => optional($item->product)->item_code ?? '',
                'pieces_per_box' => $ppb,
                'size_mode' => $sizeMode,
                'pieces_per_m2' => $item->pieces_per_m2 ?? optional($item->product)->pieces_per_m2 ?? 0,
                'price' => (float) $item->price,
                
                // Qty in Pieces for reliable calculations
                'original_qty' => $totalPurchasedPieces,
                'returned_qty' => $alreadyReturned,
                'qty' => $remainingPieces,
                'purchased_box_display' => $purchasedBoxDisplay,
                'remaining_box_display' => $remainingBoxDisplay,
                
                'unit' => $item->unit ?? ($isCarton ? 'Carton' : 'pc'),
                'discount' => $item->item_discount,
                'color' => $item->color,
            ];
        }
        
        // Block access if fully returned
        if (!$hasReturnableItems && $purchase->status_purchase == 'Returned') {
             return redirect()->route('purchase.return.index')->with('error', 'This purchase has clearly been fully returned already.');
        }

        $purchaseNetAmount = (float) ($purchase->net_amount ?? 0);
        $purchasePaidAmount = (float) ($purchase->paid_amount ?? 0);

        // Fetch actual payment vouchers if any to get exact paid amount
        $paymentVouchers = \App\Models\VoucherMaster::where('voucher_type', \App\Models\VoucherMaster::TYPE_PAYMENT)
            ->where(function($q) use ($purchase) {
                $q->where('remarks', 'like', "%#{$purchase->invoice_no}%")
                  ->orWhere('remarks', 'like', "%Purchase #{$purchase->id}%");
            })
            ->where('status', \App\Models\VoucherMaster::STATUS_POSTED)
            ->get();

        if ($paymentVouchers->isNotEmpty()) {
            $purchasePaidAmount = (float) $paymentVouchers->sum('total_amount');
        }

        // Safety cap: Paid amount cannot exceed purchase net amount
        $purchasePaidAmount = min($purchasePaidAmount, $purchaseNetAmount);

        $pastReturnAmount = (float) $pastReturns->sum('net_amount');
        $purchaseDueAmount = max(0, $purchaseNetAmount - $pastReturnAmount - $purchasePaidAmount);

        return view('admin_panel.purchase.purchase_return.create', compact('purchase', 'accounts', 'purchaseItems', 'purchaseNetAmount', 'purchasePaidAmount', 'purchaseDueAmount', 'pastReturnAmount'));
    }

    // store return
    public function storeReturn(Request $request)
    {
        $validated = $request->validate([
            'purchase_id' => 'nullable|exists:purchases,id',
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'return_date' => 'required|date',
            'return_reason' => 'nullable|string|max:255',
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'qty' => 'required|array', // Pieces (Total Pieces)
            'qty.*' => 'required|numeric|min:0', // Allow 0 if partial
            'price' => 'required|array',
            'payment_account_id' => 'nullable|array',
            'payment_amount' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // 1. Generate Return Invoice #
            $lastReturn = PurchaseReturn::latest()->first();
            $nextInvoice = 'PRTN-'.str_pad(optional($lastReturn)->id + 1 ?? 1, 5, '0', STR_PAD_LEFT);

            // 2. Create Purchase Return Record
            $purchase = $request->purchase_id ? Purchase::find($request->purchase_id) : null;
            $remarks = $request->return_reason;
            if ($purchase) {
                $remarks .= ' (Ref Invoice: '.$purchase->invoice_no.')';
            }

            $return = PurchaseReturn::create([
                'purchase_id' => $purchase ? $purchase->id : null, 
                'vendor_id' => $validated['vendor_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'return_invoice' => $nextInvoice,
                'return_date' => $validated['return_date'],
                'return_reason' => $validated['return_reason'],
                'remarks' => $remarks,
                'bill_amount' => 0, // calculated below
                'item_discount' => 0,
                'extra_discount' => $request->extra_discount ?? 0,
                'net_amount' => 0,
                'paid' => 0,
                'balance' => 0,
            ]);

            $subtotal = 0;
            $totalItemDiscount = 0;
            $movements = [];
            $now = now();

            // 3. Process Items & Stock
            foreach ($validated['product_id'] as $index => $productId) {
                $qty = (float) ($validated['qty'][$index] ?? 0); // Pieces
                $price = (float) ($validated['price'][$index] ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                $colorField = $request->color[$index] ?? null;

                // Find original item to get snapshots matching color
                $origItem = \App\Models\PurchaseItem::where('purchase_id', $purchase->id ?? 0)
                    ->where('product_id', $productId)
                    ->where(function($q) use ($colorField) {
                        if (!empty($colorField)) {
                            $q->where('color', $colorField);
                        }
                    })
                    ->first();

                if (!$origItem) {
                    $origItem = \App\Models\PurchaseItem::where('purchase_id', $purchase->id ?? 0)
                        ->where('product_id', $productId)
                        ->first();
                }

                $product = Product::find($productId);

                // Resolve variant PPB
                $vPpb = null;
                if (!empty($colorField)) {
                    try {
                        $b64Decoded = base64_decode($colorField, true);
                        $vData = $b64Decoded !== false ? json_decode($b64Decoded, true) : null;
                        if (!is_array($vData)) {
                            $vData = is_string($colorField) ? json_decode($colorField, true) : $colorField;
                        }
                        if (is_array($vData)) {
                            if (isset($vData['conv_factor']) && (float)$vData['conv_factor'] > 0) {
                                $vPpb = (float)$vData['conv_factor'];
                            } elseif (isset($vData['size']) && is_numeric($vData['size']) && (float)$vData['size'] > 0) {
                                $vPpb = (float)$vData['size'];
                            }
                        }
                    } catch (\Exception $e) {}
                }

                $ppb = (float) ($origItem ? ($origItem->pieces_per_box ?? 1) : ($vPpb ?? ($product->pieces_per_box ?? 1)));
                if ($vPpb && $vPpb > 0) {
                    $ppb = $vPpb;
                }
                if ($ppb <= 0) $ppb = 1;

                $sizeMode = $origItem ? ($origItem->size_mode ?? 'by_pieces') : ($product->size_mode ?? 'by_pieces');
                $unit = strtolower($origItem->unit ?? ($product->unit->name ?? 'pc'));
                $isCarton = in_array($unit, ['carton', 'ctn', 'box', 'bandal', 'bundal', 'bndl']) || (in_array($sizeMode, ['by_cartons', 'by_bandal']));
                
                // Fallback to m2_of_box if pieces_per_m2 is 0 or missing in old data
                $ppm2 = $origItem && $origItem->pieces_per_m2 > 0 ? $origItem->pieces_per_m2 : ($product->m2_of_box ?? 0);

                // Calculate Line Total Logic (Same as Purchase Store)
                if ($sizeMode === 'by_size') {
                    $lineTotal = round(($ppm2 ?: 1) * $qty * $price, 2);
                } elseif ($isCarton) {
                    $piecePrice = $ppb > 0 ? ($price / $ppb) : $price;
                    $lineTotal = round($qty * $piecePrice, 2);
                } else {
                    $lineTotal = round($qty * $price, 2);
                }

                $itemDisc = 0;

                PurchaseReturnItem::create([
                    'purchase_return_id' => $return->id,
                    'product_id' => $productId,
                    'qty' => $qty, // Pieces
                    'price' => $price,
                    'item_discount' => $itemDisc,
                    'unit' => $origItem->unit ?? ($isCarton ? 'Carton' : 'pc'),
                    'line_total' => $lineTotal,
                    'color' => $colorField,
                ]);

                // Check for weight product conversion factor
                $stockQty = $qty;
                if ($sizeMode === 'by_kg' || $sizeMode === 'by_gm') {
                    $colorField = $request->color[$index] ?? null;
                    if (!empty($colorField)) {
                        try {
                            $b64Decoded = base64_decode($colorField, true);
                            $variantData = $b64Decoded !== false ? json_decode($b64Decoded, true) : null;
                            if (!is_array($variantData)) {
                                $variantData = is_string($colorField) ? json_decode($colorField, true) : $colorField;
                            }
                            if (is_array($variantData) && isset($variantData['conv_factor'])) {
                                $factor = (float)$variantData['conv_factor'];
                                if ($factor > 0) {
                                    $stockQty = $stockQty * $factor;
                                }
                            }
                        } catch (\Exception $e) {}
                    }
                }

                // Update Stock (DECREMENT)
                $stockWhId = (int) $validated['warehouse_id'];
                $stock = WarehouseStock::where('warehouse_id', $stockWhId)
                    ->where('product_id', $productId)
                    ->lockForUpdate()
                    ->first();

                if (!$stock) {
                    $fallbackWhId = $origItem->warehouse_id ?? ($purchase->warehouse_id ?? null);
                    if ($fallbackWhId && (int) $fallbackWhId !== $stockWhId) {
                        $fallbackStock = WarehouseStock::where('warehouse_id', (int) $fallbackWhId)
                            ->where('product_id', $productId)
                            ->lockForUpdate()
                            ->first();
                        if ($fallbackStock) {
                            $stock = $fallbackStock;
                            $stockWhId = (int) $fallbackWhId;
                        }
                    }
                }

                $product = Product::find($productId); // Ensure fresher product data
                $ppbVal = ($product && $product->pieces_per_box > 0) ? $product->pieces_per_box : 1;

                if ($stock) {
                    // Use total_pieces as primary source of truth to avoid losing loose pieces, fallback only if zero
                    $currentTotalPieces = $stock->total_pieces;
                    if ($currentTotalPieces == 0 && $stock->quantity > 0) {
                        $currentTotalPieces = $stock->quantity * $ppbVal;
                    }

                    // Subtract Return Qty (Pieces)
                    $newTotalPieces = max(0, $currentTotalPieces - $stockQty);

                    $stock->total_pieces = $newTotalPieces;
                    $stock->quantity = $newTotalPieces / $ppbVal; // Convert back to Boxes
                    $stock->save();
                } else {
                    // Handle gracefully if stock record does not exist
                    WarehouseStock::create([
                        'warehouse_id' => $stockWhId,
                        'product_id' => $productId,
                        'total_pieces' => 0,
                        'quantity' => 0,
                        'price' => 0,
                    ]);
                }

                // Keep batches and serials synced on Purchase Return
                if ($product) {
                    app(\App\Services\StockBalanceService::class)->syncBatchStockOnDelta(
                        $product,
                        $stockWhId,
                        -1 * (float) $stockQty,
                        null,
                        $origItem->batch_id ?? null,
                        $origItem->batch_no ?? null,
                        "Purchase Return #{$nextInvoice}"
                    );

                    if ($origItem && !empty($origItem->serials)) {
                        $serialsArr = is_string($origItem->serials) ? json_decode($origItem->serials, true) : (array) $origItem->serials;
                        if (is_array($serialsArr) && count($serialsArr) > 0) {
                            $toReturn = array_slice($serialsArr, 0, (int) $stockQty);
                            \App\Models\ProductSerial::where('product_id', $productId)
                                ->whereIn('serial_number', $toReturn)
                                ->update(['status' => 'returned']);
                        }
                    }
                }

                // Prepare Stock Movement
                $movements[] = [
                    'product_id' => $productId,
                    'type' => 'out', // Return OUT to vendor
                    'qty' => $stockQty,
                    'ref_type' => 'PURCHASE_RETURN',
                    'ref_id' => $return->id,
                    'note' => "Purchase Return #{$nextInvoice} | Warehouse #{$stockWhId}",
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $subtotal += $lineTotal;
                $totalItemDiscount += $itemDisc;
            }

            // Bulk Insert Movements
            if (! empty($movements)) {
                DB::table('stock_movements')->insert($movements);
            }

            $netAmount = ($subtotal - $totalItemDiscount) - ($request->extra_discount ?? 0);

            // 4. Handle Refund Payment
            $totalPaid = 0;
            $paymentAccountIds = (array) ($request->input('payment_account_id') ?? []);
            $paymentAmounts = (array) ($request->input('payment_amount') ?? []);

            $balanceService = app(\App\Services\BalanceService::class);
            $voucherService = app(\App\Services\VoucherService::class);
            $apId = $balanceService->getAccountsPayableId();
            $defaultCashAccId = $balanceService->getCashAccountId();

            foreach ($paymentAmounts as $idx => $rawAmt) {
                $amt = (float) $rawAmt;
                if ($amt > 0) {
                    $accId = $paymentAccountIds[$idx] ?? null;
                    if (empty($accId)) {
                        $accId = $defaultCashAccId;
                    }
                    $accId = (int) $accId;

                    $totalPaid += $amt;

                    // Create Receipt Voucher via Service (Cash/Bank In from Vendor Refund)
                    $voucherData = [
                        'voucher_type' => \App\Models\VoucherMaster::TYPE_RECEIPT,
                        'date' => $validated['return_date'],
                        'status' => \App\Models\VoucherMaster::STATUS_POSTED,
                        'payment_from' => 'Vendor',
                        'party_type' => \App\Models\Vendor::class,
                        'party_id' => $validated['vendor_id'],
                        'remarks' => "Refund for Return #{$nextInvoice}" . ($purchase ? " (Ref Purchase: {$purchase->invoice_no})" : ""),
                    ];

                    $lines = [
                        [
                            'account_id' => $accId,
                            'debit' => $amt,
                            'credit' => 0,
                            'narration' => "Cash Refund Received (#{$nextInvoice})"
                        ],
                        [
                            'account_id' => $apId,
                            'debit' => 0,
                            'credit' => $amt,
                            'narration' => "Refund from Vendor (#{$nextInvoice})"
                        ]
                    ];

                    $voucherService->createVoucher($voucherData, $lines, auth()->id());
                }
            }

            $return->update([
                'bill_amount' => $subtotal,
                'item_discount' => $totalItemDiscount,
                'net_amount' => $netAmount,
                'paid' => $totalPaid,
                'balance' => $netAmount - $totalPaid,
            ]);

            // Update Purchase Status (only if full return)
            if ($purchase) {
                $totalBought = $purchase->items->sum('qty');
                $totalReturned = \App\Models\PurchaseReturnItem::join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
                    ->where('purchase_returns.purchase_id', $purchase->id)
                    ->sum('purchase_return_items.qty');
                
                if ($totalReturned >= $totalBought) {
                    $purchase->update(['status_purchase' => 'Returned']);
                }
            }

            // 5. Update Vendor Ledger & Accounting
            // A. Create General Ledger Voucher for Return (Debit Vendor, Credit Purchase Return)
            $transactionService = app(\App\Services\TransactionService::class);
            if (method_exists($transactionService, 'createPurchaseReturnVoucher')) {
                 $transactionService->createPurchaseReturnVoucher($return);
            }

            // B. Update Legacy Vendor Ledger
            // Logic: Return reduces Payable (Debit Vendor).
            // Refund increases Payable back (Credit Vendor) - effectively clearing the Debit.
            
            $balanceChange = -($netAmount - $totalPaid); // Reduces payable

            // Using VendorLedger table manual update for legacy views
            $ledger = \App\Models\VendorLedger::where('vendor_id', $validated['vendor_id'])->latest()->first();
            $currentClosing = $ledger ? $ledger->closing_balance : 0; // Current closing
            
            // Note: VendorLedger table structure does not support transaction history (no date/desc columns),
            // so we treat it as a Balance Snapshot.
            \App\Models\VendorLedger::updateOrCreate(
                ['vendor_id' => $validated['vendor_id']],
                [
                    'admin_or_user_id' => auth()->id(),
                    'opening_balance' => $ledger ? $ledger->opening_balance : 0, 
                    'previous_balance' => $currentClosing,
                    'closing_balance' => $currentClosing + $balanceChange,
                ]
            );

            DB::commit();

            return redirect()->route('purchase.return.index')->with('success', 'Purchase return processed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing return: '.$e->getMessage());
        }
    }

    public function purchaseReturnIndex()
    {
        $returns = \App\Models\PurchaseReturn::with(['vendor', 'warehouse', 'purchase'])->latest()->get();
        
        // Calculate updated financial details for each return
        $returns->each(function ($return) {
            if ($return->purchase) {
                $purchase = $return->purchase;
                
                // Original Purchase Amounts
                $return->original_net_amount = $purchase->net_amount;
                $return->original_paid_amount = $purchase->paid_amount;
                $return->original_due_amount = $purchase->due_amount;
                
                // Calculate total returns for this purchase
                $totalReturned = \App\Models\PurchaseReturn::where('purchase_id', $purchase->id)
                    ->sum('net_amount');
                
                // New amounts after return(s)
                $return->new_net_amount = max(0, $purchase->net_amount - $totalReturned);
                $return->new_due_amount = max(0, $purchase->due_amount - $return->net_amount);
                $return->total_returned = $totalReturned;
            }
        });

        return view('admin_panel.purchase.purchase_return.index', compact('returns'));
    }

    public function viewReturn($id)
    {
        $return = \App\Models\PurchaseReturn::with(['vendor', 'warehouse', 'items.product'])->findOrFail($id);
        return view('admin_panel.purchase.purchase_return.show', compact('return'));
    }

    public function updatePurchaseSettings(Request $request)
    {
        $request->validate([
            'purchase_gst_enabled' => 'nullable',
            'purchase_gst_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $enabled = $request->has('purchase_gst_enabled') && ($request->purchase_gst_enabled == '1' || $request->purchase_gst_enabled == 'on' || $request->purchase_gst_enabled === true || $request->purchase_gst_enabled === 'true') ? '1' : '0';
        $percent = $request->filled('purchase_gst_percent') ? (float)$request->purchase_gst_percent : 18.00;

        \App\Models\Setting::set('purchase_gst_enabled', $enabled, 'purchases', 'Enable Purchase GST (%)', 'boolean');
        \App\Models\Setting::set('purchase_gst_percent', number_format($percent, 2, '.', ''), 'purchases', 'Default Purchase GST Rate (%)', 'decimal');

        return response()->json([
            'success' => true,
            'message' => 'Purchase GST settings updated successfully.',
            'data' => [
                'purchase_gst_enabled' => $enabled === '1',
                'purchase_gst_percent' => $percent,
            ]
        ]);
    }
}
