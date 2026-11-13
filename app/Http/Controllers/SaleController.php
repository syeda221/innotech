<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\Product;
use App\Models\ProductBooking;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['customer_relation', 'items.product', 'returns'])
            ->whereIn('sale_status', ['draft', 'booked', 'posted', 'returned', 'quotation']);

        // Apply Status Filter
        if ($request->has('status') && $request->status != 'all') {
            $query->where('sale_status', $request->status);
        }

        // Helper for flexible date parsing
        $parseDate = function ($dateStr) {
            if (empty($dateStr)) return null;
            $dateStr = trim($dateStr);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                return $dateStr;
            }
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $dateStr, $m)) {
                return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
            try {
                return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
            } catch (\Exception $e) {
                return $dateStr;
            }
        };

        // Apply Date Filters
        if ($request->filled('from_date')) {
            $fromDate = $parseDate($request->from_date);
            if ($fromDate) {
                $query->whereDate('created_at', '>=', $fromDate);
            }
        }
        if ($request->filled('to_date')) {
            $toDate = $parseDate($request->to_date);
            if ($toDate) {
                $query->whereDate('created_at', '<=', $toDate);
            }
        }

        // Apply Mobile Number Filter
        if ($request->filled('mobile_no')) {
            $query->whereHas('customer_relation', function ($q) use ($request) {
                $q->where('mobile', 'like', "%{$request->mobile_no}%");
            });
        }

        // Apply Bill No / Invoice No Filter
        $billNo = $request->input('bill_no') ?? $request->input('invoice_no');
        if (!empty($billNo)) {
            $billNo = trim($billNo);
            $query->where(function ($q) use ($billNo) {
                $q->where('invoice_no', 'like', "%{$billNo}%")
                  ->orWhere('reference', 'like', "%{$billNo}%")
                  ->orWhere('id', 'like', "%{$billNo}%");
            });
        }

        // Apply M.Bill # (Reference) Filter
        if ($request->filled('reference')) {
            $query->where('reference', 'like', "%{$request->reference}%");
        }

        // Apply Customer Filter
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Order By (Most recent / newest on top)
        $orderBy = $request->input('order_by', 'id');
        if ($orderBy === 'invoice_no') {
            $query->orderBy('invoice_no', 'desc')->orderBy('id', 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $sales = $query->get();

        $stats = [
            'total_count' => $sales->count(),
            'total_net' => (float) $sales->sum('total_net'),
            'total_discount' => (float) $sales->sum('total_extradiscount'),
            'posted_count' => $sales->where('sale_status', 'posted')->count(),
            'draft_count' => $sales->where('sale_status', 'draft')->count(),
            'booked_count' => $sales->where('sale_status', 'booked')->count(),
            'quotation_count' => $sales->where('sale_status', 'quotation')->count(),
            'returned_count' => $sales->whereIn('sale_status', ['returned', 1])->count(),
        ];

        // If AJAX request, return only table rows partial & stats
        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin_panel.sale.partials.sales_table_body', compact('sales'))->render(),
                'stats' => $stats
            ]);
        }

        // Load all customers for filter dropdown
        $customers = Customer::orderBy('customer_name')->get();

        return view('admin_panel.sale.index', compact('sales', 'customers', 'stats'));
    }

    public function addsale()
    {
        $customer = Customer::with('salesOfficer')->get();
        $salesOfficers = \App\Models\SalesOfficer::orderBy('name')->get();
        $warehouse = Warehouse::all();
        
        \App\Models\InvoiceSeries::ensureQuotationSeries();
        $allSeries = \App\Models\InvoiceSeries::orderBy('prefix', 'asc')->get();
        $defaultSeries = $allSeries->where('is_default', 1)->first() ?: $allSeries->first();
        $activePrefix = (request('type') === 'quotation') ? 'QUOT' : ($defaultSeries ? $defaultSeries->prefix : 'INV');
        $nextInvoiceNumber = \App\Models\InvoiceSeries::generateNextNo($activePrefix);

        $recentProducts = Product::latest()->take(12)->get();
        $allProducts = Product::where('is_active', true)->withSum('warehouseStocks', 'total_pieces')->orderBy('item_name')->get();

        // Filter accounts (Cash/Bank) for Payment Voucher
        $chequeAccount = \App\Models\Account::firstOrCreate(
            ['title' => 'Cheques in Hand'],
            [
                'head_id' => (\App\Models\AccountHead::where('name', 'LIKE', '%cheque%')->orWhere('name', 'LIKE', '%cash%')->value('id')) ?: (\App\Models\AccountHead::firstOrCreate(['name' => 'Cheque'], ['type' => 'Asset', 'level' => 1, 'opening_balance' => 0])->id ?? null),
                'opening_balance' => 0,
                'current_balance' => 0,
                'type' => 'Debit',
                'status' => 1
            ]
        );
        $accounts = \App\Models\Account::whereHas('head', function($q) {
            $q->whereIn('name', ['Cash', 'Bank', 'cash', 'bank', 'Cheque', 'cheque']);
        })->where('status', 1)
            ->where('title', '!=', 'Cheques in Hand')
            ->orderBy('title')
            ->get();

        $termsAndConditions = \App\Models\TermsAndCondition::active()->ordered()->get();
        return view('admin_panel.sale.add_sale222', compact('termsAndConditions', 
            'warehouse', 'customer', 'nextInvoiceNumber', 'accounts', 'chequeAccount', 
            'recentProducts', 'allProducts', 'allSeries', 'activePrefix', 'salesOfficers'
        ));
    }

    public function searchpname(Request $request)
    {
        $q = $request->get('q');
        $warehouseId = $request->get('warehouse_id', 1);

        $products = Product::with(['brand'])
            ->leftJoin('warehouse_stocks', function ($join) use ($warehouseId) {
                $join->on('products.id', '=', 'warehouse_stocks.product_id')
                    ->where('warehouse_stocks.warehouse_id', $warehouseId);
            })
            ->where('products.is_active', true) /* Only active products */
            ->where(function ($query) use ($q) {
                $query->where('products.item_name', 'like', "%{$q}%")
                    ->orWhere('products.item_code', 'like', "%{$q}%")
                    ->orWhere('products.barcode_path', 'like', "%{$q}%");
            })
            ->select(
                'products.*',
                'warehouse_stocks.total_pieces as wh_stock',
                'warehouse_stocks.quantity as wh_box_qty'
            )
            ->limit(50)
            ->get()
            ->map(function ($product) {
                return $product;
            });


        return response()->json($products);
    }

    public function store(Request $request)
    {
        if ($request->filled('booking_id') || $request->filled('edit_id')) {
            $saleId = $request->input('booking_id') ?: $request->input('edit_id');
            $sale = Sale::find($saleId);
            if ($sale) {
                return $this->processSale($request, $sale);
            }
        }
        return $this->processSale($request, new Sale);
    }

    public function edit(Sale $sale)
    {
        //
    }

    public function convertFromBooking($id)
    {
        $booking = ProductBooking::findOrFail($id);
        $customers = Customer::all();
        $products = explode(',', $booking->product);
        $codes = explode(',', $booking->product_code);
        $brands = explode(',', $booking->brand);
        $units = explode(',', $booking->unit);
        $prices = explode(',', $booking->per_price);
        $discounts = explode(',', $booking->per_discount);
        $qtys = explode(',', $booking->qty);
        $totals = explode(',', $booking->per_total);
        $colors_json = json_decode($booking->color, true);

        $items = [];
        foreach ($products as $index => $p) {
            $product = Product::where('item_name', trim($p))
                ->orWhere('item_code', trim($codes[$index] ?? ''))
                ->first();

            $items[] = [
                'product_id' => $product->id ?? '',
                'item_name' => $product->item_name ?? $p,
                'item_code' => $product->item_code ?? ($codes[$index] ?? ''),
                'uom' => $product->brand->name ?? ($brands[$index] ?? ''),
                'unit' => $product->unit_id ?? ($units[$index] ?? ''),
                'price' => floatval($prices[$index] ?? 0),
                'discount' => floatval($discounts[$index] ?? 0),
                'qty' => intval($qtys[$index] ?? 1),
                'total' => floatval($totals[$index] ?? 0),
                'color' => isset($colors_json[$index]) ? json_decode($colors_json[$index], true) : [],
            ];
        }

        return view('admin_panel.sale.booking_edit', [
            'Customer' => $customers,
            'booking' => $booking,
            'bookingItems' => $items,
        ]);
    }

    public function saleretun($id)
    {
        $sale = Sale::with(['items.product.unit', 'items.product.brand', 'customer_relation'])->findOrFail($id);
        $customers = Customer::all();
        $items = $this->_getSaleItems($sale);

        // Get Cash/Bank accounts for payment voucher
        $accounts = \App\Models\Account::whereHas('head', function($q) {
            $q->whereIn('name', ['Cash', 'Bank', 'cash', 'bank', 'Cheque', 'cheque']);
        })->where('status', 1)
            ->where('title', '!=', 'Cheques in Hand')
            ->orderBy('title')
            ->get();

        // Calculate return deadline from database settings
        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);
        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        // Get already returned quantities for this sale
        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed'])
            ->get();

        // Calculate max returnable for each item
        foreach ($items as &$item) {
            $returned = 0;
            foreach ($alreadyReturned as $return) {
                $returnProductIds = explode(',', $return->product_code);
                $returnQtys = explode(',', $return->qty);

                foreach ($returnProductIds as $idx => $code) {
                    if (trim($code) === $item['item_code']) {
                        $returned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $item['already_returned'] = $returned;
            $item['max_returnable'] = max(0, $item['qty'] - $returned);
        }

        return view('admin_panel.sale.return.create', [
            'sale' => $sale,
            'Customer' => $customers,
            'saleItems' => $items,
            'accounts' => $accounts,
            'returnDeadline' => $returnDeadline,
            'isWithinDeadline' => $isWithinDeadline,
            'returnDeadlineDays' => $returnDeadlineDays,
        ]);
    }

    public function storeSaleReturn(Request $request)
    {
        // Validation
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'customer' => 'required|exists:customers,id',
            'product_id' => 'required|array|min:1',
            'qty' => 'required|array',
            'payment_account_id' => 'required|array|min:1',
            'payment_amount' => 'required|array|min:1',
            'quality_status' => 'nullable|in:good,damaged,defective,pending_inspection',
        ]);

        // Load the sale
        $sale = Sale::findOrFail($request->sale_id);

        // --- VALIDATION 1: Return Deadline Policy ---
        $returnDeadlineDays = \App\Models\SystemSetting::get('return_deadline_days', 30);

        // Check if returns are disabled (0 days = no returns allowed)
        if ($returnDeadlineDays == 0) {
            return back()->with('error', 'Returns are currently disabled by store policy.');
        }

        $returnDeadline = $sale->created_at->copy()->addDays($returnDeadlineDays);
        $isWithinDeadline = now()->lte($returnDeadline);

        // Check if return is past deadline
        if (! $isWithinDeadline) {
            $user = auth()->user();
            $isSuperAdmin = $user->hasRole('Super Admin');
            $canApprovePastDeadline = $user->can_approve_past_deadline_returns ?? false;

            // Only Super Admin or users with special permission can approve past deadline returns
            if (! $isSuperAdmin && ! $canApprovePastDeadline) {
                $daysLate = now()->diffInDays($returnDeadline);

                return back()->with('error', "Return period expired! This sale is {$daysLate} days past the {$returnDeadlineDays}-day return deadline (Sale Date: {$sale->created_at->format('d/m/Y')}). Only Super Admin can approve past deadline returns.");
            }

            // Log that this is a past-deadline return approved by authorized user
            \Log::info("Past deadline return approved by {$user->name} (ID: {$user->id}) for Sale #{$sale->id}");
        }

        // --- VALIDATION 2: Partial Return - Prevent returning more than sold ---
        $product_ids = $request->product_id ?? [];
        $quantities = $request->qty ?? [];

        // Get already returned quantities
        $alreadyReturned = \App\Models\SaleReturn::where('sale_id', $sale->id)
            ->whereIn('return_status', ['approved', 'completed', 'pending']) // Include pending to prevent duplicate submissions
            ->get();

        foreach ($product_ids as $index => $product_id) {
            $returnQty = (float) ($quantities[$index] ?? 0);

            if ($returnQty <= 0) {
                continue;
            }

            // Find original sale item
            $saleItem = $sale->items->where('product_id', $product_id)->first();

            if (! $saleItem) {
                return back()->with('error', "Product ID {$product_id} was not found in the original sale.");
            }

            // Calculate already returned quantity for this product
            $previouslyReturned = 0;
            foreach ($alreadyReturned as $return) {
                $returnProductIds = $request->product_id;
                $returnQtys = explode(',', $return->qty);

                // Match by product_id in the combined string
                $productCodes = explode(',', $return->product_code);
                foreach ($productCodes as $idx => $code) {
                    $product = \App\Models\Product::where('item_code', trim($code))->first();
                    if ($product && $product->id == $product_id) {
                        $previouslyReturned += (float) ($returnQtys[$idx] ?? 0);
                    }
                }
            }

            $maxReturnable = $saleItem->total_pieces - $previouslyReturned;

            if ($returnQty > $maxReturnable) {
                $productName = $saleItem->product_name ?? "Product #{$product_id}";

                return back()->with('error', "Cannot return {$returnQty} pieces of '{$productName}'. Maximum returnable: {$maxReturnable} pieces (Sold: {$saleItem->total_pieces}, Already Returned: {$previouslyReturned}).");
            }
        }

        DB::beginTransaction();

        try {
            $warehouseId = (int) ($request->input('warehouse_id', 1));

            $product_names = $request->product ?? [];
            $product_codes = $request->item_code ?? [];
            $brands = $request->uom ?? [];
            $units = $request->unit ?? [];
            $prices = $request->price ?? [];
            $discounts = $request->item_disc ?? [];
            $totals = $request->total ?? [];
            $colors = $request->color ?? [];

            $combined_products = $combined_codes = $combined_brands = $combined_units = [];
            $combined_prices = $combined_discounts = $combined_qtys = $combined_totals = $combined_colors = [];

            $jsonItems = [];
            $total_items = 0;

            // Process each returned item logic
            foreach ($product_ids as $index => $product_id) {
                $qty = max(0.0, (float) ($quantities[$index] ?? 0));
                $price = max(0.0, (float) ($prices[$index] ?? 0));

                if (! $product_id || $qty <= 0) {
                    continue;
                }

                // Add to JSON structure for later processing
                $jsonItems[] = [
                    'product_id' => $product_id,
                    'qty' => $qty,
                    'price' => $price,
                ];

                $combined_products[] = $product_names[$index] ?? '';
                $combined_codes[] = $product_codes[$index] ?? '';
                $combined_brands[] = $brands[$index] ?? '';
                $combined_units[] = $units[$index] ?? '';
                $combined_prices[] = $price;
                $combined_discounts[] = $discounts[$index] ?? 0;
                $combined_qtys[] = $qty;
                $combined_totals[] = $totals[$index] ?? 0;

                $decodedColor = $colors[$index] ?? [];
                $combined_colors[] = is_array($decodedColor) ? json_encode($decodedColor) : json_encode((array) json_decode($decodedColor, true));

                // NOTE: Stock updates are now deferred to the approval step
                $total_items += $qty;
            }

            // Prepare Payment Details
            $paymentAccountIds = $request->payment_account_id ?? [];
            $paymentAmounts = $request->payment_amount ?? [];
            $jsonPayments = [];

            foreach ($paymentAccountIds as $idx => $id) {
                if (($paymentAmounts[$idx] ?? 0) > 0) {
                    $jsonPayments[] = ['account_id' => $id, 'amount' => $paymentAmounts[$idx]];
                }
            }

            // Create Sale Return Record
            $saleReturn = new SaleReturn;
            $saleReturn->sale_id = $request->sale_id;
            $saleReturn->customer = $request->customer;
            $saleReturn->reference = $request->reference;
            $saleReturn->product = implode(',', $combined_products);
            $saleReturn->product_code = implode(',', $combined_codes);
            $saleReturn->brand = implode(',', $combined_brands);
            $saleReturn->unit = implode(',', $combined_units);
            $saleReturn->per_price = implode(',', $combined_prices);
            $saleReturn->per_discount = implode(',', $combined_discounts);
            $saleReturn->qty = implode(',', $combined_qtys);
            $saleReturn->per_total = implode(',', $combined_totals);
            $saleReturn->color = json_encode($combined_colors);
            $saleReturn->total_amount_Words = $request->total_amount_Words;
            $saleReturn->total_bill_amount = $request->total_subtotal;
            $saleReturn->total_extradiscount = $request->total_extra_cost;
            $saleReturn->total_net = $request->total_net;
            $saleReturn->cash = $request->cash;
            $saleReturn->card = $request->card;
            $saleReturn->change = $request->change;
            $saleReturn->total_items = $total_items;
            $saleReturn->return_note = $request->return_note;

            // Store comprehensive data for later processing
            $saleReturn->refund_details = json_encode([
                'items' => $jsonItems,
                'payments' => $jsonPayments,
                'warehouse_id' => $warehouseId,
                'customer_id' => $request->customer,
                'total_net' => $request->total_net,
            ]);

            // --- WORKFLOW STATUS FIELDS ---
            $autoApproveThreshold = \App\Models\SystemSetting::get('return_auto_approve_threshold', 0);
            $returnAmount = (float) $request->total_net;
            $requireApproval = \App\Models\SystemSetting::get('return_require_approval', true);

            if (($autoApproveThreshold > 0 && $returnAmount <= $autoApproveThreshold) || ! $requireApproval) {
                $saleReturn->return_status = 'approved';
                // Dates set in _processApproval
            } else {
                $saleReturn->return_status = 'pending';
            }

            // Quality Status
            $saleReturn->quality_status = $request->quality_status ?? 'pending_inspection';
            if ($request->quality_status && in_array($request->quality_status, ['good', 'damaged', 'defective'])) {
                $saleReturn->inspected_by = auth()->id();
            }

            // Return Deadline
            $saleReturn->return_deadline = $returnDeadline;
            $saleReturn->is_within_deadline = $isWithinDeadline;

            $saleReturn->save();

            // If Approved, process transactions immediately
            if ($saleReturn->return_status === 'approved') {
                $this->_processApproval($saleReturn);
            }

            DB::commit();

            // Create notification for super admins
            try {
                \App\Models\SystemNotification::createSaleReturnNotification($saleReturn, $sale);
            } catch (\Exception $e) {
                \Log::error('Notification creation failed: '.$e->getMessage());
                // Don't fail the return process if notification fails
            }

            return redirect()->route('sale.index')->with('success', 'Sale return processed successfully with journal entries and payment voucher.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Sale Return Error: '.$e->getMessage());

            return back()->with('error', 'Sale return failed: '.$e->getMessage());
        }
    }

    public function salereturnview()
    {
        $SaleReturns = SaleReturn::with(['sale.customer_relation', 'customer_relation'])->orderBy('id', 'desc')->get();

        // Calculate stats
        $stats = [
            'total' => $SaleReturns->count(),
            'pending' => $SaleReturns->where('return_status', 'pending')->count(),
            'approved' => $SaleReturns->where('return_status', 'approved')->count(),
            'rejected' => $SaleReturns->where('return_status', 'rejected')->count(),
            'completed' => $SaleReturns->where('return_status', 'completed')->count(),
        ];

        return view('admin_panel.sale.return.index', compact('SaleReturns', 'stats'));
    }

    /**
     * Approve a sale return
     */
    public function approveReturn($id)
    {
        try {
            $return = SaleReturn::findOrFail($id);

            // Check if already processed
            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            DB::beginTransaction();

            // If we have refund_details, it means we used the new workflow where
            // actions were deferred until approval.
            if (! empty($return->refund_details)) {
                $this->_processApproval($return);
            } else {
                // Legacy support: If no refund_details, it means stock/accounting
                // were likely done at creation time (old behavior), so just update status.
                $return->return_status = 'approved';
                $return->approved_by = auth()->id();
                $return->approved_at = now();
                $return->save();
            }

            DB::commit();

            return back()->with('success', 'Return approved successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Return approval failed: '.$e->getMessage());

            return back()->with('error', 'Failed to approve return: '.$e->getMessage());
        }
    }

    /**
     * Process the physical and financial transactions for a return
     */
    private function _processApproval($saleReturn)
    {
        $data = is_string($saleReturn->refund_details) ? json_decode($saleReturn->refund_details, true) : $saleReturn->refund_details;

        if (! $data) {
            throw new \Exception('Invalid return data for processing');
        }

        $warehouseId = $data['warehouse_id'] ?? 1; // Default to 1 if missing
        $items = $data['items'] ?? [];
        $payments = $data['payments'] ?? [];
        $srMovements = [];

        // 1. Update Stock & Original Sale Items
        $sale = Sale::find($saleReturn->sale_id);

        foreach ($items as $item) {
            $productId = $item['product_id'];
            $qty = (float) $item['qty'];

            if ($qty <= 0) {
                continue;
            }

            // Update Warehouse Stock
            $stock = \App\Models\WarehouseStock::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->first();

            // Find variant color from SaleItem
            $saleColor = null;
            if ($sale && $sale->items) {
                $sItem = $sale->items->where('product_id', $productId)->first();
                if ($sItem) $saleColor = $sItem->color;
            }

            $stockQty = $qty;
            if (!empty($saleColor)) {
                try {
                    $b64Decoded = base64_decode($saleColor, true);
                    $variantData = $b64Decoded !== false ? json_decode($b64Decoded, true) : null;
                    if (!is_array($variantData)) {
                        $variantData = is_string($saleColor) ? json_decode($saleColor, true) : $saleColor;
                    }
                    if (is_array($variantData) && isset($variantData['conv_factor'])) {
                        $factor = (float)$variantData['conv_factor'];
                        if ($factor > 0) {
                            $stockQty = $qty * $factor;
                        }
                    }
                } catch (\Exception $e) {}
            }

            if ($stock) {
                $stock->total_pieces += $stockQty;
                $prod = Product::find($productId);
                $ppb = $prod->pieces_per_box > 0 ? $prod->pieces_per_box : 1;
                $stock->quantity += ($stockQty / $ppb);
                $stock->save();
            }

            // Prepare Stock Movement
            $srMovements[] = [
                'product_id' => $productId,
                'type' => 'in',
                'qty' => $stockQty,
                'ref_type' => 'SR',
                'ref_id' => $saleReturn->id,
                'note' => 'Sale return approved',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Update Sales Item (Decrement sold quantity and revenue)
            if ($sale && $sale->items) {
                $saleItem = $sale->items->where('product_id', $productId)->first();
                if ($saleItem) {
                    $returnPrice = (float) ($saleItem->price ?? 0);
                    // Calculate discount per piece to ensure net total is correctly reduced
                    $discountPerPiece = $saleItem->total_pieces > 0 ? ($saleItem->discount_amount / $saleItem->total_pieces) : 0;
                    
                    $saleItem->total_pieces = max(0, $saleItem->total_pieces - $qty);
                    $prod = Product::find($productId);
                    $ppb = $prod->pieces_per_box > 0 ? $prod->pieces_per_box : 1;
                    $saleItem->qty = $saleItem->total_pieces / $ppb;
                    $saleItem->loose_pieces = $saleItem->total_pieces % $ppb;
                    
                    // Recalculate item total after return
                    $newGross = $saleItem->total_pieces * $saleItem->price;
                    $newDiscount = $saleItem->total_pieces * $discountPerPiece;
                    $saleItem->discount_amount = $newDiscount;
                    $saleItem->total = max(0, $newGross - $newDiscount);
                    
                    $saleItem->save();
                }
            }
        }

        // 2. Update Sale Header Totals
        if ($sale) {
            $allSaleItems = \App\Models\SaleItem::where('sale_id', $sale->id)->get();
            $sale->total_bill_amount = $allSaleItems->sum('total');
            // Recalculate Net (Accounting for extra discount if any)
            $sale->total_net = max(0, $sale->total_bill_amount - ($sale->total_extradiscount ?? 0));
            $sale->total_items = $allSaleItems->sum('total_pieces');
            $sale->save();
        }

        // Inert Stock Movements
        if (! empty($srMovements)) {
            DB::table('stock_movements')->insert($srMovements);
        }

        // 2. Journal Entries
        $customer = Customer::find($data['customer_id'] ?? $saleReturn->customer);
        $returnAmount = (float) $saleReturn->total_net;
        $date = now()->format('Y-m-d');
        $journalService = app(\App\Services\JournalEntryService::class);
        $balanceService = app(\App\Services\BalanceService::class);

        // Accounts
        $arAccountId = $balanceService->getAccountsReceivableId();
        $salesAccountId = $balanceService->getSalesRevenueId();

        if ($returnAmount > 0) {
            // Debit Sales Return (or Sales Revenue)
            $journalService->recordEntry(
                $saleReturn,
                $salesAccountId,
                $returnAmount,
                0,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date
            );

            // Credit Customer (AR)
            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                0,
                $returnAmount,
                "Sale Return #{$saleReturn->id} - Invoice #{$sale->invoice_no}",
                $date,
                $customer
            );
        }

        // 3. Process Payments (Refunds)
        $totalPaid = 0;
        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            $accountId = $payment['account_id'];

            if ($amount <= 0 || ! $accountId) {
                continue;
            }

            $totalPaid += $amount;

            // Credit Cash/Bank (Money Out)
            $journalService->recordEntry(
                $saleReturn,
                $accountId,
                0,
                $amount,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date
            );
        }

        // Debit Customer (AR) for the refund amount (since we paid them back)
        // Logic: Return credited AR (balance down). Refund debits AR (balance up/neutralized).
        // Net result: Sales reversed, Cash paid out. Customer balance neutral.
        if ($totalPaid > 0) {
            $journalService->recordEntry(
                $saleReturn,
                $arAccountId,
                $totalPaid,
                0,
                "Refund Payment for Sale Return #{$saleReturn->id}",
                $date,
                $customer
            );

            // Create Payment Voucher Record
            \App\Models\CustomerPayment::create([
                'customer_id' => $customer->id,
                'admin_or_user_id' => auth()->id(),
                'voucher_no' => 'SR-'.$saleReturn->id,
                'payment_date' => $date,
                'payment_method' => 'Cash',
                'amount' => $totalPaid,
                'note' => "Refund for Sale Return #{$saleReturn->id}",
                'type' => 'refund',
            ]);

            // Auto-Generate Payment Voucher (PV) for Refund
            try {
                $pvid = \App\Models\PaymentVoucher::generateInvoiceNo();
                // Extract accounts and amounts
                $pvAccounts = [];
                $pvAmounts = [];
                foreach ($payments as $p) {
                    if (($p['amount'] ?? 0) > 0) {
                        $pvAccounts[] = $p['account_id'];
                        $pvAmounts[] = $p['amount'];
                    }
                }

                \App\Models\PaymentVoucher::create([
                    'pvid' => $pvid,
                    'party_id' => $customer->id,
                    'type' => 'customer',
                    'total_amount' => $totalPaid,
                    'payment_date' => $date,
                    'row_account_id' => json_encode($pvAccounts),
                    'row_account_head' => json_encode([]),
                    'row_amount' => json_encode($pvAmounts),
                    'remarks' => "Refund for Sale Return #{$saleReturn->id}",
                ]);
            } catch (\Exception $e) {
                \Log::error('Refund PV Creation Failed: '.$e->getMessage());
            }
        }

        // 4. Update Status
        $saleReturn->return_status = 'approved';
        $saleReturn->approved_by = auth()->id();
        $saleReturn->approved_at = now();
        $saleReturn->save();
    }

    /**
     * Reject a sale return
     */
    public function rejectReturn(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        try {
            $return = SaleReturn::findOrFail($id);

            // Check if already processed
            if ($return->return_status !== 'pending') {
                return back()->with('error', 'This return has already been processed.');
            }

            // Update return status
            $return->return_status = 'rejected';
            $return->approved_by = auth()->id();
            $return->approved_at = now();
            $return->rejection_reason = $request->rejection_reason;
            $return->save();

            return back()->with('success', 'Return rejected successfully!');

        } catch (\Exception $e) {
            \Log::error('Return rejection failed: '.$e->getMessage());

            return back()->with('error', 'Failed to reject return: '.$e->getMessage());
        }
    }

    /**
     * Show detailed sale return information
     */
    public function saleReturnDetail($id)
    {
        $saleReturn = SaleReturn::with('customer_relation')->findOrFail($id);
        $sale = Sale::with(['customer_relation', 'items.product'])->findOrFail($saleReturn->sale_id);

        // Parse return items
        $returnItems = [];
        $productNames = explode(',', $saleReturn->product);
        $productCodes = explode(',', $saleReturn->product_code);
        $brands = explode(',', $saleReturn->brand);
        $units = explode(',', $saleReturn->unit);
        $prices = explode(',', $saleReturn->per_price);
        $discounts = explode(',', $saleReturn->per_discount);
        $quantities = explode(',', $saleReturn->qty);
        $totals = explode(',', $saleReturn->per_total);

        for ($i = 0; $i < count($productNames); $i++) {
            $returnItems[] = [
                'product_name' => $productNames[$i] ?? '',
                'product_code' => $productCodes[$i] ?? '',
                'brand' => $brands[$i] ?? '',
                'unit' => $units[$i] ?? '',
                'price' => $prices[$i] ?? 0,
                'discount' => $discounts[$i] ?? 0,
                'quantity' => $quantities[$i] ?? 0,
                'total' => $totals[$i] ?? 0,
            ];
        }

        // Get payment details
        $payments = \App\Models\CustomerPayment::where('note', 'like', "%Sale Return #{$saleReturn->id}%")->get();

        // Get journal entries
        $journalEntries = \App\Models\JournalEntry::where('source_type', 'App\Models\SaleReturn')
            ->where('source_id', $saleReturn->id)
            ->with('account')
            ->get();

        // Get approver and inspector info
        $approver = $saleReturn->approved_by ? \App\Models\User::find($saleReturn->approved_by) : null;
        $inspector = $saleReturn->inspected_by ? \App\Models\User::find($saleReturn->inspected_by) : null;

        return view('admin_panel.sale.return.detail', compact(
            'saleReturn',
            'sale',
            'returnItems',
            'payments',
            'journalEntries',
            'approver',
            'inspector'
        ));
    }

    public function saleinvoice($id)
    {
        $sale = Sale::with(['customer_relation.salesOfficer'])->findOrFail($id);
        if ($sale->customer_id) {
            \App\Models\Cheque::reconcileUnreversedBouncedForCustomer($sale->customer_id);
            $sale->refresh();
        }
        $items = $this->_getSaleItems($sale);
        $isEstimate = request()->query('type') === 'estimate';

        // Calculate Balances for Invoice
        $previousBalance = 0;
        $currentBalance = 0;

        // Find the Journal Entry for this Sale (Debit side)
        // We look for the entry by its description which matches the invoice number
        $journalEntry = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
            ->where('party_id', $sale->customer_id)
            ->where('description', "Sale Invoice #{$sale->invoice_no}")
            ->first();

        if ($journalEntry && $sale->customer_id) {
            // Calculate Previous Balance: Sum of (Dr - Cr) for all entries BEFORE this one
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));
        } else {
            // Fallback if no journal entry (not posted yet)
            // The customer's current balance is effectively the previous balance before this sale is posted
            if ($sale->customer_id) {
                $balanceService = app(\App\Services\BalanceService::class);
                $previousBalance = $balanceService->getCustomerBalance($sale->customer_id);
            } else {
                $previousBalance = 0;
            }
        }
        $paidOnSale = min((float) ($sale->total_net ?? 0), max(0, (float) ($sale->cash ?? 0) + (float) ($sale->card ?? 0)));
        $currentBalance = $previousBalance + (float) ($sale->total_net ?? 0) - $paidOnSale;

        return view('admin_panel.sale.saleinvoice', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
            'isEstimate' => $isEstimate,
        ]);
    }

    public function getSaleDetailsModal($id)
    {
        $sale = Sale::with(['customer_relation.salesOfficer', 'salesOfficer', 'items.product'])->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        $sumGross = 0;
        $sumDisc = 0;
        $sumExclGst = 0;
        $sumSalesTax = 0;
        $sumFurtherTax = 0;

        foreach ($items as $item) {
            $qty = (float)($item['qty_box'] ?? $item['qty'] ?? 0);
            $price = (float)($item['price'] ?? 0);
            $gross = $item['gross_amount'] > 0 ? $item['gross_amount'] : ($qty * $price);
            
            $sumGross += $gross;
            $sumDisc += (float)($item['discount_amount'] ?? 0);
            $sumExclGst += (float)($item['exclusive_gst_amount'] ?? 0);
            $sumSalesTax += (float)($item['sales_tax_amount'] ?? 0);
            $sumFurtherTax += (float)($item['further_tax_amount'] ?? 0);
        }

        return response()->json([
            'status' => true,
            'sale' => [
                'id' => $sale->id,
                'invoice_no' => $sale->invoice_no,
                'created_at_formatted' => $sale->created_at ? $sale->created_at->format('d/m/Y h:i A') : date('d/m/Y'),
                'sale_status' => $sale->sale_status,
                'reference' => $sale->reference,
                'po_no' => $sale->po_no,
                'po_date' => $sale->po_date ? \Carbon\Carbon::parse($sale->po_date)->format('d/m/Y') : null,
                'sales_officer_name' => $sale->salesOfficer->name ?? ($sale->customer_relation->salesOfficer->name ?? null),
                'return_note' => $sale->return_note,
                'total_bill_amount' => (float)$sale->total_bill_amount,
                'total_extradiscount' => (float)$sale->total_extradiscount,
                'total_net' => (float)$sale->total_net,
                'cash' => (float)$sale->cash,
                'card' => (float)$sale->card,
                'change' => (float)$sale->change,
                'customer_name' => $sale->walkin_name ?? ($sale->customer_relation->customer_name ?? 'Walking Customer'),
                'customer_code' => $sale->customer_relation->customer_id ?? '',
                'customer_mobile' => $sale->customer_relation->mobile ?? '—',
                'customer_address' => $sale->customer_relation->address ?? '—',
            ],
            'items' => $items,
            'stats' => [
                'sum_gross' => $sumGross,
                'sum_disc' => $sumDisc,
                'sum_excl_gst' => $sumExclGst,
                'sum_sales_tax' => $sumSalesTax,
                'sum_further_tax' => $sumFurtherTax,
            ]
        ]);
    }

    public function saleedit($id)
    {
        // 1. Fetch Sale with relations (including nested items.product for pre-fill)
        // Eager load warehouseStocks to display stock in edit view
        $sale = Sale::with(['items.product.warehouseStocks', 'customer_relation'])->findOrFail($id);

        if (in_array($sale->sale_status, ['cancelled', 'returned'])) {
            return redirect()->route('sale.index')->with('error', 'Cannot edit a '.$sale->sale_status.' sale.');
        }

        // 2. Data for Dropdowns (Same as addsale)
        $customer = Customer::with('salesOfficer')->get();
        $warehouse = Warehouse::all();
        $salesOfficers = \App\Models\SalesOfficer::orderBy('name')->get();
        \App\Models\InvoiceSeries::ensureQuotationSeries();
        $allSeries = \App\Models\InvoiceSeries::orderBy('prefix', 'asc')->get();
        $defaultSeries = $allSeries->where('is_default', 1)->first() ?: $allSeries->first();
        $activePrefix = ($sale->sale_status === 'quotation' || request('type') === 'quotation') ? 'QUOT' : ($defaultSeries ? $defaultSeries->prefix : 'INV');
        $recentProducts = Product::latest()->take(12)->get();
        $allProducts = Product::where('is_active', true)->withSum('warehouseStocks', 'total_pieces')->orderBy('item_name')->get();

        // Filter accounts (Cash/Bank) for Receipt Voucher
        $chequeAccount = \App\Models\Account::where('title', 'Cheques in Hand')->first();
        $existingCheque = \App\Models\Cheque::where('note', 'like', "%#{$sale->invoice_no}%")->first();
        $accounts = \App\Models\Account::whereHas('head', function($q) {
            $q->whereIn('name', ['Cash', 'Bank', 'cash', 'bank', 'Cheque', 'cheque']);
        })->where('status', 1)
            ->where('title', '!=', 'Cheques in Hand')
            ->orderBy('title')
            ->get();

        // 3. Reuse nextInvoiceNumber var for current invoice no (view expects this variable name)
        $nextInvoiceNumber = $sale->invoice_no;

        // 4. Return the Edit Sale View
        $termsAndConditions = \App\Models\TermsAndCondition::active()->ordered()->get();
        return view('admin_panel.sale.edit_sale', compact('termsAndConditions', 'warehouse', 'customer', 'nextInvoiceNumber', 'accounts', 'chequeAccount', 'existingCheque', 'sale', 'recentProducts', 'allProducts', 'allSeries', 'activePrefix', 'salesOfficers'));
    }

    public function updatesale(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);
        if (in_array($sale->sale_status, ['cancelled', 'returned'])) {
            return redirect()->back()->with('error', 'Cannot edit a '.$sale->sale_status.' sale.');
        }

        return $this->processSale($request, $sale);
    }

    public function saledc($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saledc', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function saledcThermal($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        $items = $this->_getSaleItems($sale);

        return view('admin_panel.sale.saledc_thermal', ['sale' => $sale, 'saleItems' => $items]);
    }

    public function salereceipt($id)
    {
        $sale = Sale::with('customer_relation')->findOrFail($id);
        if ($sale->customer_id) {
            \App\Models\Cheque::reconcileUnreversedBouncedForCustomer($sale->customer_id);
            $sale->refresh();
        }
        $items = $this->_getSaleItems($sale);

        // Logic for Previous Balance (copied from saleinvoice)
        $journalEntry = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
            ->where('party_id', $sale->customer_id)
            ->where('description', "Sale Invoice #{$sale->invoice_no}")
            ->first();

        $previousBalance = 0;
        $currentBalance = 0;

        if ($journalEntry && $sale->customer_id) {
            // Calculate Previous Balance: Sum of (Dr - Cr) for all entries BEFORE this one
            $previousBalance = \App\Models\JournalEntry::where('party_type', \App\Models\Customer::class)
                ->where('party_id', $sale->customer_id)
                ->where('id', '<', $journalEntry->id)
                ->sum(\Illuminate\Support\Facades\DB::raw('debit - credit'));
        } else {
            // Fallback if no journal entry (legacy or draft)
            if ($sale->customer_id) {
                $balanceService = app(\App\Services\BalanceService::class);
                $previousBalance = $balanceService->getCustomerBalance($sale->customer_id);
            }
        }
        $paidOnSale = min((float) ($sale->total_net ?? 0), max(0, (float) ($sale->cash ?? 0) + (float) ($sale->card ?? 0)));
        $currentBalance = $previousBalance + (float) ($sale->total_net ?? 0) - $paidOnSale;

        return view('admin_panel.sale.salereceipt', [
            'sale' => $sale,
            'saleItems' => $items,
            'previousBalance' => $previousBalance,
            'currentBalance' => $currentBalance,
        ]);
    }

    public function postFinal(Request $request)
    {
        // If the request contains full form data, we process it as an update + post
        // If it only contains an ID, we just transition state?
        // Based on previous code, it receives form data.
        $request->merge(['action' => 'post']);

        if ($request->booking_id) {
            $sale = Sale::findOrFail($request->booking_id);
            return $this->processSale($request, $sale);
        }

        return $this->processSale($request, new Sale);
    }

    private function processSale(Request $request, Sale $sale)
    {
        $isWalkin = $request->input('is_walkin') == '1';

        $rules = [
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'nullable|exists:products,id',
            'qty' => 'required|array|min:1',
            'warehouse_id' => 'nullable|array',
        ];

        if ($isWalkin) {
            $rules['walkin_name'] = 'required|string|max:255';
            $request->merge(['customer' => null]);
        } else {
            $rules['customer'] = 'required|exists:customers,id';
        }

        $request->validate($rules);

        // Prevent duplicate products (removed to allow multiple variants of the same product)
        // if (count($request->product_id) !== count(array_unique($request->product_id))) {
        //     throw \Illuminate\Validation\ValidationException::withMessages(['product_id' => 'Duplicate products are not allowed in a single sale. Please merge quantities.']);
        // }

        $status = in_array($request->action, ['post', 'sale', 'posted']) ? 'posted' : ($request->action === 'quotation' || ($sale->exists && $sale->sale_status === 'quotation' && empty($request->action)) ? 'quotation' : 'booked');

        // Concurrency Safe Transaction
        try {
            return DB::transaction(function () use ($request, $sale, $status, $isWalkin) {

                // If this is an update to a previously posted sale, rollback its impact first
                if ($sale->exists && $sale->sale_status === 'posted') {
                    $this->rollbackPostedSale($sale);
                }

            // 2. Prepare Header Data
            $isNew = ! $sale->exists;
            $sale->customer_id = $request->customer;
            $sale->sales_officer_id = $request->filled('sales_officer_id') ? $request->sales_officer_id : null;
            $sale->po_no = $request->filled('po_no') ? trim($request->po_no) : null;
            $sale->po_date = $request->filled('po_date') ? \Carbon\Carbon::parse($request->po_date)->format('Y-m-d') : null;
            $sale->walkin_name = $isWalkin ? $request->walkin_name : null;
            $sale->reference = $request->reference;
            if ($request->boolean('terms_submitted') || $request->has('selected_terms')) {
                $sale->selected_terms = array_values(array_map('intval', (array) $request->input('selected_terms', [])));
            } elseif (!$sale->exists) {
                $sale->selected_terms = null;
            }
            $sale->total_amount_Words = $request->total_amount_Words; // Consider auto-generating this too?
            $sale->sale_status = $status;
            $sale->source = $request->input('source', $sale->source ?? 'invoice');

            // Credit Days & Due Date (Optional)
            if ($request->filled('credit_days') && $request->credit_days > 0) {
                $creditDays = (int) $request->credit_days;
                $sale->credit_days = $creditDays;

                // Use existing created_at for edits, or now() for new sales
                $baseDate = $sale->created_at ? $sale->created_at->copy() : now();
                $sale->due_date = $baseDate->addDays($creditDays);
            } else {
                // No credit days = no notification
                $sale->credit_days = null;
                $sale->due_date = null;
            }

            if ($isNew) {
                // Check if user provided manual invoice number or selected series
                $invInput = $request->input('Invoice_no') ?: $request->input('invoice_no');
                if ($request->action === 'quotation' || $status === 'quotation') {
                    if (empty($invInput) || \Illuminate\Support\Str::startsWith(strtoupper(trim($invInput)), 'INV-')) {
                        $invInput = \App\Models\InvoiceSeries::generateNextNo('QUOT');
                    }
                }
                if (!empty($invInput)) {
                    $manualInvoice = trim($invInput);

                    // Check for duplicates
                    $exists = Sale::where('invoice_no', $manualInvoice)->exists();
                    if ($exists) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'invoice_no' => "Invoice number '{$manualInvoice}' already exists. Please use a different number or click refresh.",
                        ]);
                    }

                    $sale->invoice_no = $manualInvoice;
                } else {
                    // Auto-generate unique invoice number
                    $sale->invoice_no = ($status === 'quotation') ? \App\Models\InvoiceSeries::generateNextNo('QUOT') : Sale::generateInvoiceNo();
                }
            }

            // We will calculate totals from verified items
            $total_bill = 0;
            $total_items = 0;
            $total_gross = 0;
            $total_exclusive_gst = 0;
            $total_sales_tax = 0;
            $total_further_tax = 0;
            $total_bonus_qty = 0;

            // Determine if this is a booking transaction
            if ($request->action === 'booking' || ($status === 'booked' && $sale->is_booking)) {
                $sale->is_booking = 1;
            } else {
                $sale->is_booking = 0;
            }

            if ($request->filled('sale_date')) {
                $sale->created_at = \Carbon\Carbon::parse($request->sale_date)->format('Y-m-d H:i:s');
            }

            $sale->save(); // Save first to get ID

            // Increment Invoice Series counter if matching series prefix
            \App\Models\InvoiceSeries::incrementCounterForInvoice($sale->invoice_no);

            // 3. Process Items
            // Delete old items if updating
            if (! $isNew) {
                // Restore stock if we were somehow editing a posted sale (should be blocked, but safety first)
                // For now, we blocked editing posted sales, so strictly 'booked'.
                SaleItem::where('sale_id', $sale->id)->delete();
            }

            // Check if customer is registered with STRN (Exempt from Further Tax per Sales Tax Act)
            $customerHasStrn = false;
            if (!$isWalkin && !empty($request->customer)) {
                $relCust = Customer::with('customFieldValues')->find($request->customer);
                if ($relCust) {
                    $cStrn = $relCust->getCustomValue('strn_number') ?? $relCust->strn_number ?? null;
                    $customerHasStrn = !empty(trim((string)$cStrn));
                }
            }

            $productIds = $request->product_id;
            $quantities = $request->qty; // Assumed pieces based on previous context, or Box?
            // User: "qty > 0". Previous code used qty as boxes and total_pieces as real qty.
            // Let's stick to: Frontend sends 'qty' (Boxes) and we calculate total_pieces?
            // Or Frontend sends 'total_pieces'?
            // Looking at invoice blade: $item['qty'] is boxes.
            // Let's assume input 'qty' is BOXES.

            $user = auth()->user();
            $canSelectWarehouse = $user && (
                (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) ||
                (method_exists($user, 'hasRole') && ($user->hasRole('Super Admin') || $user->hasRole('admin') || $user->hasRole('superadmin'))) ||
                $user->can('warehouse.view')
            );
            $userDefaultWhId = (int) ($user?->warehouse_id ?: 1);

            $headerWhId = $canSelectWarehouse
                ? (is_numeric($request->warehouse_id) ? (int)$request->warehouse_id : $userDefaultWhId)
                : $userDefaultWhId;

            $warehouses = ($canSelectWarehouse && is_array($request->warehouse_id))
                ? $request->warehouse_id
                : array_fill(0, count($productIds), $userDefaultWhId);

            $defaultWhId = $headerWhId;
            $discounts = $request->item_disc ?? [];

            $manualItemsForLedger = [];

            foreach ($productIds as $index => $pid) {
                $isManual = (isset($request->is_manual[$index]) && $request->is_manual[$index] == '1');

                if (! $isManual && ! $pid) {
                    continue;
                }

                $qtyInput = (float) ($quantities[$index] ?? 0);
                if ($qtyInput <= 0) {
                    continue;
                }

                if ($isManual) {
                    $inputPrice = (float) ($request->price_per_piece[$index] ?? 0);
                    $dbPrice = $inputPrice;
                    $ppb = 1;
                    $productName = $request->manual_product_name[$index] ?? 'Manual Product';
                    $brandId = null;
                    $unitId = null;
                    $sizeMode = 'pieces';
                    
                    $totalPieces = (float) ($request->total_pieces[$index] ?? 0);
                    if ($totalPieces <= 0) $totalPieces = $qtyInput;
                    $loose = 0;
                    $storedQtyBox = $totalPieces;
                    
                    $manualVendorId = $request->manual_vendor_id[$index] ?? null;
                    $manualPurchasePrice = (float) ($request->manual_purchase_price[$index] ?? 0);
                    $manualPaymentAmount = (float) ($request->manual_vendor_payment_amount[$index] ?? 0);
                    $manualAccountId = $request->manual_vendor_account_id[$index] ?? null;
                } else {
                    $product = Product::findOrFail($pid);

                    // USE INPUT PRICE (User might have changed it manually)
                    $inputPrice = (float) ($request->price_per_piece[$index] ?? 0);
                    $dbPrice = $inputPrice > 0 ? $inputPrice : ($product->sale_price_per_piece > 0 ? $product->sale_price_per_piece : 0);

                    \Log::info("SaleItem #{$index}: Product {$product->item_name}, InputPrice: {$inputPrice}, FinalPrice: {$dbPrice}");

                    $vData = [];
                    if (!empty($request->color[$index])) {
                        $b64 = base64_decode($request->color[$index], true);
                        $vData = ($b64 !== false) ? json_decode($b64, true) : json_decode($request->color[$index], true);
                        if (!is_array($vData)) $vData = [];
                    }

                    $liveVariant = null;
                    if ($product && !empty($product->color)) {
                        $prodVariants = json_decode($product->color, true);
                        if (is_array($prodVariants)) {
                            if (!empty($vData['barcode'])) {
                                $liveVariant = collect($prodVariants)->firstWhere('barcode', $vData['barcode']);
                            }
                            if (!$liveVariant && !empty($vData['name'])) {
                                $liveVariant = collect($prodVariants)->first(function($v) use ($vData) {
                                    $n1 = strtolower(trim($v['name'] ?? ''));
                                    $n2 = strtolower(trim($vData['name'] ?? ''));
                                    return $n1 === $n2 || ($n1 && $n2 && (str_contains($n1, $n2) || str_contains($n2, $n1)));
                                });
                            }
                            if (!$liveVariant && !empty($vData['size']) && $vData['size'] !== '-') {
                                $liveVariant = collect($prodVariants)->first(function($v) use ($vData) {
                                    $s1 = strtolower(trim($v['size'] ?? ''));
                                    $s2 = strtolower(trim($vData['size'] ?? ''));
                                    return $s1 === $s2 || ($s1 && $s2 && (str_starts_with($s1, $s2) || str_starts_with($s2, $s1)));
                                });
                            }
                            if (!$liveVariant && count($prodVariants) === 1) {
                                $liveVariant = $prodVariants[0];
                            }
                        }
                    }

                    $ppb = 1;
                    if ($liveVariant && !empty($liveVariant['conv_factor']) && (float)$liveVariant['conv_factor'] > 0) {
                        $ppb = (float)$liveVariant['conv_factor'];
                    } elseif ($liveVariant && !empty($liveVariant['pieces_per_box']) && (float)$liveVariant['pieces_per_box'] > 0) {
                        $ppb = (float)$liveVariant['pieces_per_box'];
                    } elseif ($product->pieces_per_box > 0) {
                        $ppb = (float)$product->pieces_per_box;
                    } elseif (!empty($vData['conv_factor']) && (float)$vData['conv_factor'] > 0) {
                        $ppb = (float)$vData['conv_factor'];
                    } elseif (!empty($vData['pieces_per_box']) && (float)$vData['pieces_per_box'] > 0) {
                        $ppb = (float)$vData['pieces_per_box'];
                    }

                    if ($ppb <= 0) $ppb = 1;

                    $totalPieces = 0;
                    $loose = (float) ($request->loose_pieces[$index] ?? 0); // Legacy/Fallback

                    // Quantity Logic based on Size Mode
                    if (in_array($product->size_mode, ['by_cartons', 'by_bandal']) || $product->size_mode === 'by_size') {
                        // For Carton/Size modes, Frontend sends 'total_pieces' (Calculated Pieces)
                        // and 'qty' contains "Box.Loose" string.
                        $reqTotal = (float) ($request->total_pieces[$index] ?? 0);

                        if ($reqTotal > 0) {
                            $totalPieces = $reqTotal;
                        } else {
                            // Fallback: Parse "Box.Loose" manually if Frontend failed/missing
                            $qStr = (string) ($quantities[$index] ?? '');
                            $parts = explode('.', $qStr);
                            $boxes = (int) ($parts[0] ?? 0);
                            $l = isset($parts[1]) ? (int) $parts[1] : 0;
                            $totalPieces = ($boxes * $ppb) + $l;
                        }
                    } else {
                        // Start of By Piece
                        $reqTotal = (float) ($request->total_pieces[$index] ?? 0);
                        if ($reqTotal > 0) {
                            $totalPieces = $reqTotal;
                        } else {
                            $qStr = (string) ($quantities[$index] ?? '');
                            $parts = explode('.', $qStr);
                            $boxes = (int) ($parts[0] ?? 0);
                            $l = isset($parts[1]) ? (int) $parts[1] : 0;
                            // For by_pieces, both fields act as plain piece counts in frontend
                            $totalPieces = $boxes + $l;
                        }
                    }

                    // Calculate boxes for storage (reverse calculation)
                    $storedQtyBox = $ppb > 0 ? ($totalPieces / $ppb) : $totalPieces;
                    
                    $productName = $product->item_name;
                    $brandId = $product->brand_id;
                    $unitId = $product->unit_id;
                    $sizeMode = $product->size_mode;
                }

                $discount = (float) ($discounts[$index] ?? 0);
                // 'pkr' means fixed PKR amount;  anything else ('percent' or missing) = percentage
                $discType = $request->discount_type[$index] ?? 'percent';

                // Calculate Line Gross (before discount)
                $frontendGross = (float) ($request->gross_amount[$index] ?? 0);
                $frontendNet   = (float) ($request->total[$index] ?? 0);

                if ($frontendGross > 0) {
                    $lineGross = $frontendGross;
                } elseif ($frontendNet > 0) {
                    // frontendNet was already after discount in frontend
                    if ($discType === 'pkr') {
                        $lineGross = $frontendNet + $discount;
                    } else {
                        $lineGross = $discount < 100 ? round($frontendNet / (1 - ($discount / 100)), 2) : $frontendNet;
                    }
                } else {
                    if (in_array($sizeMode, ['by_cartons', 'by_bandal'])) {
                        $subUnit = $request->sub_unit_mode[$index] ?? 'main';
                        if ($subUnit === 'pcs') {
                            $lineGross = $totalPieces * $dbPrice;
                        } else {
                            $lineGross = $storedQtyBox * $dbPrice;
                        }
                    } else {
                        $lineGross = $totalPieces * $dbPrice;
                    }
                }

                // Apply Discount correctly
                if ($discType === 'pkr') {
                    // User entered a fixed PKR amount
                    $calcDiscountAmount  = min($lineGross, $discount);
                    // Back-calculate the equivalent percent for storage/reporting (avoid division by zero)
                    $calcDiscountPercent = $lineGross > 0 ? round(($calcDiscountAmount / $lineGross) * 100, 4) : 0;
                } else {
                    // User entered a percentage
                    $calcDiscountPercent = $discount;
                    $calcDiscountAmount  = round(($lineGross * $discount) / 100, 2);
                }

                $calcExclusiveGst = max(0, round($lineGross - $calcDiscountAmount, 2));
                // Dynamic Tax Controls
                $isStEnabled = \App\Models\SaleFieldConfiguration::isEnabled('sales_tax', true);
                $isFtEnabled = \App\Models\SaleFieldConfiguration::isEnabled('further_tax', true);
                $strnExemptEnabled = \App\Models\SaleFieldConfiguration::isEnabled('strn_exemption', true);

                $salesTaxPct = $isStEnabled ? (float) ($request->sales_tax_percent[$index] ?? 0) : 0;
                $salesTaxAmt = round($calcExclusiveGst * ($salesTaxPct / 100), 2);

                if ($isFtEnabled) {
                    if ($customerHasStrn && $strnExemptEnabled) {
                        $furtherTaxPct = 0;
                    } else {
                        $furtherTaxPct = (float) ($request->further_tax_percent[$index] ?? 0);
                    }
                    $furtherTaxAmt = round($calcExclusiveGst * ($furtherTaxPct / 100), 2);
                } else {
                    $furtherTaxPct = 0;
                    $furtherTaxAmt = 0;
                }
                $lineTotal = round($calcExclusiveGst + $salesTaxAmt + $furtherTaxAmt, 2);
                $bonusQty = (float) ($request->bonus_qty[$index] ?? 0);
                $mfgDate = $request->mfg_date[$index] ?? null;
                $expDate = $request->exp_date[$index] ?? null;

                $colorVal = $request->color[$index] ?? null;
                $sizeVal = $request->size_display[$index] ?? ($request->size[$index] ?? null);

                if ($sizeVal !== null && $sizeVal !== '' && $sizeVal !== '-') {
                    $vData = [];
                    if (!empty($colorVal)) {
                        $b64 = base64_decode($colorVal, true);
                        $vData = ($b64 !== false) ? json_decode($b64, true) : json_decode($colorVal, true);
                    }
                    if (!is_array($vData)) {
                        $vData = [];
                    }
                    $vData['size'] = $sizeVal;
                    $colorVal = base64_encode(json_encode($vData));
                }

                $saleItem = new SaleItem;
                $saleItem->sale_id = $sale->id;
                $saleItem->product_id = $isManual ? null : $pid;
                $saleItem->batch_id = !empty($request->batch_id[$index]) ? $request->batch_id[$index] : null;
                $saleItem->batch_no = !empty($request->batch_no[$index]) ? $request->batch_no[$index] : null;

                $rawSerials = $request->serials[$index] ?? null;
                if (!empty($rawSerials)) {
                    if (is_array($rawSerials)) {
                        $saleItem->serials = json_encode(array_values($rawSerials));
                    } elseif (is_string($rawSerials)) {
                        $decoded = json_decode($rawSerials, true);
                        if (is_array($decoded)) {
                            $saleItem->serials = json_encode(array_values($decoded));
                        } else {
                            $cleanArr = array_values(array_filter(array_map('trim', explode(',', $rawSerials))));
                            $saleItem->serials = count($cleanArr) > 0 ? json_encode($cleanArr) : null;
                        }
                    }
                } else {
                    $saleItem->serials = null;
                }

                $saleItem->color = $colorVal;
                $saleItem->warehouse_id = !empty($warehouses[$index]) ? (int)$warehouses[$index] : $headerWhId;
                $saleItem->product_name = $productName; // Store name snapshot

                $saleItem->qty = $storedQtyBox; // Store as Box equivalent for consistency
                $saleItem->total_pieces = $totalPieces;
                $saleItem->loose_pieces = $loose;
                $saleItem->bonus_qty = $bonusQty;
                $saleItem->mfg_date = $mfgDate;
                $saleItem->exp_date = $expDate;

                $saleItem->price = $dbPrice;
                $saleItem->gross_amount = $lineGross;
                $saleItem->discount_percent = $calcDiscountPercent;
                $saleItem->discount_amount = $calcDiscountAmount;
                $saleItem->exclusive_gst_amount = $calcExclusiveGst;
                $saleItem->sales_tax_percent = $salesTaxPct;
                $saleItem->sales_tax_amount = $salesTaxAmt;
                $saleItem->further_tax_percent = $furtherTaxPct;
                $saleItem->further_tax_amount = $furtherTaxAmt;
                $saleItem->total = $lineTotal;

                // Meta
                $saleItem->brand_id = $brandId;
                $saleItem->unit_id = $unitId;
                $saleItem->size_mode = $sizeMode;

                if ($isManual) {
                    $saleItem->is_manual = 1;
                    $saleItem->vendor_id = $manualVendorId;
                    $saleItem->purchase_price = $manualPurchasePrice;

                    $manualItemsForLedger[] = [
                        'vendor_id' => $manualVendorId,
                        'product_name' => $productName,
                        'qty' => $totalPieces,
                        'purchase_price' => $manualPurchasePrice,
                        'payment_amount' => $manualPaymentAmount,
                        'account_id' => $manualAccountId,
                    ];
                }

                $saleItem->save();

                $total_bill += $lineTotal;
                $total_items += $totalPieces;
                $total_gross += $lineGross;
                $total_exclusive_gst += $calcExclusiveGst;
                $total_sales_tax += $salesTaxAmt;
                $total_further_tax += $furtherTaxAmt;
                $total_bonus_qty += $bonusQty;
            }

            // Update Sale Totals
            $sale->total_bill_amount = $total_bill;
            $sale->total_gross = $total_gross;
            $sale->total_exclusive_gst = $total_exclusive_gst;
            $sale->total_sales_tax = $total_sales_tax;
            $sale->total_further_tax = $total_further_tax;
            $sale->total_bonus_qty = $total_bonus_qty;
            $sale->total_extradiscount = $request->total_extra_cost ?? 0;
            $sale->freight_charges = $request->freight_charges ?? 0;
            $sale->freight_type = $request->freight_type ?? 'add';
            
            $net = $total_bill - $sale->total_extradiscount;
            if ($sale->freight_type === 'add') {
                $net += $sale->freight_charges;
            } elseif ($sale->freight_type === 'deduct') {
                $net -= $sale->freight_charges;
            }
            $sale->total_net = max(0, $net);
            $sale->total_items = $total_items;

            $sale->cash = $request->cash ?? 0;
            $sale->change = ($sale->cash - $sale->total_net);
            $sale->change_account_id = $request->input('change_account_id') ?: null;
            $sale->paid_amount = round(min((float) $sale->total_net, max(0, (float) ($sale->cash ?? 0) + (float) ($sale->card ?? 0))), 2);
            $sale->due_amount = max(0, round((float) $sale->total_net - $sale->paid_amount, 2));

            if ($isWalkin && $sale->change < -0.05) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'cash' => 'Walk-in customers must pay 100% upfront. Balance cannot be unpaid.'
                ]);
            }

            $sale->save();

            // Process returned/exchange items (if any)
            $returnProductIds = $request->input('return_product_id');
            $totalReturnAmountForNet = 0;
            if (is_array($returnProductIds) && count($returnProductIds) > 0) {
                $originalSaleIds = array_filter(array_unique($request->input('original_sale_id') ?? []));
                
                foreach ($originalSaleIds as $origSaleId) {
                    $origSale = Sale::with('items')->find($origSaleId);
                    if (!$origSale) continue;

                    $lastReturnId = \App\Models\SaleReturn::max('id') ?? 0;
                    do {
                        $lastReturnId++;
                        $nextInvoice = 'SR-' . str_pad($lastReturnId, 4, '0', STR_PAD_LEFT);
                    } while (\App\Models\SaleReturn::where('return_invoice', $nextInvoice)->exists());

                    $returnHeader = \App\Models\SaleReturn::create([
                        'sale_id' => $origSaleId,
                        'return_invoice' => $nextInvoice,
                        'customer_id' => $sale->customer_id ?: null,
                        'warehouse_id' => 1,
                        'return_date' => now()->format('Y-m-d'),
                        'remarks' => 'POS Exchange Return (Sales Invoice #' . $sale->invoice_no . ')',
                        'status' => 'posted',
                    ]);

                    $movements = [];
                    $saleReturnTotalAmount = 0;

                    $manualVendorReturnsForLedger = [];

                    foreach ($returnProductIds as $idx => $rPid) {
                        if ($request->original_sale_id[$idx] == $origSaleId) {
                            $rQty = (float)$request->return_qty[$idx];
                            if ($rQty <= 0) continue;

                            $rPrice = (float)$request->return_price[$idx];
                            $rColor = $request->return_color[$idx];
                            
                            $rIsManual = isset($request->return_is_manual[$idx]) && $request->return_is_manual[$idx] == '1';
                            $rSaleItemId = $request->return_sale_item_id[$idx] ?? null;

                            if ($rIsManual) {
                                $origSaleItem = $origSale->items->where('id', $rSaleItemId)->first();
                            } else {
                                $origSaleItem = $origSale->items->where('product_id', $rPid)->first();
                            }

                            if (!$origSaleItem || $rQty > $origSaleItem->total_pieces) {
                                $productName = $origSaleItem->product_name ?? "Product #" . ($rIsManual ? $rSaleItemId : $rPid);
                                $maxReturnable = $origSaleItem ? $origSaleItem->total_pieces : 0;
                                throw \Illuminate\Validation\ValidationException::withMessages([
                                    'return_qty' => "Cannot return {$rQty} pieces of '{$productName}'. Maximum returnable: {$maxReturnable} pieces.",
                                ]);
                            }

                            if ($rIsManual) {
                                $ppb = 1;
                            } else {
                                $rProduct = \App\Models\Product::find($rPid);
                                $ppb = $rProduct && $rProduct->pieces_per_box > 0 ? $rProduct->pieces_per_box : 1;
                            }
                            
                            $lineTotal = $rQty * $rPrice;
                            $saleReturnTotalAmount += $lineTotal;
                            $totalReturnAmountForNet += $lineTotal;

                            $retWhId = (int) ($origSaleItem->warehouse_id ?: $headerWhId);
                            \App\Models\SaleReturnItem::create([
                                'sale_return_id' => $returnHeader->id,
                                'product_id' => $rIsManual ? null : $rPid,
                                'is_manual' => $rIsManual ? 1 : 0,
                                'product_name' => $rIsManual ? $origSaleItem->product_name : null,
                                'vendor_id' => $rIsManual ? $origSaleItem->vendor_id : null,
                                'purchase_price' => $rIsManual ? $origSaleItem->purchase_price : null,
                                'color' => $rColor,
                                'warehouse_id' => $retWhId,
                                'qty' => $rQty,
                                'boxes' => $rQty / $ppb,
                                'loose_pieces' => $rQty % $ppb,
                                'price' => $rPrice,
                                'item_discount' => 0,
                                'unit' => 'pc',
                                'line_total' => $lineTotal,
                            ]);

                            if ($rIsManual) {
                                $manualVendorReturnsForLedger[] = [
                                    'vendor_id' => $origSaleItem->vendor_id,
                                    'product_name' => $origSaleItem->product_name,
                                    'qty' => $rQty,
                            // Normalize qty for stock based on variant conv_factor
                                    'purchase_price' => $origSaleItem->purchase_price,
                                ];
                            } else {
                                // Normalize qty for stock based on variant conv_factor
                                $stockQty = $rQty;
                                if (!empty($rColor)) {
                                    try {
                                        $b64Decoded = base64_decode($rColor, true);
                                        $variantData = $b64Decoded !== false ? json_decode($b64Decoded, true) : null;
                                        if (!is_array($variantData)) {
                                            $variantData = is_string($rColor) ? json_decode($rColor, true) : $rColor;
                                        }
                                        if (is_array($variantData) && isset($variantData['conv_factor'])) {
                                            $factor = (float)$variantData['conv_factor'];
                                            if ($factor > 0) {
                                                $stockQty = $stockQty * $factor;
                                            }
                                        }
                                    } catch (\Exception $e) {}
                                }
                                // Restore stock
                                $stock = \App\Models\WarehouseStock::where('warehouse_id', $retWhId)
                                    ->where('product_id', $rPid)
                                    ->lockForUpdate()
                                    ->first();

                                if ($stock) {
                                    $newTotalPieces = $stock->total_pieces + $stockQty;
                                    $stock->total_pieces = $newTotalPieces;
                                    $stock->quantity = $newTotalPieces / $ppb;
                                    $stock->save();
                                } else {
                                    \App\Models\WarehouseStock::create([
                                        'warehouse_id' => $retWhId,
                                        'product_id' => $rPid,
                                        'total_pieces' => $stockQty,
                                        'quantity' => $stockQty / $ppb,
                                        'price' => 0
                                    ]);
                                }

                                // Keep ProductBatch synced on POS exchange return
                                $rProd = Product::find($rPid);
                                if ($rProd) {
                                    app(\App\Services\StockBalanceService::class)->syncBatchStockOnDelta(
                                        $rProd,
                                        (int) $retWhId,
                                        (float) $stockQty,
                                        null,
                                        $origSaleItem->batch_id ?? null,
                                        $origSaleItem->batch_no ?? null,
                                        "Exchange Return #{$nextInvoice}"
                                    );
                                }

                                // Movement
                                $movements[] = [
                                    'product_id' => $rPid,
                                    'type' => 'in',
                                    'qty' => $stockQty,
                                    'ref_type' => 'SALE_RETURN',
                                    'ref_id' => $returnHeader->id,
                                    'note' => "Exchange Return #{$nextInvoice} on Sale #{$sale->invoice_no}",
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }
                            
                            // Note: We DO NOT decrement from the original Sale Item.
                            // A Sale Return is a separate transaction and does not alter the historical Sale Invoice.
                        }
                    }

                    if (!empty($movements)) {
                        DB::table('stock_movements')->insert($movements);
                    }

                    // Recalculate true change based on net payable
                    $netAfterExchange = max(0, (float) $sale->total_net - (float) $totalReturnAmountForNet);
                    $sale->change = $sale->cash - $netAfterExchange;
                    $sale->paid_amount = round(min($netAfterExchange, max(0, (float) ($sale->cash ?? 0) + (float) ($sale->card ?? 0))), 2);
                    $sale->due_amount = max(0, round($netAfterExchange - $sale->paid_amount, 2));
                    $sale->reference = "Exchange for " . $origSale->invoice_no;
                    $sale->save();

                    // Update Original Sale Header Totals
                    $allSaleItems = \App\Models\SaleItem::where('sale_id', $origSale->id)->get();
                    $origSale->total_bill_amount = $allSaleItems->sum('total');
                    $origSale->total_net = max(0, $origSale->total_bill_amount - ($origSale->total_extradiscount ?? 0));
                    $origSale->total_items = $allSaleItems->sum('total_pieces');
                    $origSale->save();

                    // Exchange Mode: We DO NOT record the full Sales Return journal entry here.
                    // The difference will be settled later during Sale posting.


                    // --- MANUAL PRODUCT VENDOR RETURN LEDGER ---
                    if (isset($manualVendorReturnsForLedger) && count($manualVendorReturnsForLedger) > 0) {
                        try {
                            $journalService = app(\App\Services\JournalEntryService::class);
                            $balanceService = app(\App\Services\BalanceService::class);
                            $date = now()->format('Y-m-d');
                            $purchasesAccount = \App\Models\Account::where('title', 'like', '%Purchase%')->first();
                            $purchasesAccountId = $purchasesAccount ? $purchasesAccount->id : $balanceService->getSalesRevenueId(); 

                            foreach ($manualVendorReturnsForLedger as $mRetItem) {
                                $vendor = \App\Models\Vendor::find($mRetItem['vendor_id']);
                                if (!$vendor) continue;

                                $cogsReturnAmount = $mRetItem['qty'] * $mRetItem['purchase_price'];

                                // Debit Vendor, Credit Purchases/COGS
                                $journalService->recordEntry(
                                    clone $returnHeader, 
                                    $balanceService->getAccountsPayableId(),
                                    $cogsReturnAmount,
                                    0,
                                    "Manual Purchase Return for POS Sale Exchange #{$sale->invoice_no} ({$mRetItem['product_name']})",
                                    $date,
                                    $vendor
                                );

                                $journalService->recordEntry(
                                    clone $returnHeader,
                                    $purchasesAccountId, 
                                    0,
                                    $cogsReturnAmount,
                                    "Manual Purchase Return for POS Sale Exchange #{$sale->invoice_no} ({$mRetItem['product_name']})",
                                    $date
                                );
                            }
                        } catch (\Exception $e) {
                            \Log::error('Exchange Return Vendor Ledger Error: '.$e->getMessage());
                        }
                    }
                }
            }

            // 4. Handle Status Logic
            if ($status === 'posted') {
                \Log::info('Proceeding to Auto-Receipt & Ledger logic for Sale #'.$sale->invoice_no);

                // 1. DEDUCT STOCK FROM WAREHOUSE
                $this->handleStockImpact($sale, 'out');

                $totalReturnVal = $totalReturnAmountForNet ?? 0;
                $isExchange = $totalReturnVal > 0;
                $trueNetPayable = $sale->total_net - $totalReturnVal;

                // 2. LEGACY LEDGER: Post Invoice First (Increases Balance)
                if ($isExchange) {
                    if ($trueNetPayable !== 0.0) {
                        $this->updateLedger($sale, $trueNetPayable);
                    }
                } else {
                    $this->updateLedger($sale);
                }

                try {
                    $journalService = app(\App\Services\JournalEntryService::class);
                    $balanceService = app(\App\Services\BalanceService::class);
                    $arAccountId = $balanceService->getAccountsReceivableId();
                    $salesAccountId = $balanceService->getSalesRevenueId();
                    $date = $sale->created_at->format('Y-m-d');
                    $custForVoucher = $sale->customer_relation ?? \App\Models\Customer::find($sale->customer_id);

                    // --- PROFESSIONAL LEDGER POSTING (ENTRY 1: THE INVOICE) ---
                    if ($isExchange) {
                        if ($trueNetPayable > 0 && $custForVoucher) {
                            $balanceService->createSaleVoucher(
                                $custForVoucher,
                                $trueNetPayable,
                                $sale->invoice_no,
                                $date
                            );
                        } elseif ($trueNetPayable < 0 && $custForVoucher) {
                            $refundAmt = abs($trueNetPayable);
                            
                            // Debit Sales Return (Reduce Revenue)
                            $journalService->recordEntry(
                                clone $sale,
                                $salesAccountId,
                                $refundAmt,
                                0,
                                "Refund for POS Exchange #{$sale->invoice_no}",
                                $date
                            );

                            // Credit Customer (AR)
                            $journalService->recordEntry(
                                clone $sale,
                                $arAccountId,
                                0,
                                $refundAmt,
                                "Refund for POS Exchange #{$sale->invoice_no}",
                                $date,
                                $custForVoucher
                            );
                        }
                    } else {
                        if ($custForVoucher) {
                            $balanceService->createSaleVoucher(
                                $custForVoucher,
                                $sale->total_net,
                                $sale->invoice_no,
                                $date
                            );
                        }
                    }

                    // --- AUTO RECEIPT (ENTRY 2: THE PAYMENT) ---
                    $transactionService = app(\App\Services\TransactionService::class);
                    $transactionService->createReceiptFromSale(
                        $sale,
                        $request->input('receipt_account_id', []),
                        $request->input('receipt_amount', []),
                        $sale->change_account_id
                    );

                    // --- CHEQUE PAYMENT RECORDING ---
                    $chequeAcc = \App\Models\Account::where('title', 'Cheques in Hand')->first();
                    $receiptAccIds = $request->input('receipt_account_id', []);
                    $receiptAmounts = $request->input('receipt_amount', []);
                    $paidByCheque = 0;

                    if ($chequeAcc) {
                        foreach ($receiptAccIds as $idx => $accId) {
                            if ((int)$accId === (int)$chequeAcc->id) {
                                $paidByCheque += (float) ($receiptAmounts[$idx] ?? 0);
                            }
                        }
                    }

                    if ($paidByCheque > 0 && $request->filled('sale_cheque_no')) {
                        \App\Models\Cheque::updateOrCreate(
                            [
                                'cheque_no' => $request->input('sale_cheque_no'),
                                'customer_id' => $sale->customer_id,
                            ],
                            [
                                'bank_name' => $request->input('sale_cheque_bank', 'Cheque'),
                                'cheque_date' => $request->input('sale_cheque_date', date('Y-m-d')),
                                'amount' => $paidByCheque,
                                'status' => 'in_hand',
                                'note' => "Payment for Sale Invoice #{$sale->invoice_no}",
                                'created_by' => auth()->id(),
                            ]
                        );
                    }

                    // --- AUTO REFUND (ENTRY 3: IF NET PAYABLE IS NEGATIVE) ---
                    if ($isExchange && $trueNetPayable < 0 && $custForVoucher) {
                        $refundAmt = abs($trueNetPayable);
                        
                        $cashAccountId = $balanceService->getCashAccountId();
                        
                        // Debit AR
                        $journalService->recordEntry(
                            clone $sale,
                            $arAccountId,
                            $refundAmt,
                            0,
                            "Refund Paid for POS Exchange #{$sale->invoice_no}",
                            $date,
                            $custForVoucher
                        );
                        
                        // Credit Cash
                        $journalService->recordEntry(
                            clone $sale,
                            $cashAccountId,
                            0,
                            $refundAmt,
                            "Refund Paid for POS Exchange #{$sale->invoice_no}",
                            $date
                        );

                        // Log refund voucher
                        \App\Models\CustomerPayment::create([
                            'customer_id' => $custForVoucher->id,
                            'admin_or_user_id' => auth()->id(),
                            'payment_date' => $date,
                            'payment_method' => 'Cash',
                            'amount' => $refundAmt,
                            'note' => "Refund Paid for POS Exchange #{$sale->invoice_no}"
                        ]);
                    }

                    // --- MANUAL PRODUCT VENDOR LEDGER ---
                    if (isset($manualItemsForLedger) && count($manualItemsForLedger) > 0) {
                        $purchasesAccount = \App\Models\Account::where('title', 'like', '%Purchase%')->first();
                        $purchasesAccountId = $purchasesAccount ? $purchasesAccount->id : $balanceService->getSalesRevenueId(); 

                        foreach ($manualItemsForLedger as $mItem) {
                            $vendor = \App\Models\Vendor::find($mItem['vendor_id']);
                            if (!$vendor) continue;

                            $cogsAmount = $mItem['qty'] * $mItem['purchase_price'];

                            // 1. Purchase (Vendor Payable)
                            $journalService->recordEntry(
                                clone $sale, // Clone to avoid overriding attributes
                                $purchasesAccountId,
                                $cogsAmount,
                                0,
                                "Manual Purchase for POS Sale #{$sale->invoice_no} ({$mItem['product_name']})",
                                $date
                            );

                            $journalService->recordEntry(
                                clone $sale,
                                $balanceService->getAccountsPayableId(), 
                                0,
                                $cogsAmount,
                                "Manual Purchase for POS Sale #{$sale->invoice_no} ({$mItem['product_name']})",
                                $date,
                                $vendor
                            );

                            // 2. Vendor Payment (if paid)
                            if ($mItem['payment_amount'] > 0 && $mItem['account_id']) {
                                $journalService->recordEntry(
                                    clone $sale,
                                    $balanceService->getAccountsPayableId(),
                                    $mItem['payment_amount'],
                                    0,
                                    "Payment for Manual Purchase - POS Sale #{$sale->invoice_no}",
                                    $date,
                                    $vendor
                                );

                                $journalService->recordEntry(
                                    clone $sale,
                                    $mItem['account_id'],
                                    0,
                                    $mItem['payment_amount'],
                                    "Payment for Manual Purchase - POS Sale #{$sale->invoice_no}",
                                    $date
                                );
                            }
                        }
                    }

                } catch (\Exception $e) {
                    \Log::error('Professional Ledger Posting Error: '.$e->getMessage());
                }
            }

            // If AJAX/JSON response needed
            if ($request->ajax() || $request->wantsJson()) {
                $receiptUrl = route('sales.receipt', $sale->id) . '?from=pos';
                return response()->json([
                    'ok' => true,
                    'booking_id' => $sale->id,
                    'msg' => 'Sale '.ucfirst($status).' Successfully',
                    'invoice_url' => $receiptUrl,
                    'receipt_url' => $receiptUrl,
                ]);
            }

            return redirect()->route('sale.index')->with('success', 'Sale saved as '.$status);
        });
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage();
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $errorMsg = 'Validation Error: ' . implode(', ', collect($e->errors())->flatten()->toArray());
            } else {
                \Log::error('Sale Save Error: ' . $errorMsg . "\n" . $e->getTraceAsString());
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'ok' => false,
                    'message' => $errorMsg
                ], 422);
            }
            return redirect()->back()->withInput()->with('error', $errorMsg);
        }
    }

    public function handleStockImpact(Sale $sale, $type = 'out')
    {
        // Type: 'out' (Sale Posted), 'in' (Sale Cancelled), 'return' (Returned)

        // Load items relationship if not already loaded
        if (! $sale->relationLoaded('items')) {
            $sale->load('items.product');
        }

        foreach ($sale->items as $item) {
            // Outsourced / Manual products do not affect warehouse stock
            if ($item->is_manual) {
                continue;
            }

            $targetWhId = $item->warehouse_id ?: (auth()->user()->warehouse_id ?? 1);
            $stock = WarehouseStock::where('product_id', $item->product_id)
                ->where('warehouse_id', $targetWhId)
                ->lockForUpdate() // LOCK ROW
                ->first();

            if (! $stock) {
                $stock = WarehouseStock::where('product_id', $item->product_id)->lockForUpdate()->first();
            }

            if (! $stock) {
                // Create if missing? Or fail? User said "Validate warehouse stock".
                throw new \Exception('Stock not found for product: '.$item->product_name);
            }

            // Calculate stock deduction quantity (for Kg products, total_pieces in WarehouseStock = total Kg)
            $productMode = $item->product->size_mode ?? '';
            $qtyPieces = (float)$item->total_pieces;

            $factor = 1.0;
            $unit = '';

            if (!empty($item->color)) {
                try {
                    $itemColor = $item->color;
                    $b64Decoded = base64_decode($itemColor, true);
                    $variantData = $b64Decoded !== false ? json_decode($b64Decoded, true) : null;
                    if (!is_array($variantData)) {
                        $variantData = is_string($itemColor) ? json_decode($itemColor, true) : $itemColor;
                    }
                    if (is_array($variantData)) {
                        if (isset($variantData['conv_factor']) && (float)$variantData['conv_factor'] > 0) {
                            $factor = (float)$variantData['conv_factor'];
                        } elseif (isset($variantData['weight_per_piece']) && (float)$variantData['weight_per_piece'] > 0) {
                            $factor = (float)$variantData['weight_per_piece'] / 1000.0;
                        }
                        if (isset($variantData['unit'])) {
                            $unit = strtolower(trim($variantData['unit']));
                        }
                    }
                } catch (\Exception $e) {}
            }

            if ($productMode === 'by_kg' || $productMode === 'by_gm') {

                if ($unit === 'gm' || $unit === 'g') {
                    $qtyPieces = ((float)$item->qty) / 1000.0;
                } else if ($unit === 'pcs' || $unit === 'piece' || $unit === 'pieces' || ($factor > 0 && $factor != 1.0)) {
                    $qtyPieces = ((float)$item->qty) * $factor;
                } else {
                    $qtyPieces = (float)$item->qty > 0 ? (float)$item->qty : (float)$item->total_pieces;
                }
            } else {
                // Ensure we respect the unit toggle for by_pieces and by_cartons products as well
                if ($unit === 'pcs' || $unit === 'piece' || $unit === 'pieces') {
                    $qtyPieces = (float)$item->qty;
                } elseif ($unit === 'carton' || $unit === 'ctn' || $unit === 'cbn' || $unit === 'box') {
                    $ppb = (float)($item->product->pieces_per_box ?? 1);
                    if (isset($factor) && $factor > 0 && $factor != 1.0) {
                        $ppb = $factor;
                    }
                    if ($ppb <= 0) $ppb = 1;
                    $qtyPieces = ((float)$item->qty) * $ppb;
                } else {
                    $qtyPieces = (float)$item->total_pieces > 0 ? (float)$item->total_pieces : (float)$item->qty;
                }
            }

            if ($type === 'out') {
                // Deduct
                $available = $this->calculateAvailableForItem($item, $stock);
                if ($available < $qtyPieces) {
                    $availFormatted = number_format($available, 3);
                    $unitLabel = ($productMode === 'by_kg' || $productMode === 'by_gm') ? 'Kg' : 'Pcs';
                    throw new \Exception('Insufficient stock for '.$item->product_name.'. Available: '.$availFormatted.' '.$unitLabel);
                }
                $stock->total_pieces -= $qtyPieces;
                // Update approx boxes for display
                $ppb = $item->product->pieces_per_box ?? 1;
                $stock->quantity = round($stock->total_pieces / ($ppb > 0 ? $ppb : 1), 2);
                $stock->save();

                // Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $item->product_id,
                    'type' => 'out',
                    'qty' => -$qtyPieces, // Negative for OUT
                    'ref_type' => 'sale',
                    'ref_id' => $sale->id,
                    'note' => 'Sale Posted #'.$sale->invoice_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Deduct Batch quantity if specified
                if (!empty($item->batch_id)) {
                    \App\Models\ProductBatch::where('id', $item->batch_id)->decrement('qty', $qtyPieces);
                } elseif (!empty($item->batch_no)) {
                    \App\Models\ProductBatch::where('product_id', $item->product_id)
                        ->where('warehouse_id', $targetWhId)
                        ->where('batch_no', $item->batch_no)
                        ->decrement('qty', $qtyPieces);
                }

                // Update Serials status to 'sold' if specified
                if (!empty($item->serials)) {
                    $serialList = is_array($item->serials) ? $item->serials : json_decode($item->serials, true);
                    if (is_array($serialList) && count($serialList) > 0) {
                        \App\Models\ProductSerial::where('product_id', $item->product_id)
                            ->whereIn('serial_number', $serialList)
                            ->update(['status' => 'sold']);
                    }
                }
            } elseif ($type === 'in' || $type === 'return') {
                // Restore (Cancel or Return)
                $stock->total_pieces += $qtyPieces;
                // Update approx boxes
                $ppb = $item->product->pieces_per_box ?? 1;
                $stock->quantity = round($stock->total_pieces / ($ppb > 0 ? $ppb : 1), 2);
                $stock->save();

                // Movement
                DB::table('stock_movements')->insert([
                    'product_id' => $item->product_id,
                    'type' => 'in',
                    'qty' => $qtyPieces,
                    'ref_type' => 'sale_'.$type, // sale_in (cancel), sale_return
                    'ref_id' => $sale->id,
                    'note' => 'Sale '.ucfirst($type).' #'.$sale->invoice_no,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Restore Batch quantity if specified
                if (!empty($item->batch_id)) {
                    \App\Models\ProductBatch::where('id', $item->batch_id)->increment('qty', $qtyPieces);
                } elseif (!empty($item->batch_no)) {
                    \App\Models\ProductBatch::where('product_id', $item->product_id)
                        ->where('batch_no', $item->batch_no)
                        ->increment('qty', $qtyPieces);
                }

                // Restore Serials status to 'available' if specified
                if (!empty($item->serials)) {
                    $serialList = is_array($item->serials) ? $item->serials : json_decode($item->serials, true);
                    if (is_array($serialList) && count($serialList) > 0) {
                        \App\Models\ProductSerial::where('product_id', $item->product_id)
                            ->whereIn('serial_number', $serialList)
                            ->update(['status' => 'available']);
                    }
                }
            }
        }
    }

    /**
     * Compute available stock for a sale item. For variant products, uses the same
     * per-variant ledger that the item stock report / sale create page display.
     * Falls back to physical warehouse stock for non-variant products.
     */
    private function calculateAvailableForItem($item, $stock)
    {
        if (!$item->product) {
            return (float) $stock->total_pieces;
        }

        return app(\App\Services\StockBalanceService::class)
            ->getAvailableForSaleItem($item->product, $stock->warehouse_id, $item->color);
    }

    /**
     * Match a sale/purchase/return item to a product variant (barcode-first).
     */
    private function matchStockVariant($item, $variant)
    {
        $itemColor = $item->color ?? '';
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

        $vBarcode = trim($variant['barcode'] ?? '');
        $itemBarcode = trim($itemVariant['barcode'] ?? '');
        if (!empty($vBarcode) && !empty($itemBarcode)) {
            return $vBarcode === $itemBarcode;
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

    public function updateLedger(Sale $sale, $customAmount = null)
    {
        $customer_id = $sale->customer_id;
        if (! $customer_id) {
            return;
        }

        $ledger = CustomerLedger::where('customer_id', $customer_id)->latest('id')->first();
        // Fallback: If no ledger, check Customer Master
        if (! $ledger) {
            $cust = \App\Models\Customer::find($customer_id);
            $prev_bal = $cust->previous_balance ?? 0;
        } else {
            $prev_bal = $ledger->closing_balance;
        }

        $amount = $customAmount !== null ? $customAmount : $sale->total_net;
        $new_bal = $prev_bal + $amount;

        \Log::info("Legacy Ledger (Invoice): Customer #{$customer_id}. Prev: {$prev_bal} + Sale: {$sale->total_net} = New: {$new_bal}");

        CustomerLedger::create([
            'customer_id' => $customer_id,
            'admin_or_user_id' => auth()->id() ?? 1,
            'description' => 'Sale Invoice #'.$sale->invoice_no,
            'previous_balance' => $prev_bal,
            'closing_balance' => $new_bal,
            'opening_balance' => 0, // Schema might require this
        ]);

        // Update Customer Master
        $cust = \App\Models\Customer::find($customer_id);
        if ($cust) {
            $cust->previous_balance = $new_bal;
            $cust->save();
        }
    }

    /**
     * Auto-generate receipt voucher when sale is posted
     */
    private function autoGenerateReceiptVoucher(Sale $sale, Request $request)
    {
        // Get account IDs from request (from receipt voucher section)
        $accountIds = $request->input('receipt_account_id', []);
        $amounts = $request->input('receipt_amount', []);

        // If no accounts selected, use default cash account
        if (empty($accountIds) || empty(array_filter($accountIds))) {
            $accountIds = [1]; // Default to account ID 1 (Cash)
            $amounts = [$sale->cash];
        }

        // Generate unique RVID
        $lastRV = \App\Models\ReceiptsVoucher::orderBy('id', 'desc')->first();
        $nextNumber = $lastRV ? (int) preg_replace('/[^0-9]/', '', $lastRV->rvid) + 1 : 1;
        $rvid = 'RV-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Create receipt voucher
        \App\Models\ReceiptsVoucher::create([
            'rvid' => $rvid,
            'party_id' => $sale->customer_id,
            'type' => 'customer',
            'total_amount' => $sale->cash,
            'receipt_date' => now()->format('Y-m-d'),
            'row_account_id' => json_encode($accountIds),
            'row_account_head' => json_encode([]), // Can be populated if needed
            'row_amount' => json_encode($amounts),
            'remarks' => 'Auto-generated from Sale Invoice #'.$sale->invoice_no,
            'processed' => true, // Mark as processed since it's linked to sale
        ]);
    }

    private function _getSaleItems($sale)
    {
        // Legacy support wrapper or direct relation
        // Re-implementing correctly based on new structure
        return $sale->items->map(function ($item) {
            $variant = [];
            if (!empty($item->color)) {
                // Try base64 decode first (since frontend sends base64 variant_data in color field)
                $b64Decoded = base64_decode($item->color, true);
                if ($b64Decoded !== false) {
                    $json = json_decode($b64Decoded, true);
                    if (is_array($json)) {
                        $variant = $json;
                    }
                }
                // Fallback to normal JSON
                if (empty($variant)) {
                    $json = json_decode($item->color, true);
                    if (is_array($json)) {
                        $variant = $json;
                    } else {
                        $variant = ['color' => $item->color];
                    }
                }
            }

            $liveVariant = null;
            if ($item->product && !empty($item->product->color)) {
                $prodVariants = json_decode($item->product->color, true);
                if (is_array($prodVariants)) {
                    if (!empty($variant['barcode'])) {
                        $liveVariant = collect($prodVariants)->firstWhere('barcode', $variant['barcode']);
                    }
                    if (!$liveVariant && !empty($variant['name'])) {
                        $liveVariant = collect($prodVariants)->first(function($v) use ($variant) {
                            $n1 = strtolower(trim($v['name'] ?? ''));
                            $n2 = strtolower(trim($variant['name'] ?? ''));
                            return $n1 === $n2 || ($n1 && $n2 && (str_contains($n1, $n2) || str_contains($n2, $n1)));
                        });
                    }
                    if (!$liveVariant && !empty($variant['size']) && $variant['size'] !== '-') {
                        $liveVariant = collect($prodVariants)->first(function($v) use ($variant) {
                            $s1 = strtolower(trim($v['size'] ?? ''));
                            $s2 = strtolower(trim($variant['size'] ?? ''));
                            return $s1 === $s2 || ($s1 && $s2 && (str_starts_with($s1, $s2) || str_starts_with($s2, $s1)));
                        });
                    }
                    if (!$liveVariant && count($prodVariants) === 1) {
                        $liveVariant = $prodVariants[0];
                    }
                }
            }

            $ppb = 1;
            if ($liveVariant && !empty($liveVariant['conv_factor']) && (float)$liveVariant['conv_factor'] > 0) {
                $ppb = (float)$liveVariant['conv_factor'];
            } elseif ($liveVariant && !empty($liveVariant['pieces_per_box']) && (float)$liveVariant['pieces_per_box'] > 0) {
                $ppb = (float)$liveVariant['pieces_per_box'];
            } elseif ($item->product && (float)$item->product->pieces_per_box > 0) {
                $ppb = (float)$item->product->pieces_per_box;
            } elseif (!empty($variant['conv_factor']) && (float)$variant['conv_factor'] > 0) {
                $ppb = (float)$variant['conv_factor'];
            } elseif (!empty($variant['pieces_per_box']) && (float)$variant['pieces_per_box'] > 0) {
                $ppb = (float)$variant['pieces_per_box'];
            } elseif (!empty($item->pieces_per_box) && (float)$item->pieces_per_box > 0) {
                $ppb = (float)$item->pieces_per_box;
            }

            if ($ppb <= 0) $ppb = 1;

            $variantUnit = $variant['unit'] ?? ($liveVariant['unit'] ?? ($item->product->unit->name ?? ''));

            return [
                'is_manual' => $item->is_manual,
                'product_id' => $item->product_id,
                'item_name' => $item->is_manual ? ($item->product_name ?? 'Manual Product') . ' (Manual)' : ($item->product_name ?? $item->product->item_name ?? 'Item'),
                'item_code' => $item->product->item_code ?? '',
                'brand' => $item->product->brand->name ?? '',
                'unit' => $item->product->unit->name ?? '', // Access name if relation exists
                'qty' => (int) $item->total_pieces, // Use Pieces for Return Logic (Matches Price-Per-Piece)
                'qty_box' => (float) $item->qty, // Store Box Count separately
                'total_pieces' => (int) $item->total_pieces,
                'loose_pieces' => (int) $item->loose_pieces,
                'price' => (float) $item->price, // Price Per Piece / Unit
                'discount' => (float) $item->discount_percent, // Legacy
                'discount_percent' => (float) $item->discount_percent,
                'discount_amount' => (float) $item->discount_amount,
                'total' => (float) $item->total,
                'variant_name' => $variant['name'] ?? '',
                'color_val' => $variant['color'] ?? '',
                'size_val' => $variant['size'] ?? '',
                'variant_unit' => $variantUnit,
                'weight_per_piece' => $variant['weight_per_piece'] ?? $item->product->weight_per_piece ?? 0,
                'color' => is_array($variant) ? $variant : [$item->color], // for legacy compatibility
                'pieces_per_box' => $ppb,
                'price_per_piece' => ($item->total_pieces > 0) ? ($item->total / $item->total_pieces) : 0,
                // Add dimension and m² data from product
                'height' => $item->product->height ?? 0,
                'width' => $item->product->width ?? 0,
                'pieces_per_m2' => $item->product->pieces_per_m2 ?? 0,
                'size_mode' => $item->size_mode ?? $item->product->size_mode ?? 'std',
                'batch_id' => $item->batch_id ?? null,
                'batch_no' => $item->batch_no ?? null,
                'serials' => $item->serials ?? null,
                'model' => $item->product->model ?? null,
                'bonus_qty' => (float) ($item->bonus_qty ?? 0),
                'mfg_date' => $item->mfg_date ?? '',
                'exp_date' => $item->exp_date ?? '',
                'gross_amount' => (float) ($item->gross_amount ?? 0),
                'exclusive_gst_amount' => (float) ($item->exclusive_gst_amount ?? 0),
                'sales_tax_percent' => (float) ($item->sales_tax_percent ?? 0),
                'sales_tax_amount' => (float) ($item->sales_tax_amount ?? 0),
                'further_tax_percent' => (float) ($item->further_tax_percent ?? 0),
                'further_tax_amount' => (float) ($item->further_tax_amount ?? 0),
            ];
        });
    }

    /**
     * Generate a unique invoice number with duplicate checking
     */
    private function generateUniqueInvoiceNo()
    {
        $maxAttempts = 100;
        $attempt = 0;
        
        $lastId = \App\Models\Sale::max('id') ?? 0;

        do {
            $lastId++;
            $invoiceNo = 'INV-'.str_pad($lastId, 4, '0', STR_PAD_LEFT);

            // Check if this invoice number already exists
            $exists = \App\Models\Sale::where('invoice_no', $invoiceNo)->exists();

            if (! $exists) {
                return $invoiceNo;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        // Fallback: use timestamp-based unique number
        return 'INV-'.date('YmdHis');
    }

    private function rollbackPostedSale(Sale $sale)
    {
        // 1. Restore Stock (Warehouse stock, batch quantities, and product serials)
        $this->handleStockImpact($sale, 'in');

        // Delete stock movements for this sale (both 'sale' out and 'sale_in' restore records)
        DB::table('stock_movements')
            ->where('ref_id', $sale->id)
            ->where(function ($q) {
                $q->where('ref_type', 'sale')
                  ->orWhere('ref_type', 'sale_in')
                  ->orWhere('ref_type', 'sale_return');
            })
            ->delete();

        // 2. Reverse and delete Journal Entries & Vouchers
        $journalService = app(\App\Services\JournalEntryService::class);

        // Reverse any direct journal entries linked to this Sale model
        $directEntries = \App\Models\JournalEntry::where('source_type', get_class($sale))
            ->where('source_id', $sale->id)
            ->get();
        foreach ($directEntries as $dEntry) {
            $journalService->updateAccountBalance($dEntry->account_id, $dEntry->credit, $dEntry->debit);
            $dEntry->delete();
        }

        // Find all VoucherMaster records related to this sale (Sale JV and Auto-Receipt RV)
        $vouchers = \App\Models\VoucherMaster::where(function ($q) use ($sale) {
            $q->where('remarks', 'like', "%#{$sale->invoice_no}%")
              ->orWhere('remarks', 'like', "%{$sale->invoice_no}%");
        })->get();

        foreach ($vouchers as $voucher) {
            // Reverse account balances and delete journal entries
            $journalService->reverseEntriesForSource($voucher);

            // Delete details
            \App\Models\VoucherDetail::where('voucher_master_id', $voucher->id)->delete();

            // Delete master
            $voucher->delete();
        }

        // 3. Remove associated CustomerPayments (e.g., POS Exchange refunds)
        \App\Models\CustomerPayment::where('note', 'like', "%{$sale->invoice_no}%")->delete();

        // 4. Remove legacy ReceiptsVoucher / PaymentVoucher linked to this invoice
        \App\Models\ReceiptsVoucher::where('remarks', 'like', "%#{$sale->invoice_no}%")
            ->orWhere('remarks', 'like', "%{$sale->invoice_no}%")
            ->delete();

        \App\Models\PaymentVoucher::where('remarks', 'like', "%#{$sale->invoice_no}%")
            ->orWhere('remarks', 'like', "%{$sale->invoice_no}%")
            ->delete();

        // 5. Customer Ledger Cleanup & Running Balance Recalculation
        if ($sale->customer_id) {
            \App\Models\CustomerLedger::where('customer_id', $sale->customer_id)
                ->where(function ($q) use ($sale) {
                    $q->where('description', 'like', "%#{$sale->invoice_no}%")
                      ->orWhere('description', 'like', "%{$sale->invoice_no}%");
                })
                ->delete();

            $this->recalculateCustomerLedger($sale->customer_id);
        }
    }

    /**
     * Recalculate running balance for all ledger entries of a customer chronologically.
     */
    public function recalculateCustomerLedger($customerId)
    {
        $customer = \App\Models\Customer::find($customerId);
        if (!$customer) {
            return;
        }

        $ledgerEntries = \App\Models\CustomerLedger::where('customer_id', $customerId)
            ->orderBy('id', 'asc')
            ->get();

        if ($ledgerEntries->isEmpty()) {
            $customer->previous_balance = (float) ($customer->opening_balance ?? 0);
            $customer->save();
            return;
        }

        // Base balance before transactions
        $firstEntry = $ledgerEntries->first();
        if ($firstEntry && (str_contains(strtolower($firstEntry->description ?? ''), 'opening balance') || $firstEntry->opening_balance > 0)) {
            $currentBalance = 0;
        } else {
            $currentBalance = (float) ($customer->opening_balance ?? 0);
        }

        foreach ($ledgerEntries as $entry) {
            if (str_contains(strtolower($entry->description ?? ''), 'opening balance')) {
                $obAmount = (float) ($entry->opening_balance > 0 ? $entry->opening_balance : ($customer->opening_balance ?? 0));
                $entry->previous_balance = 0;
                $entry->opening_balance = $obAmount;
                $entry->closing_balance = $obAmount;
                $currentBalance = $obAmount;
                $entry->save();
                continue;
            }

            $netChange = (float) $entry->closing_balance - (float) $entry->previous_balance;
            $entry->previous_balance = $currentBalance;
            $currentBalance += $netChange;
            $entry->closing_balance = $currentBalance;
            $entry->save();
        }

        $customer->previous_balance = $currentBalance;
        $customer->save();
    }

    /**
     * Pre-check invoice deletion: checks permissions, linked returns, and linked payment vouchers.
     */
    public function checkDelete($id)
    {
        if (!auth()->user()->can('sales.delete')) {
            return response()->json([
                'can_delete' => false,
                'message' => 'You do not have permission to delete sales invoices (permission:sales.delete).'
            ], 403);
        }

        $sale = Sale::with(['customer_relation'])->findOrFail($id);

        // Check for linked returns
        $returns = \App\Models\SaleReturn::where('sale_id', $sale->id)->get();
        if ($returns->count() > 0) {
            $returnList = $returns->pluck('return_invoice')->filter()->implode(', ') ?: ('#' . $returns->pluck('id')->implode(', #'));
            return response()->json([
                'can_delete' => false,
                'has_returns' => true,
                'message' => "Cannot delete invoice <strong>{$sale->invoice_no}</strong> because it has linked Sale Return(s): <strong>{$returnList}</strong>. Please delete or cancel the sale return(s) first."
            ]);
        }

        // Check for separate / manual payment vouchers (excluding the auto-receipt generated upon posting)
        $manualVouchers = \App\Models\VoucherMaster::where('remarks', 'like', "%#{$sale->invoice_no}%")
            ->where('remarks', 'not like', 'Auto-Receipt%')
            ->where('remarks', 'not like', 'Sale Invoice%')
            ->get();

        $legacyReceipts = \App\Models\ReceiptsVoucher::where('reference_no', 'like', "%{$sale->invoice_no}%")
            ->orWhere('remarks', 'like', "%{$sale->invoice_no}%")
            ->get();

        $legacyPayments = \App\Models\PaymentVoucher::where('reference_no', 'like', "%{$sale->invoice_no}%")
            ->orWhere('remarks', 'like', "%{$sale->invoice_no}%")
            ->get();

        $paymentWarnings = [];
        if ($manualVouchers->count() > 0) {
            $vNos = $manualVouchers->pluck('voucher_no')->implode(', ');
            $paymentWarnings[] = "Linked Accounts Voucher(s): <strong>{$vNos}</strong>";
        }
        if ($legacyReceipts->count() > 0) {
            $rNos = $legacyReceipts->pluck('rvid')->filter()->implode(', ') ?: ('#' . $legacyReceipts->pluck('id')->implode(', #'));
            $paymentWarnings[] = "Linked Receipt Voucher(s): <strong>{$rNos}</strong>";
        }
        if ($legacyPayments->count() > 0) {
            $pNos = $legacyPayments->pluck('pvid')->filter()->implode(', ') ?: ('#' . $legacyPayments->pluck('id')->implode(', #'));
            $paymentWarnings[] = "Linked Payment Voucher(s): <strong>{$pNos}</strong>";
        }

        $hasLinkedPayments = count($paymentWarnings) > 0;
        $paymentWarningText = $hasLinkedPayments 
            ? implode('<br>', $paymentWarnings) . '<br><small class="text-danger">Aap is invoice ko delete karenge to unlinked payment record na bache is baat ka dhyan rakhein.</small>'
            : '';

        return response()->json([
            'can_delete' => true,
            'has_returns' => false,
            'has_linked_payments' => $hasLinkedPayments,
            'payment_warning' => $paymentWarningText,
            'sale_status' => $sale->sale_status,
            'invoice_no' => $sale->invoice_no ?: ('#' . $sale->id),
        ]);
    }

    public function confirmBooking($id)
    {
        $sale = Sale::findOrFail($id);

        if (!in_array($sale->sale_status, ['booked', 'quotation'])) {
            return redirect()->back()->with('error', 'Only booked or quotation sales can be confirmed.');
        }

        $originalStatus = $sale->sale_status;
        $typeLabel = $originalStatus === 'quotation' ? 'Quotation' : 'Booking';

        DB::beginTransaction();
        try {
            $oldInvoiceNo = $sale->invoice_no;

            // When converting from quotation, assign standard Sale Invoice Series (e.g. INV-0001)
            // and preserve original quotation number in reference
            if ($originalStatus === 'quotation') {
                $newSaleInvoiceNo = \App\Models\Sale::generateInvoiceNo();
                $sale->invoice_no = $newSaleInvoiceNo;
                $quotationRef = "Quotation #{$oldInvoiceNo}";
                $sale->reference = !empty($sale->reference) ? ($sale->reference . " | {$quotationRef}") : $quotationRef;

                // Increment counter for the newly assigned sale series
                \App\Models\InvoiceSeries::incrementCounterForInvoice($newSaleInvoiceNo);
            }

            // Update status to posted (which is the system-wide confirmed status)
            $sale->sale_status = 'posted';
            $sale->save();

            // 1. DEDUCT STOCK FROM WAREHOUSE
            $this->handleStockImpact($sale, 'out');

            // 2. LEGACY LEDGER: Post Invoice First (Increases Balance)
            $this->updateLedger($sale);

            // 3. PROFESSIONAL LEDGER POSTING (ENTRY 1: THE INVOICE)
            $journalService = app(\App\Services\JournalEntryService::class);
            $balanceService = app(\App\Services\BalanceService::class);

            $custForVoucher = $sale->customer_relation ?? \App\Models\Customer::find($sale->customer_id);

            if ($custForVoucher) {
                $balanceService->createSaleVoucher(
                    $custForVoucher,
                    $sale->total_net,
                    $sale->invoice_no,
                    $sale->created_at->format('Y-m-d')
                );
            }

            // 4. AUTO RECEIPT (ENTRY 2: THE PAYMENT)
            $transactionService = app(\App\Services\TransactionService::class);
            $transactionService->createReceiptFromSale($sale);

            DB::commit();

            $successMsg = ($originalStatus === 'quotation')
                ? "Quotation #{$oldInvoiceNo} has been successfully converted to Sale #{$sale->invoice_no}."
                : "Booking #{$sale->invoice_no} confirmed and converted to sale successfully.";

            return redirect()->back()->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error($typeLabel . ' Confirmation Error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Failed to confirm ' . strtolower($typeLabel) . ': '.$e->getMessage());
        }
    }

    /**
     * Safely delete sale invoice with complete stock, customer ledger, and journal entry reversal.
     */
    public function destroy($id, Request $request)
    {
        if (!auth()->user()->can('sales.delete')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized! You do not have permission to delete sales invoices (permission:sales.delete).'
                ], 403);
            }
            return redirect()->back()->with('error', 'Unauthorized! You do not have permission to delete sales invoices.');
        }

        $sale = Sale::with(['items', 'customer_relation'])->findOrFail($id);
        $invNo = $sale->invoice_no ?: ('#' . $sale->id);
        $typeLabel = $sale->sale_status === 'quotation' ? 'Quotation' : 'Sale Invoice';

        // Check for linked Sale Returns
        $hasReturns = \App\Models\SaleReturn::where('sale_id', $sale->id)->exists();
        if ($hasReturns) {
            $msg = "Cannot delete invoice {$invNo} because it has linked Sale Return(s). Please remove or reverse the sale returns first.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        // Check for manual linked payment vouchers (unless confirmed/force requested)
        $manualVouchersExist = \App\Models\VoucherMaster::where('remarks', 'like', "%#{$sale->invoice_no}%")
            ->where('remarks', 'not like', 'Auto-Receipt%')
            ->where('remarks', 'not like', 'Sale Invoice%')
            ->exists();

        $legacyReceiptsExist = \App\Models\ReceiptsVoucher::where('reference_no', 'like', "%{$sale->invoice_no}%")
            ->orWhere('remarks', 'like', "%{$sale->invoice_no}%")
            ->exists();

        if (($manualVouchersExist || $legacyReceiptsExist) && !$request->has('force_delete')) {
            $msg = "Cannot delete invoice {$invNo}: Separate payment voucher(s) are linked to this invoice. Please unlink or delete the payment voucher first to prevent orphaned records.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg, 'has_linked_payments' => true], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        DB::beginTransaction();
        try {
            // If posted or returned, safely rollback stock, accounting entries, and customer ledger
            if (in_array($sale->sale_status, ['posted', 'returned'])) {
                $this->rollbackPostedSale($sale);
            }

            // Delete sale items
            $sale->items()->delete();

            // Delete sale record
            $sale->delete();

            DB::commit();

            $successMsg = "{$typeLabel} {$invNo} has been deleted successfully. Stock, customer ledger, and journal entries have been safely reversed.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $successMsg
                ]);
            }

            return redirect()->route('sale.index')->with('success', $successMsg);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Failed to delete {$typeLabel} {$invNo}: " . $e->getMessage(), [
                'sale_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            $errMsg = "Failed to delete {$typeLabel} {$invNo}: " . $e->getMessage();
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errMsg
                ], 500);
            }

            return redirect()->back()->with('error', $errMsg);
        }
    }

    public function fetchInvoiceSeries(Request $request)
    {
        $seriesList = \App\Models\InvoiceSeries::orderBy('prefix', 'asc')->get();
        $activePrefix = $request->prefix ?: ($seriesList->where('is_default', 1)->first()->prefix ?? ($seriesList->first()->prefix ?? 'INV'));
        $invoiceNo = \App\Models\InvoiceSeries::generateNextNo($activePrefix);

        return response()->json([
            'series' => $seriesList,
            'active_prefix' => strtoupper($activePrefix),
            'invoice_no' => $invoiceNo
        ]);
    }

    /**
     * Create or update invoice series prefix
     */
    public function storeInvoiceSeries(Request $request)
    {
        $request->validate([
            'prefix' => 'required|string|max:20',
            'next_number' => 'required|numeric|min:1',
            'padding' => 'nullable|numeric|min:1|max:10',
        ]);

        $prefix = strtoupper(trim($request->prefix));
        $padding = (int) ($request->padding ?: 4);
        $nextNum = (int) $request->next_number;

        $series = \App\Models\InvoiceSeries::updateOrCreate(
            ['prefix' => $prefix],
            [
                'next_number' => $nextNum,
                'padding' => $padding,
                'updated_at' => now()
            ]
        );

        $generatedNo = \App\Models\InvoiceSeries::generateNextNo($prefix);

        return response()->json([
            'success' => true,
            'message' => "Invoice Series {$prefix} saved successfully!",
            'series' => $series,
            'prefix' => $prefix,
            'invoice_no' => $generatedNo
        ]);
    }

    /**
     * AJAX endpoint to generate next invoice number for selected prefix
     */
    public function generateInvoiceNoAjax(Request $request)
    {
        $prefix = $request->prefix;
        $invoiceNo = \App\Models\InvoiceSeries::generateNextNo($prefix);
        return response()->json(['invoice_no' => $invoiceNo]);
    }

    /**
     * AJAX endpoint to fetch active batches for a product and warehouse
     */
    public function getProductBatches(Request $request)
    {
        $productId = $request->get('product_id');
        $warehouseId = $request->get('warehouse_id');
        $strict = $request->boolean('strict_warehouse', false);

        $product = \App\Models\Product::find($productId);
        if ($product && in_array($product->tracking_type, ['simple', 'serial'])) {
            return response()->json([
                'success' => true,
                'is_batch_product' => false,
                'batches' => []
            ]);
        }
        $isBatchProduct = ($product && $product->tracking_type === 'batch') || \App\Models\ProductBatch::where('product_id', $productId)->exists();

        $query = \App\Models\ProductBatch::with('warehouse')
            ->where('product_id', $productId);

        if ($warehouseId && $strict) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($warehouseId) {
            $query->orderByRaw("CASE WHEN warehouse_id = ? THEN 0 ELSE 1 END", [$warehouseId]);
        }
        $batches = $query->orderByRaw("CASE WHEN qty > 0 THEN 0 ELSE 1 END")
            ->orderBy('qty', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'is_batch_product' => $isBatchProduct,
            'batches' => $batches
        ]);
    }

    /**
     * AJAX endpoint to fetch available serials/IMEIs for a product and warehouse
     */
    public function getProductSerials(Request $request)
    {
        $productId = $request->get('product_id');
        $warehouseId = $request->get('warehouse_id');
        $search = $request->get('search');
        $strict = $request->boolean('strict_warehouse', false);

        $product = \App\Models\Product::find($productId);
        if ($product && $product->tracking_type === 'batch') {
            return response()->json([
                'success' => true,
                'is_serial_product' => false,
                'serials' => [],
                'non_serial_stock' => 0,
                'total_stock' => 0,
            ]);
        }

        $query = \App\Models\ProductSerial::with('warehouse')
            ->where('product_id', $productId)
            ->where('status', 'available');

        if ($warehouseId && $strict) {
            $query->where('warehouse_id', $warehouseId);
        }

        if (!empty($search)) {
            $query->where('serial_number', 'like', "%{$search}%");
        }

        if ($warehouseId) {
            $query->orderByRaw("CASE WHEN warehouse_id = ? THEN 0 ELSE 1 END", [$warehouseId]);
        }
        $serials = $query->orderBy('warehouse_id', 'asc')
            ->orderBy('serial_number', 'asc')
            ->get();

        $hasSerials = $serials->count() > 0 || \App\Models\ProductSerial::where('product_id', $productId)->exists();
        $isSerialProduct = ($product && $product->tracking_type === 'serial') || $hasSerials;

        // Compute Total Physical Stock and Non-Serial Stock
        $stockQuery = \App\Models\WarehouseStock::where('product_id', $productId);
        if ($warehouseId) {
            $stockQuery->where('warehouse_id', $warehouseId);
        }
        $totalStock = (float) $stockQuery->sum('total_pieces');

        // Count available serials in the matching scope
        $availableSerialsCountQuery = \App\Models\ProductSerial::where('product_id', $productId)->where('status', 'available');
        if ($warehouseId) {
            $availableSerialsCountQuery->where('warehouse_id', $warehouseId);
        }
        $serialStockCount = (float) $availableSerialsCountQuery->count();

        // Non-serial stock = max(0, Total Pieces - Available Serials)
        $nonSerialStock = max(0, $totalStock - $serialStockCount);

        return response()->json([
            'success' => true,
            'is_serial_product' => $isSerialProduct,
            'serials' => $serials,
            'total_stock' => $totalStock,
            'serial_count' => $serialStockCount,
            'non_serial_stock' => $nonSerialStock,
        ]);
    }
}
