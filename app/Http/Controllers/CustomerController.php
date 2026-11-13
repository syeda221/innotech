<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\Cheque;
use App\Models\JournalEntry;
use App\Models\SalesOfficer;
use App\Models\Zone;
use App\Services\CustomerFieldService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    protected $fieldService;

    public function __construct(CustomerFieldService $fieldService)
    {
        $this->fieldService = $fieldService;
    }

    // 🔹 Load customers list by type (Main Customer returns all types)
    public function saleindex(Request $request)
    {
        $type   = $request->type   ?? 'Main Customer';
        $search = $request->search ?? '';

        $query = Customer::query();

        if ($type && $type !== 'Main Customer' && $type !== 'all') {
            $query->where('customer_type', $type);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_id',   'like', "%{$search}%")
                  ->orWhere('mobile',        'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('customer_name')->get();

        return response()->json($customers);
    }

    // 🔹 Single customer detail
    public function show($id)
    {
        $customer = Customer::with('customFieldValues')->findOrFail($id);

        $data = $customer->toArray();
        $data['previous_balance'] = $customer->previous_balance;
        $data['balance_range'] = $customer->balance_range ?? 0;
        $customValues = $customer->getAllCustomValues();
        $data['custom_fields'] = $customValues;
        $data['ntn_number'] = $customValues['ntn_number'] ?? $customer->ntn_number ?? '';
        $data['strn_number'] = $customValues['strn_number'] ?? $customer->strn_number ?? '';
        $data['cnic'] = $customValues['cnic'] ?? $customer->cnic ?? '';
        $data['filer_type'] = $customValues['filer_type'] ?? $customer->filer_type ?? '';

        // Map status to remarks if needed by frontend
        $data['remarks'] = $customer->status ?? '';

        return response()->json($data);
    }

    // 🔹 Full Dynamic Customer View Details Modal Data
    public function details($id)
    {
        $customer = Customer::with('customFieldValues')->findOrFail($id);
        $viewFields = $this->fieldService->getFieldsForContext('view');

        $fieldData = [];
        foreach ($viewFields as $f) {
            $formattedVal = $this->fieldService->getFieldValue($customer, $f, true);
            $rawVal = $this->fieldService->getFieldValue($customer, $f, false);
            $fieldData[] = [
                'key'         => $f->field_key,
                'label'       => $f->label,
                'type'        => $f->field_type,
                'section'     => $f->section ?: 'General Information',
                'value'       => $rawVal,
                'formatted'   => $formattedVal,
                'is_system'   => $f->is_system,
            ];
        }

        return response()->json([
            'success' => true,
            'customer' => [
                'id'            => $customer->id,
                'customer_id'   => $customer->customer_id,
                'customer_name' => $customer->customer_name,
                'status'        => $customer->status,
                'balance'       => $customer->previous_balance,
            ],
            'fields' => $fieldData,
        ]);
    }

    // //////////

    public function index(Request $request)
    {
        $listFields = $this->fieldService->getFieldsForContext('list');
        $searchFields = $this->fieldService->getFieldsForContext('search');
        $dropdownLookups = $this->fieldService->getDropdownLookupData();

        $query = Customer::with('customFieldValues')->latest();

        // Keyword search
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_id', 'like', "%{$s}%")
                  ->orWhere('mobile', 'like', "%{$s}%")
                  ->orWhere('address', 'like', "%{$s}%");
            });
        }

        // Party Category (Role) Filter
        if ($request->filled('party_type') && $request->party_type !== 'all') {
            if ($request->party_type === 'both') {
                $query->where(function ($q) {
                    $q->where('party_type', 'both')
                      ->orWhereNotNull('linked_vendor_id');
                });
            } else {
                $query->where(function ($q) {
                    $q->where('party_type', 'customer')
                      ->orWhereNull('party_type');
                });
            }
        }

        // Dynamic Field Filters
        foreach ($searchFields as $sf) {
            $k = $sf->field_key;
            if ($request->filled($k)) {
                $val = trim($request->input($k));
                if ($sf->is_system && $sf->system_column) {
                    $query->where($sf->system_column, $val);
                } else {
                    $query->whereHas('customFieldValues', function ($q) use ($sf, $val) {
                        $q->where('field_definition_id', $sf->id)
                          ->where('value', 'like', "%{$val}%");
                    });
                }
            }
        }

        $customers = $query->get();

        return view('admin_panel.customers.index', compact('customers', 'listFields', 'searchFields', 'dropdownLookups'));
    }

    public function toggleStatus($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->save();

        return redirect()->back()->with('success', 'Customer status updated.');
    }

    // Add this in CustomerController
    public function getCustomerLedger($id)
    {
        $ledger = CustomerLedger::where('customer_id', $id)->latest()->first();

        return response()->json([
            'closing_balance' => $ledger->closing_balance,
        ]);
    }

    public function markInactive($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->status = 'inactive';
        $customer->save();

        return redirect()->route('customers.index')->with('success', 'Customer marked as inactive.');
    }

    public function inactiveCustomers()
    {
        $customers = Customer::where('status', 'inactive')->latest()->get();

        return view('admin_panel.customers.inactive', compact('customers'));
    }

    public function create()
    {
        $latestId = 'CUST-'.str_pad(Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $fields = $this->fieldService->getFieldsForContext('create');
        $groupedFields = $this->fieldService->getGroupedFieldsForForm('create');
        $dropdownLookups = $this->fieldService->getDropdownLookupData();

        return view('admin_panel.customers.create', compact('latestId', 'fields', 'groupedFields', 'dropdownLookups'));
    }

    public function store(Request $request)
    {
        // Auto-generate customer_id if empty
        if (empty($request->customer_id)) {
            $latestId = 'CUST-'.str_pad(Customer::max('id') + 1, 4, '0', STR_PAD_LEFT);
            $request->merge(['customer_id' => $latestId]);
        }

        // Auto-default party_type if empty (e.g. from quick modals)
        if (empty($request->party_type)) {
            $request->merge(['party_type' => 'customer']);
        }

        // Auto-default customer_type if empty
        if ($request->customer_type === null || $request->customer_type === '') {
            $request->merge(['customer_type' => 'Main Customer']);
        }

        // Auto-default opening_balance if empty
        if ($request->opening_balance === null || $request->opening_balance === '') {
            $request->merge(['opening_balance' => 0]);
        }

        // Auto-default balance_range (credit limit) if empty
        if ($request->balance_range === null || $request->balance_range === '') {
            $request->merge(['balance_range' => 0]);
        }

        $rules = $this->fieldService->buildValidationRules('create');
        // Ensure License No and Drug License No are strictly optional
        $rules['license_no'] = ['nullable', 'string', 'max:100'];
        $rules['drug_license_no'] = ['nullable', 'string', 'max:100'];
        $data = $request->validate($rules);

        // Create Customer model instance
        $customer = new Customer();
        $this->fieldService->saveCustomerFields($customer, $request->all(), false);

        // Ledger & Journal entry agar opening balance dia gaya ho
        $opening = (float) ($customer->opening_balance ?? 0);
        $this->syncOpeningBalance($customer, $opening);

        // Sync linked Vendor if party_type is both
        $this->syncLinkedVendor($customer, $request->party_type ?? $customer->party_type ?? 'customer');

        if ($request->ajax() || $request->wantsJson()) {
            $customer = $customer->fresh() ?? $customer;
            $customerData = $customer->toArray();
            $customerData['previous_balance'] = (float)$customer->previous_balance;
            $customerData['balance_range'] = (float)($customer->balance_range ?? 0);
            $customValues = $customer->getAllCustomValues();
            $customerData['custom_fields'] = $customValues;
            $customerData['ntn_number'] = $customValues['ntn_number'] ?? $customer->ntn_number ?? '';
            $customerData['strn_number'] = $customValues['strn_number'] ?? $customer->strn_number ?? '';
            $customerData['license_no'] = $customValues['license_no'] ?? $customer->license_no ?? '';
            $customerData['drug_license_no'] = $customValues['drug_license_no'] ?? $customer->drug_license_no ?? '';
            $customerData['cnic'] = $customValues['cnic'] ?? $customer->cnic ?? '';
            $customerData['filer_type'] = $customValues['filer_type'] ?? $customer->filer_type ?? '';
            $customerData['remarks'] = $customer->status ?? '';

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully.',
                'customer' => $customerData,
                'data' => $customerData
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    public function edit($id)
    {
        $customer = Customer::with('customFieldValues')->findOrFail($id);
        $fields = $this->fieldService->getFieldsForContext('edit');
        $groupedFields = $this->fieldService->getGroupedFieldsForForm('edit');
        $dropdownLookups = $this->fieldService->getDropdownLookupData();

        return view('admin_panel.customers.edit', compact('customer', 'fields', 'groupedFields', 'dropdownLookups'));
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::with('customFieldValues')->findOrFail($id);

        $rules = $this->fieldService->buildValidationRules('edit', $id);
        // Ensure License No and Drug License No are strictly optional
        $rules['license_no'] = ['nullable', 'string', 'max:100'];
        $rules['drug_license_no'] = ['nullable', 'string', 'max:100'];
        $data = $request->validate($rules);

        $this->fieldService->saveCustomerFields($customer, $request->all(), true);

        // Sync opening balance in JournalEntry & CustomerLedger
        $opening = (float) ($customer->opening_balance ?? 0);
        $this->syncOpeningBalance($customer, $opening);

        // Sync linked Vendor if party_type is both
        $this->syncLinkedVendor($customer, $request->party_type ?? $customer->party_type ?? 'customer');

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    /**
     * Synchronize Customer with linked Vendor record when party_type is 'both'
     */
    private function syncLinkedVendor(Customer $customer, string $partyType): void
    {
        if ($partyType === 'both') {
            $vendor = null;
            if ($customer->linked_vendor_id) {
                $vendor = \App\Models\Vendor::find($customer->linked_vendor_id);
            }
            if (!$vendor) {
                $vendor = \App\Models\Vendor::where('name', $customer->customer_name)
                    ->orWhere(function($q) use ($customer) {
                        if (!empty($customer->mobile)) $q->where('phone', $customer->mobile);
                    })->first();
            }

            if ($vendor) {
                $vendor->update([
                    'name' => $customer->customer_name,
                    'phone' => $customer->mobile ?: $vendor->phone,
                    'email' => $customer->email_address ?: $vendor->email,
                    'cnic' => $customer->cnic ?: $vendor->cnic,
                    'address' => $customer->address ?: $vendor->address,
                    'province' => $customer->province ?: $vendor->province,
                    'city' => $customer->city ?: $vendor->city,
                    'area' => $customer->area ?: $vendor->area,
                    'zone' => $customer->zone ?: $vendor->zone,
                    'party_type' => 'both',
                    'linked_customer_id' => $customer->id,
                ]);
            } else {
                $vendor = \App\Models\Vendor::create([
                    'name' => $customer->customer_name,
                    'phone' => $customer->mobile,
                    'email' => $customer->email_address,
                    'cnic' => $customer->cnic,
                    'address' => $customer->address,
                    'province' => $customer->province,
                    'city' => $customer->city,
                    'area' => $customer->area,
                    'zone' => $customer->zone,
                    'party_type' => 'both',
                    'linked_customer_id' => $customer->id,
                ]);
            }

            if ($customer->linked_vendor_id !== $vendor->id || $customer->party_type !== 'both') {
                $customer->update([
                    'linked_vendor_id' => $vendor->id,
                    'party_type' => 'both',
                ]);
            }
        } else {
            // Party type is customer only
            if ($customer->linked_vendor_id) {
                $vendor = \App\Models\Vendor::find($customer->linked_vendor_id);
                if ($vendor) {
                    $vendor->update(['party_type' => 'vendor', 'linked_customer_id' => null]);
                }
                $customer->update(['linked_vendor_id' => null, 'party_type' => 'customer']);
            }
        }
    }

    /**
     * Synchronize opening balance in CustomerLedger and JournalEntry.
     */
    public static function syncOpeningBalance(Customer $customer, float $newOpening)
    {
        try {
            $balanceService = app(\App\Services\BalanceService::class);
            $journalService = app(\App\Services\JournalEntryService::class);
            $arId = $balanceService->getAccountsReceivableId();
            $entryDate = $customer->created_at ? $customer->created_at->format('Y-m-d') : now()->format('Y-m-d');

            // 1. Find existing Opening Balance Journal Entry
            $obJournal = JournalEntry::where(function ($q) use ($customer) {
                $q->where('source_type', Customer::class)->where('source_id', $customer->id);
            })
            ->where(function ($q) {
                $q->where('description', 'Opening Balance')
                  ->orWhere('description', 'LIKE', 'Opening Balance%');
            })
            ->first();

            if (!$obJournal) {
                $obJournal = JournalEntry::where('party_type', Customer::class)
                    ->where('party_id', $customer->id)
                    ->where(function ($q) {
                        $q->where('description', 'Opening Balance')
                          ->orWhere('description', 'LIKE', 'Opening Balance%');
                    })
                    ->first();
            }

            if ($newOpening > 0) {
                if ($obJournal) {
                    $diff = $newOpening - (float) $obJournal->debit;
                    if ($diff != 0) {
                        $account = \App\Models\Account::find($obJournal->account_id ?: $arId);
                        if ($account) {
                            $account->current_balance = ($account->current_balance ?? 0) + $diff;
                            $account->save();
                        }
                        $obJournal->debit = $newOpening;
                        $obJournal->credit = 0;
                        $obJournal->account_id = $obJournal->account_id ?: $arId;
                        $obJournal->party_type = Customer::class;
                        $obJournal->party_id = $customer->id;
                        $obJournal->source_type = Customer::class;
                        $obJournal->source_id = $customer->id;
                        $obJournal->save();
                    }
                } else {
                    $journalService->recordEntry(
                        $customer,
                        $arId,
                        $newOpening, // Debit (Asset)
                        0,           // Credit
                        "Opening Balance",
                        $entryDate,
                        $customer
                    );
                }
            } else {
                if ($obJournal) {
                    $account = \App\Models\Account::find($obJournal->account_id);
                    if ($account) {
                        $account->current_balance = ($account->current_balance ?? 0) - (float) $obJournal->debit + (float) $obJournal->credit;
                        $account->save();
                    }
                    $obJournal->delete();
                }
            }

            // 2. Sync CustomerLedger
            $obLedger = CustomerLedger::where('customer_id', $customer->id)
                ->where(function ($q) {
                    $q->where('previous_balance', 0)->where('opening_balance', '>', 0)
                      ->orWhere('description', 'Opening Balance');
                })
                ->first();

            if ($newOpening > 0) {
                if ($obLedger) {
                    $obLedger->update([
                        'opening_balance' => $newOpening,
                        'closing_balance' => $newOpening,
                    ]);
                } else {
                    CustomerLedger::create([
                        'customer_id' => $customer->id,
                        'admin_or_user_id' => Auth::id() ?? 1,
                        'previous_balance' => 0,
                        'opening_balance' => $newOpening,
                        'closing_balance' => $newOpening,
                        'description' => 'Opening Balance',
                        'created_at' => $customer->created_at ?? now(),
                    ]);
                }
            } else {
                if ($obLedger) {
                    $obLedger->delete();
                }
            }
        } catch (\Exception $e) {
            \Log::error("Customer syncOpeningBalance Error: " . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    // customer ledger start

    // Customer Ledger View
    public function customer_ledger(Request $request)
    {
        if (Auth::check()) {
            
            $customers = Customer::all();
            $ledgerData = collect([]);
            $openingBalance = 0;
            $closingBalance = 0;
            
            if ($request->filled('customer_id')) {
                // Use Balance Service for accurate Statement
                $balanceService = app(\App\Services\BalanceService::class);
                
                $startDate = $request->from_date ?? '2000-01-01';
                $endDate = $request->to_date ?? date('Y-m-d');
                
                $data = $balanceService->getCustomerLedger($request->customer_id, $startDate, $endDate);
                
                $openingBalance = $data['opening_balance'];
                $closingBalance = $data['closing_balance'];
                
                // transform for view
                $ledgerData = collect($data['transactions'])->map(function($t) use ($data) {
                    return (object) [
                        'created_at' => \Carbon\Carbon::parse($t['date']),
                        'customer' => $data['customer'],
                        'description' => $t['description'],
                        'debit' => $t['debit'],
                        'credit' => $t['credit'],
                        'closing_balance' => $t['balance'],
                        // We act as if previous balance is calculated, but views usually use these explicitly now
                        'previous_balance' => $t['balance'] - ($t['debit'] - $t['credit']) 
                    ];
                });
                
            }

            return view('admin_panel.customers.customer_ledger', [
                'CustomerLedgers' => $ledgerData,
                'customers' => $customers,
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
            ]);
            
        } else {
            return redirect()->back();
        }
    }
    // customer payment start

    // View all customer payments
    public function customer_payments()
    {
        $payments = CustomerPayment::with(['customer', 'cheque'])->orderByDesc('id')->get();
        $customers = Customer::all();

        return view('admin_panel.customers.customer_payments', compact('payments', 'customers'));
    }

    // Store a customer payment
    public function store_customer_payment(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'adjustment_type' => 'required|in:plus,minus',
            'payment_method' => 'nullable|string',
            'cheque_no' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'cheque_date' => 'nullable|date',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);

        $userId = Auth::id();

        $isCheque = strtolower($request->payment_method ?? '') === 'cheque';
        if ($isCheque) {
            $request->validate([
                'cheque_no' => 'required|string|max:100',
                'bank_name' => 'required|string|max:150',
                'cheque_date' => 'required|date',
            ]);
        }

        // Save the payment
        $payment = CustomerPayment::create([
            'customer_id' => $request->customer_id,
            'admin_or_user_id' => $userId,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'payment_date' => $request->payment_date,
            'note' => $request->note,
        ]);

        if ($isCheque) {
            Cheque::create([
                'cheque_no' => $request->cheque_no,
                'bank_name' => $request->bank_name,
                'cheque_date' => $request->cheque_date,
                'amount' => $request->amount,
                'customer_id' => $request->customer_id,
                'customer_payment_id' => $payment->id,
                'status' => 'in_hand',
                'note' => $request->note,
                'created_by' => $userId,
            ]);
        }

        // Get latest ledger record to calculate new balance
        $latestLedger = CustomerLedger::where('customer_id', $request->customer_id)->latest()->first();

        // Default to opening balance if no ledger exists, or 0
        // If no ledger exists, we assume previous balance is opening balance of customer?
        // But checking 'customers' table again is safer.
        $previousBalance = 0;
        if ($latestLedger) {
            $previousBalance = $latestLedger->closing_balance;
        } else {
            $cust = Customer::find($request->customer_id);
            $previousBalance = $cust->opening_balance ?? 0;
        }

        // Calculate new balance
        $newBalance = $request->adjustment_type === 'plus'
            ? $previousBalance + $request->amount
            : $previousBalance - $request->amount;

        // Create NEW ledger record (Preserve History)
        CustomerLedger::create([
            'customer_id' => $request->customer_id,
            'admin_or_user_id' => $userId,
            'previous_balance' => $previousBalance,
            'opening_balance' => 0, // This is not an "opening" entry, so 0 or null
            'closing_balance' => $newBalance,
            'description' => 'Payment: ' . ($request->payment_method ?? 'Payment') . ($isCheque ? " (Cheque #{$request->cheque_no} - {$request->bank_name})" : '') . ($request->note ? " - {$request->note}" : ''),
        ]);

        // Update customer reminder: If they made a payment, set next reminder to +7 days
        $cust = Customer::find($request->customer_id);
        if ($cust) {
            $updateData = [
                'reminder_snoozed_at' => null // clear snooze
            ];

            if ($newBalance > 0) {
                // Only auto-reschedule if they DON'T use a fixed weekly day
                if (!$cust->reminder_day) {
                    $updateData['payment_reminder_date'] = date('Y-m-d', strtotime('+7 days'));
                }
            } else {
                // Clear dynamic date if balance settled
                $updateData['payment_reminder_date'] = null;
            }

            $cust->update($updateData);
        }

        return back()->with('success', 'Payment adjusted and ledger updated.');
    }

    public function snoozeReminder($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->update([
            'reminder_snoozed_at' => date('Y-m-d'),
            // Re-appear next day means we keep the scheduled date as today or past, 
            // but the snooze logic will prevent it from showing today.
        ]);

        return response()->json(['success' => true]);
    }

    public function getReminders()
    {
        $today = date('Y-m-d');
        $todayDay = date('l'); // Monday, Tuesday, etc.
        
        $customers = Customer::where(function($q) use ($today, $todayDay) {
                $q->where(function($sq) use ($today) {
                    $sq->whereNotNull('payment_reminder_date')
                       ->where('payment_reminder_date', '<=', $today);
                })
                ->orWhere('reminder_day', $todayDay);
            })
            ->where(function($q) use ($today) {
                $q->whereNull('reminder_snoozed_at')
                  ->orWhere('reminder_snoozed_at', '<', $today);
            })
            ->get();

        $customerIds = $customers->pluck('id')->toArray();
        $balances = \App\Models\JournalEntry::where('party_type', Customer::class)
            ->whereIn('party_id', $customerIds)
            ->selectRaw('party_id, COALESCE(SUM(debit) - SUM(credit), 0) as balance')
            ->groupBy('party_id')
            ->pluck('balance', 'party_id');

        $reminders = $customers->map(function($c) use ($balances) {
            $balance = (float) ($balances[$c->id] ?? 0);
            if ($balance > 0) {
                return [
                    'id' => $c->id,
                    'name' => $c->customer_name,
                    'balance' => $balance,
                    'date' => $c->reminder_day ?? $c->payment_reminder_date
                ];
            }
            return null;
        })->filter()->values();

        return response()->json(['reminders' => $reminders]);
    }

    public function destroy_payment($id)
    {
        $payment = CustomerPayment::findOrFail($id);

        $customerId = $payment->customer_id;
        $amount = $payment->amount;

        // Latest ledger record for that customer
        $ledger = CustomerLedger::where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->first();
        if ($ledger) {
            $ledger->closing_balance += $amount;
            $ledger->save();
        }

        // Delete any related cheque
        Cheque::where('customer_payment_id', $payment->id)->delete();

        // Delete the payment entry
        $payment->delete();

        return redirect()->back()->with('success', 'Payment deleted and customer ledger updated successfully.');
    }

    public function getByType(Request $request)
    {
        $type = $request->get('type');

        $customers = Customer::where('customer_type', $type)->get(['id', 'customer_name']);

        return response()->json(['customers' => $customers]);
    }
}
