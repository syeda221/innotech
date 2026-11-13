<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\JournalEntry;
use App\Models\VendorLedger;
use App\Models\VendorPayment;
use App\Models\VendorBilty;
use App\Models\Purchase;

class VendorController extends Controller
{
    /**
     * Show all vendors with search and location filters.
     */
    public function index(Request $request)
    {
        $query = Vendor::query();

        // Keyword Search
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('cnic', 'LIKE', "%{$search}%")
                  ->orWhere('ntn_number', 'LIKE', "%{$search}%")
                  ->orWhere('strn_number', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%");
            });
        }

        // Location Filters
        if ($request->filled('province') && $request->province !== 'all') {
            $query->where('province', $request->province);
        }

        if ($request->filled('city') && $request->city !== 'all') {
            $query->where('city', $request->city);
        }

        if ($request->filled('area') && $request->area !== 'all') {
            $query->where('area', $request->area);
        }

        if ($request->filled('zone') && $request->zone !== 'all') {
            $query->where('zone', $request->zone);
        }

        // Party Type Filter
        if ($request->filled('party_type') && $request->party_type !== 'all') {
            if ($request->party_type === 'both') {
                $query->where(function ($q) {
                    $q->where('party_type', 'both')
                      ->orWhereNotNull('linked_customer_id');
                });
            } else {
                $query->where('party_type', 'vendor');
            }
        }

        $vendors = $query->orderByDesc('id')->get();

        // Calculate KPI Metrics for ERP Dashboard Cards
        $stats = [
            'total_vendors' => Vendor::count(),
            'total_opening_balance' => (float) Vendor::sum('opening_balance'),
            'both_parties' => Vendor::where('party_type', 'both')->orWhereNotNull('linked_customer_id')->count(),
            'total_cities' => Vendor::whereNotNull('city')->where('city', '<>', '')->distinct('city')->count('city'),
        ];

        $zones = \App\Models\Zone::orderBy('zone')->get();

        return view('admin_panel.vendors.index', compact('vendors', 'stats', 'zones'));
    }

    /**
     * Store or update vendor information.
     */
    public function store(Request $request)
    {
        // Consolidate phone / mobile if needed
        if (empty($request->phone) && !empty($request->mobile)) {
            $request->merge(['phone' => $request->mobile]);
        }

        // Clean opening_balance input: if empty or missing, default to 0.00
        if (!$request->has('opening_balance') || $request->opening_balance === '' || $request->opening_balance === null) {
            $request->merge(['opening_balance' => 0.00]);
        }

        // Comprehensive validation with clear user-friendly messages
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'party_type' => 'nullable|string|in:vendor,both',
            'opening_balance' => 'nullable|numeric',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'cnic' => 'nullable|string|max:50',
            'ntn_number' => 'nullable|string|max:50',
            'strn_number' => 'nullable|string|max:50',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:100',
            'zone' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
        ], [
            'name.required' => 'Vendor Name is required.',
            'opening_balance.numeric' => 'Opening balance must be a valid number.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            return DB::transaction(function () use ($request) {
                $partyType = $request->party_type ?? 'vendor';
                $newOpening = (float) ($request->opening_balance ?? 0.00);

                $vendorData = [
                    'name' => trim($request->name),
                    'party_type' => $partyType,
                    'email' => $request->email ? trim($request->email) : null,
                    'phone' => $request->phone ? trim($request->phone) : null,
                    'cnic' => $request->cnic ? trim($request->cnic) : null,
                    'ntn_number' => $request->ntn_number ? trim($request->ntn_number) : null,
                    'strn_number' => $request->strn_number ? trim($request->strn_number) : null,
                    'province' => $request->province ?: null,
                    'city' => $request->city ?: null,
                    'area' => $request->area ?: null,
                    'zone' => $request->zone ?: null,
                    'address' => $request->address ?: null,
                    'opening_balance' => $newOpening,
                ];

                if ($request->id) {
                    $vendor = Vendor::findOrFail($request->id);
                    $vendor->update($vendorData);
                } else {
                    $vendor = Vendor::create($vendorData);
                }

                // Sync opening balance in ledgers and journal entries
                $this->syncOpeningBalance($vendor, $newOpening);

                // Synchronize with Customer table if party_type is 'both'
                $this->syncLinkedCustomer($vendor, $partyType);

                $message = $request->id ? 'Vendor updated successfully.' : 'Vendor created successfully.';

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'vendor' => $vendor,
                        'message' => $message
                    ]);
                }

                return back()->with('success', $message);
            });
        } catch (\Throwable $e) {
            Log::error("Vendor Store Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            $errMsg = $e->getMessage();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $errMsg,
                    'errors' => [
                        'general' => [$errMsg]
                    ]
                ], 422);
            }

            return back()->withErrors(['error' => $errMsg])->withInput();
        }
    }

    /**
     * Show vendor for editing via AJAX.
     */
    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        return response()->json([
            'success' => true,
            'vendor' => $vendor
        ]);
    }

    /**
     * Synchronize Vendor with linked Customer record when party_type is 'both'
     */
    private function syncLinkedCustomer(Vendor $vendor, string $partyType): void
    {
        if ($partyType === 'both') {
            $customer = null;
            // 1. Look up by explicit linked_customer_id
            if ($vendor->linked_customer_id) {
                $customer = \App\Models\Customer::find($vendor->linked_customer_id);
            }
            // 2. Or lookup by customer linked to this vendor ID
            if (!$customer) {
                $customer = \App\Models\Customer::where('linked_vendor_id', $vendor->id)->first();
            }
            // 3. Or lookup by exact customer_name
            if (!$customer) {
                $customer = \App\Models\Customer::where('customer_name', $vendor->name)->first();
            }

            $opening = !empty($vendor->opening_balance) ? (float) $vendor->opening_balance : 0.00;

            if ($customer) {
                $customer->update([
                    'customer_name' => $vendor->name,
                    'mobile' => $vendor->phone ?: $customer->mobile,
                    'email_address' => $vendor->email ?: $customer->email_address,
                    'cnic' => $vendor->cnic ?: $customer->cnic,
                    'address' => $vendor->address ?: $customer->address,
                    'province' => $vendor->province ?: $customer->province,
                    'city' => $vendor->city ?: $customer->city,
                    'area' => $vendor->area ?: $customer->area,
                    'zone' => $vendor->zone ?: $customer->zone,
                    'party_type' => 'both',
                    'linked_vendor_id' => $vendor->id,
                    'status' => 'active',
                ]);
            } else {
                $maxId = (int) \App\Models\Customer::max('id') + 1;
                $newCustId = 'CUST-' . str_pad($maxId, 4, '0', STR_PAD_LEFT);
                $customer = \App\Models\Customer::create([
                    'customer_id' => $newCustId,
                    'customer_name' => $vendor->name,
                    'mobile' => $vendor->phone,
                    'email_address' => $vendor->email,
                    'cnic' => $vendor->cnic,
                    'address' => $vendor->address,
                    'province' => $vendor->province,
                    'city' => $vendor->city,
                    'area' => $vendor->area,
                    'zone' => $vendor->zone,
                    'customer_type' => 'Main Customer',
                    'party_type' => 'both',
                    'linked_vendor_id' => $vendor->id,
                    'status' => 'active',
                    'opening_balance' => $opening,
                    'previous_balance' => $opening,
                    'balance_range' => 0.00,
                ]);
            }

            // Sync dynamic custom fields (NTN and STRN)
            if (!empty($vendor->ntn_number)) {
                $ntnDef = \App\Models\CustomerFieldDefinition::where('field_key', 'ntn_number')->first();
                if ($ntnDef) {
                    \App\Models\CustomerCustomFieldValue::updateOrCreate(
                        ['customer_id' => $customer->id, 'field_definition_id' => $ntnDef->id],
                        ['field_key' => 'ntn_number', 'value' => $vendor->ntn_number]
                    );
                }
            }
            if (!empty($vendor->strn_number)) {
                $strnDef = \App\Models\CustomerFieldDefinition::where('field_key', 'strn_number')->first();
                if ($strnDef) {
                    \App\Models\CustomerCustomFieldValue::updateOrCreate(
                        ['customer_id' => $customer->id, 'field_definition_id' => $strnDef->id],
                        ['field_key' => 'strn_number', 'value' => $vendor->strn_number]
                    );
                }
            }

            if ($vendor->linked_customer_id !== $customer->id || $vendor->party_type !== 'both') {
                $vendor->update([
                    'linked_customer_id' => $customer->id,
                    'party_type' => 'both',
                ]);
            }
        } else {
            // Party type is vendor only
            if ($vendor->linked_customer_id) {
                $customer = \App\Models\Customer::find($vendor->linked_customer_id);
                if ($customer) {
                    $customer->update(['party_type' => 'customer', 'linked_vendor_id' => null]);
                }
                $vendor->update(['linked_customer_id' => null, 'party_type' => 'vendor']);
            }
        }
    }

    /**
     * Synchronize opening balance in VendorLedger and JournalEntry.
     */
    private function syncOpeningBalance(Vendor $vendor, float $newOpening)
    {
        try {
            $balanceService = app(\App\Services\BalanceService::class);
            $journalService = app(\App\Services\JournalEntryService::class);
            $apId = $balanceService->getAccountsPayableId();
            $entryDate = $vendor->created_at ? $vendor->created_at->format('Y-m-d') : now()->format('Y-m-d');

            // 1. Find existing Opening Balance Journal Entry for this vendor
            $obJournal = JournalEntry::where(function ($q) use ($vendor) {
                $q->where('source_type', Vendor::class)->where('source_id', $vendor->id);
            })
            ->where(function ($q) {
                $q->where('description', 'Opening Balance')
                  ->orWhere('description', 'LIKE', 'Opening Balance%');
            })
            ->first();

            if (!$obJournal) {
                $obJournal = JournalEntry::where('party_type', Vendor::class)
                    ->where('party_id', $vendor->id)
                    ->where(function ($q) {
                        $q->where('description', 'Opening Balance')
                          ->orWhere('description', 'LIKE', 'Opening Balance%');
                    })
                    ->first();
            }

            if ($newOpening > 0) {
                if ($obJournal) {
                    $diff = $newOpening - (float) $obJournal->credit;
                    if ($diff != 0) {
                        $account = \App\Models\Account::find($obJournal->account_id ?: $apId);
                        if ($account) {
                            $account->current_balance = ($account->current_balance ?? 0) + $diff;
                            $account->save();
                        }
                        $obJournal->credit = $newOpening;
                        $obJournal->debit = 0;
                        $obJournal->account_id = $obJournal->account_id ?: $apId;
                        $obJournal->party_type = Vendor::class;
                        $obJournal->party_id = $vendor->id;
                        $obJournal->source_type = Vendor::class;
                        $obJournal->source_id = $vendor->id;
                        $obJournal->save();
                    }
                } else {
                    $journalService->recordEntry(
                        $vendor,
                        $apId,
                        0, // Debit
                        $newOpening, // Credit (Liability)
                        "Opening Balance",
                        $entryDate,
                        $vendor
                    );
                }
            } else {
                if ($obJournal) {
                    $account = \App\Models\Account::find($obJournal->account_id);
                    if ($account) {
                        $account->current_balance = ($account->current_balance ?? 0) - (float) $obJournal->credit + (float) $obJournal->debit;
                        $account->save();
                    }
                    $obJournal->delete();
                }
            }

            // 2. Sync VendorLedger
            $obLedger = VendorLedger::where('vendor_id', $vendor->id)
                ->where('previous_balance', 0)
                ->first();

            if ($newOpening > 0) {
                if ($obLedger) {
                    $obLedger->update([
                        'opening_balance' => $newOpening,
                        'closing_balance' => $newOpening,
                    ]);
                } else {
                    VendorLedger::create([
                        'vendor_id' => $vendor->id,
                        'admin_or_user_id' => Auth::id() ?? 1,
                        'previous_balance' => 0,
                        'opening_balance' => $newOpening,
                        'closing_balance' => $newOpening,
                        'created_at' => $vendor->created_at ?? now(),
                    ]);
                }
            } else {
                if ($obLedger) {
                    $obLedger->delete();
                }
            }
        } catch (\Exception $e) {
            Log::error("Vendor syncOpeningBalance Error: " . $e->getMessage());
        }
    }

    /**
     * Soft delete vendor and related ledger entry.
     */
    public function delete($id)
    {
        $vendor = Vendor::with('ledger')->findOrFail($id);
        $vendor->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Vendor deleted successfully.'
            ]);
        }

        return back()->with('success', 'Vendor deleted successfully.');
    }

    /**
     * Show vendor ledger summary.
     */
    public function vendors_ledger()
    {
        if (Auth::check()) {
            $VendorLedgers = VendorLedger::with('vendor')->get();
            $balanceService = app(\App\Services\BalanceService::class);
            
            foreach ($VendorLedgers as $ledger) {
                $ledger->formatted_closing_balance = $balanceService->getVendorBalance($ledger->vendor_id);
            }
            
            return view('admin_panel.vendors.vendors_ledger', compact('VendorLedgers'));
        } else {
            return redirect()->back();
        }
    }

    /**
     * Show all vendor payments.
     */
    public function vendor_payments()
    {
        $userId = Auth::id();
        $payments = VendorPayment::with('vendor')
            ->where('admin_or_user_id', $userId)
            ->orderByDesc('payment_date')
            ->get();

        $vendors = Vendor::all();
        return view('admin_panel.vendors.vendor_payments', compact('payments', 'vendors'));
    }

    /**
     * Store vendor payment and update ledger.
     */
    public function store_vendor_payment(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
            'note' => 'nullable|string',
            'adjustment_type' => 'required|in:plus,minus',
        ]);

        VendorPayment::create([
            'vendor_id' => $request->vendor_id,
            'admin_or_user_id' => Auth::id(),
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'note' => $request->note,
        ]);

        $ledger = VendorLedger::where('vendor_id', $request->vendor_id)->first();
        if ($ledger) {
            $ledger->closing_balance += ($request->adjustment_type === 'minus' ? -1 : 1) * $request->amount;
            $ledger->save();
        }

        return redirect()->back()->with('success', 'Vendor payment recorded.');
    }

    /**
     * Show all vendor bilties.
     */
    public function vendor_bilties()
    {
        $bilties = VendorBilty::with(['vendor', 'purchase'])->orderByDesc('id')->get();
        $vendors = Vendor::all();
        $purchases = Purchase::all();
        return view('admin_panel.vendors.vendor_bilties', compact('bilties', 'vendors', 'purchases'));
    }

    /**
     * Store vendor bilty information.
     */
    public function store_vendor_bilty(Request $request)
    {
        $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'purchase_id' => 'nullable|exists:purchases,id',
            'bilty_no' => 'nullable|string',
            'vehicle_no' => 'nullable|string',
            'transporter_name' => 'nullable|string',
            'delivery_date' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        VendorBilty::create($request->all());

        return back()->with('success', 'Vendor bilty saved successfully.');
    }

    /**
     * Get vendor balance by vendor id.
     */
    public function getVendorBalance($id)
    {
        $ledger = VendorLedger::where('vendor_id', $id)->first();
        return response()->json([
            'closing_balance' => $ledger ? $ledger->closing_balance : 0
        ]);
    }

    /**
     * Show vendor ledger (journal-based).
     */
    public function ledger($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $startDate = request('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = request('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $balanceService = app(\App\Services\BalanceService::class);
        $ledgerData = $balanceService->getVendorLedger($vendorId, $startDate, $endDate);
        
        return view('admin_panel.vendors.ledger', $ledgerData);
    }

    /**
     * Get vendor ledger as JSON (AJAX).
     */
    public function getVendorLedgerJson($vendorId)
    {
        $vendor = Vendor::findOrFail($vendorId);
        $balanceService = app(\App\Services\BalanceService::class);
        
        $startDate = request('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = request('end_date', now()->format('Y-m-d'));
        
        $ledgerData = $balanceService->getVendorLedger($vendorId, $startDate, $endDate);
        $ledgerData['current_balance'] = $balanceService->getVendorBalance($vendorId);
        
        return response()->json($ledgerData);
    }
}
