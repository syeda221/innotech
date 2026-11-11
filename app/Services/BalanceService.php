<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\VoucherDetail;
use App\Models\VoucherMaster;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    /**
     * Get account (Cash/Bank/any GL account) balance
     * Positive = net Dr balance, Negative = net Cr balance
     */
    public function getAccountBalance(int $accountId): float
    {
        $account = Account::find($accountId);
        if (!$account) {
            return 0.0;
        }

        if ($account->current_balance !== null) {
            return (float) $account->current_balance;
        }

        $opening = (float) ($account->opening_balance ?? 0);
        $type = strtolower((string) ($account->type ?? 'debit'));
        $journalDiff = (float) (JournalEntry::where('account_id', $accountId)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
            ->value('balance') ?? 0);

        if ($type === 'credit') {
            return -$opening + $journalDiff;
        }

        return $opening + $journalDiff;
    }

    /**
     * Get customer balance from journal entries
     * Positive = Customer owes money (Dr)
     * Negative = Customer has advance/credit (Cr)
     */
    public function getCustomerBalance($customer): float
    {
        if (!($customer instanceof Customer)) {
            $customer = Customer::find($customer);
        }

        if (! $customer) {
            return 0;
        }

        $this->ensureCustomerOpeningBalance($customer);

        // Sum of all journal entries for this customer (includes Opening Balance journal entry)
        $journalBalance = JournalEntry::where('party_type', Customer::class)
            ->where('party_id', $customer->id)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
            ->value('balance') ?? 0;

        return (float) $journalBalance;
    }

    /**
     * Ensure Opening Balance Journal Entry and CustomerLedger exist if customer has opening_balance > 0.
     */
    public function ensureCustomerOpeningBalance(Customer $customer): void
    {
        $opening = (float) ($customer->opening_balance ?? 0);
        if ($opening <= 0) {
            return;
        }

        $hasObJournal = JournalEntry::where(function ($q) use ($customer) {
            $q->where('source_type', Customer::class)->where('source_id', $customer->id)
              ->orWhere(function ($sq) use ($customer) {
                  $sq->where('party_type', Customer::class)->where('party_id', $customer->id);
              });
        })
        ->where(function ($q) {
            $q->where('description', 'Opening Balance')
              ->orWhere('description', 'LIKE', 'Opening Balance%');
        })
        ->exists();

        if (! $hasObJournal) {
            try {
                $journalService = app(JournalEntryService::class);
                $arId = $this->getAccountsReceivableId();
                $entryDate = $customer->created_at ? $customer->created_at->format('Y-m-d') : now()->format('Y-m-d');

                $journalService->recordEntry(
                    $customer,
                    $arId,
                    $opening,
                    0,
                    'Opening Balance',
                    $entryDate,
                    $customer
                );

                \App\Models\CustomerLedger::firstOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'previous_balance' => 0,
                        'opening_balance' => $opening,
                    ],
                    [
                        'admin_or_user_id' => auth()->id() ?? 1,
                        'closing_balance' => $opening,
                        'description' => 'Opening Balance',
                        'created_at' => $customer->created_at ?? now(),
                    ]
                );
            } catch (\Exception $e) {
                \Log::error('ensureCustomerOpeningBalance error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get customer balance before a specific date
     */
    public function getCustomerBalanceBeforeDate(int $customerId, string $date): float
    {
        $journalBalance = JournalEntry::where('party_type', Customer::class)
            ->where('party_id', $customerId)
            ->where('entry_date', '<', $date)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
            ->value('balance') ?? 0;

        return (float) $journalBalance;
    }

    /**
     * Get customer ledger entries for a date range
     */
    public function getCustomerLedger(int $customerId, string $startDate, string $endDate): array
    {
        $customer = Customer::find($customerId);
        if (! $customer) {
            return [
                'customer' => null,
                'opening_balance' => 0,
                'transactions' => [],
            ];
        }

        $this->ensureCustomerOpeningBalance($customer);

        // Get opening balance (balance before start date)
        $openingBalance = $this->getCustomerBalanceBeforeDate($customerId, $startDate);

        // Get journal entries in range
        $entries = JournalEntry::where('party_type', Customer::class)
            ->where('party_id', $customerId)
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balance
        $runningBalance = $openingBalance;
        $transactions = $entries->map(function ($entry) use (&$runningBalance) {
            $runningBalance += ($entry->debit - $entry->credit);

            $allocData = $this->getVoucherAllocationsData($entry->source_type, $entry->source_id);

            return [
                'id' => $entry->id,
                'date' => $entry->entry_date,
                'description' => $entry->description,
                'debit' => $entry->debit,
                'credit' => $entry->credit,
                'balance' => $runningBalance,
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
                'alloc_data' => $allocData,
            ];
        });

        return [
            'customer' => $customer,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'transactions' => $transactions,
        ];
    }

    /**
     * Get vendor balance directly from purchases & payments
     * Positive = We owe vendor (Payable)
     * Logic: Opening + Purchases - Payments - Purchase Returns
     */
    public function getVendorBalance(int $vendorId): float
    {
        $vendor = \App\Models\Vendor::find($vendorId);
        if ($vendor) {
            $this->ensureVendorOpeningBalance($vendor);
        }

        $apId = $this->getAccountsPayableId();
        
        $balance = JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('party_id', $vendorId)
            ->where('account_id', $apId)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->value('balance') ?? 0;

        return (float) $balance;
    }

    /**
     * Ensure Opening Balance Journal Entry and VendorLedger exist if vendor has opening_balance > 0.
     */
    public function ensureVendorOpeningBalance(\App\Models\Vendor $vendor): void
    {
        $opening = (float) ($vendor->opening_balance ?? 0);
        if ($opening <= 0) {
            return;
        }

        $hasObJournal = JournalEntry::where(function ($q) use ($vendor) {
            $q->where('source_type', \App\Models\Vendor::class)->where('source_id', $vendor->id)
              ->orWhere(function ($sq) use ($vendor) {
                  $sq->where('party_type', \App\Models\Vendor::class)->where('party_id', $vendor->id);
              });
        })
        ->where(function ($q) {
            $q->where('description', 'Opening Balance')
              ->orWhere('description', 'LIKE', 'Opening Balance%');
        })
        ->exists();

        if (! $hasObJournal) {
            try {
                $journalService = app(JournalEntryService::class);
                $apId = $this->getAccountsPayableId();
                $entryDate = $vendor->created_at ? $vendor->created_at->format('Y-m-d') : now()->format('Y-m-d');

                $journalService->recordEntry(
                    $vendor,
                    $apId,
                    0,
                    $opening,
                    'Opening Balance',
                    $entryDate,
                    $vendor
                );

                \App\Models\VendorLedger::firstOrCreate(
                    [
                        'vendor_id' => $vendor->id,
                        'previous_balance' => 0,
                        'opening_balance' => $opening,
                    ],
                    [
                        'admin_or_user_id' => auth()->id() ?? 1,
                        'closing_balance' => $opening,
                        'description' => 'Opening Balance',
                        'created_at' => $vendor->created_at ?? now(),
                    ]
                );
            } catch (\Exception $e) {
                \Log::error('ensureVendorOpeningBalance error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Get vendor balance before a specific date
     */
    public function getVendorBalanceBeforeDate(int $vendorId, string $date): float
    {
        $vendor = \App\Models\Vendor::find($vendorId);
        if ($vendor) {
            $this->ensureVendorOpeningBalance($vendor);
        }

        $apId = $this->getAccountsPayableId();

        $balance = JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('party_id', $vendorId)
            ->where('account_id', $apId)
            ->where('entry_date', '<', $date)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->value('balance') ?? 0;

        return (float) $balance;
    }

    /**
     * Get Financial Summary for Dashboard
     */
    public function getFinancialSummary(string $startDate, string $endDate): array
    {
        // 1. Sales Revenue (Credit entries in Sales Account)
        // Assuming Sales Account ID is 4 (Standard) or fetch by code
        $salesHeadId = 4; // Income
        $sales = JournalEntry::whereHas('account', function ($q) use ($salesHeadId) {
            $q->where('head_id', $salesHeadId);
        })
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->sum('credit');

        // 2. Purchase Expense (Debit entries in Expense Account)
        $expenseHeadId = 3; // Expense
        $purchases = JournalEntry::whereHas('account', function ($q) use ($expenseHeadId) {
            $q->where('head_id', $expenseHeadId);
        })
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->sum('debit');

        // 3. Total Receivables (Money people owe us)
        $receivables = \App\Models\CustomerLedger::sum('closing_balance'); // Use legacy for now or calculate from journals

        // 4. Total Payables (Money we owe vendors)
        // Calculate from Journal Entries since we just implemented it
        $payables = JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->selectRaw('SUM(credit) - SUM(debit) as balance')
            ->value('balance') ?? 0;

        return [
            'sales' => $sales,
            'purchases' => $purchases,
            'receivables' => $receivables,
            'payables' => $payables,
            'net_cash_flow' => $sales - $purchases, // Rough estimate
        ];
    }

    /**
     * Get vendor ledger entries for a date range
     * Builds from purchases + payment journal entries directly
     */
    public function getVendorLedger(int $vendorId, string $startDate, string $endDate): array
    {
        $vendor = \App\Models\Vendor::find($vendorId);
        if (! $vendor) {
            return ['vendor' => null, 'opening_balance' => 0, 'transactions' => []];
        }

        $openingBalance = $this->getVendorBalanceBeforeDate($vendorId, $startDate);

        // Purchases in range (Increase payable - they go in Debit column = money we owe)
        $purchases = DB::table('purchases')
            ->where('vendor_id', $vendorId)
            ->whereIn('status_purchase', ['approved', 'Returned'])
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->select('id', 'invoice_no', 'purchase_order_no', 'net_amount', 'purchase_date')
            ->get()
            ->map(fn($p) => [
                'source_type'       => 'Purchase',
                'source_id'         => $p->id,
                'date'              => $p->purchase_date,
                'invoice_no'        => $p->invoice_no,
                'vendor_invoice_no' => $p->purchase_order_no,
                'description'       => 'Purchase Invoice #' . $p->invoice_no . ($p->purchase_order_no ? ' (Vendor Inv#: ' . $p->purchase_order_no . ')' : ''),
                'debit'             => 0,
                'credit'            => (float) $p->net_amount, // Cr = we owe vendor more
                'sort_date'         => $p->purchase_date,
            ]);

        // Purchase Returns in range (Reduce payable)
        $returns = DB::table('purchase_returns')
            ->where('vendor_id', $vendorId)
            ->whereBetween('return_date', [$startDate, $endDate])
            ->select('id', 'return_invoice', 'net_amount', 'return_date')
            ->get()
            ->map(fn($r) => [
                'source_type'       => 'PurchaseReturn',
                'source_id'         => $r->id,
                'date'              => $r->return_date,
                'invoice_no'        => $r->return_invoice,
                'vendor_invoice_no' => null,
                'description'       => 'Purchase Return #' . $r->return_invoice,
                'debit'             => (float) $r->net_amount, // Dr = reduces what we owe
                'credit'            => 0,
                'sort_date'         => $r->return_date,
            ]);

        // Payments in range: AP debit journal entries against this vendor (excluding duplicate purchase/return voucher entries)
        $apId = $this->getAccountsPayableId();
        $payments = JournalEntry::where('party_type', \App\Models\Vendor::class)
            ->where('party_id', $vendorId)
            ->where('account_id', $apId)
            ->whereNot('description', 'like', 'Payable to Vendor%')
            ->whereNot('description', 'like', 'Debit Note for Return%')
            ->whereBetween('entry_date', [$startDate, $endDate])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get()
            ->map(fn($e) => [
                'source_type'       => $e->source_type,
                'source_id'         => $e->source_id,
                'date'              => $e->entry_date,
                'invoice_no'        => null,
                'vendor_invoice_no' => null,
                'description'       => $e->description,
                'debit'             => (float) $e->debit,   // Dr = reduces what we owe
                'credit'            => (float) $e->credit,  // Cr = increases what we owe (e.g. refunds/adjustments)
                'sort_date'         => $e->entry_date,
            ]);

        // Merge & sort
        $all = collect([])
            ->merge($purchases)
            ->merge($returns)
            ->merge($payments)
            ->sortBy('sort_date')
            ->values();

        $runningBalance = $openingBalance;
        $transactions = $all->map(function ($row) use (&$runningBalance) {
            // Credit = payable increases | Debit = payable decreases
            $runningBalance += $row['credit'] - $row['debit'];
            return array_merge($row, ['balance' => $runningBalance]);
        });

        return [
            'vendor'          => $vendor,
            'opening_balance' => $openingBalance,
            'closing_balance' => $runningBalance,
            'transactions'    => $transactions,
        ];
    }

    /**
     * Get Combined Consolidated Ledger for a Party that operates as BOTH Customer and Vendor.
     * Accounting Convention (Net Balance):
     * - Debit (Dr) = Sale / Debit Note / Payment to them -> We are owed / Receivable increases
     * - Credit (Cr) = Purchase / Receipt / Return -> We owe them / Payable increases
     * - Net Balance > 0: "Lene hain" (Receivable from Party)
     * - Net Balance < 0: "Dene hain" (Payable to Party)
     */
    public function getCombinedPartyLedger(?int $customerId, ?int $vendorId, string $startDate, string $endDate): array
    {
        $customer = $customerId ? \App\Models\Customer::find($customerId) : null;
        $vendor = $vendorId ? \App\Models\Vendor::find($vendorId) : null;

        if (!$customer && $vendor && $vendor->linked_customer_id) {
            $customer = \App\Models\Customer::find($vendor->linked_customer_id);
            $customerId = $customer ? $customer->id : null;
        }
        if (!$vendor && $customer && $customer->linked_vendor_id) {
            $vendor = \App\Models\Vendor::find($customer->linked_vendor_id);
            $vendorId = $vendor ? $vendor->id : null;
        }

        // Opening Balance calculation before start date
        // Customer opening (Positive = Receivable / Dr)
        $custOpening = $customerId ? $this->getCustomerBalanceBeforeDate($customerId, $startDate) : 0;
        // Vendor opening (Positive in getVendorBalance = Payable / Cr, so subtract to net)
        $vendOpening = $vendorId ? $this->getVendorBalanceBeforeDate($vendorId, $startDate) : 0;
        $netOpeningBalance = $custOpening - $vendOpening; // Net > 0 means Receivable (Dr)

        $allRows = collect([]);

        // 1. Customer Transactions (Sales, Returns, Receipts)
        if ($customerId) {
            $custEntries = JournalEntry::where('party_type', Customer::class)
                ->where('party_id', $customerId)
                ->whereBetween('entry_date', [$startDate, $endDate])
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get();

            foreach ($custEntries as $entry) {
                $desc = '[Sale/Cust] ' . $entry->description;
                $refNo = $entry->source_type ? (class_basename($entry->source_type) . ' #' . $entry->source_id) : '-';
                $allocData = $this->getVoucherAllocationsData($entry->source_type, $entry->source_id);
                if (!empty($allocData['summary']) && !str_contains($desc, 'Against ')) {
                    $desc .= ' ' . $allocData['summary'];
                }
                if (!empty($allocData['ref_text'])) {
                    $refNo = $allocData['ref_text'];
                }

                $allRows->push([
                    'date'        => $entry->entry_date,
                    'type'        => 'Customer',
                    'ref_no'      => $refNo,
                    'description' => $desc,
                    'debit'       => (float) $entry->debit,   // Dr = Sale (Receivable increases)
                    'credit'      => (float) $entry->credit,  // Cr = Receipt/Return (Receivable decreases)
                    'sort_date'   => $entry->entry_date,
                    'sort_id'     => $entry->id,
                ]);
            }
        }

        // 2. Vendor Transactions (Purchases, Returns, Payments)
        if ($vendorId) {
            $vendLedger = $this->getVendorLedger($vendorId, $startDate, $endDate);
            foreach ($vendLedger['transactions'] as $tx) {
                // In Vendor ledger:
                // tx['credit'] = Purchase (Payable increases) -> In net combined view, this is Credit (Cr)
                // tx['debit']  = Payment/Return (Payable decreases) -> In net combined view, this is Debit (Dr)
                $desc = '[Purchase/Vend] ' . $tx['description'];
                $refNo = $tx['invoice_no'] ?: ($tx['source_type'] ? ($tx['source_type'] . ' #' . $tx['source_id']) : '-');
                if (!empty($tx['source_type']) && !empty($tx['source_id'])) {
                    $allocData = $this->getVoucherAllocationsData($tx['source_type'], $tx['source_id']);
                    if (!empty($allocData['summary']) && !str_contains($desc, 'Against ')) {
                        $desc .= ' ' . $allocData['summary'];
                    }
                    if (!empty($allocData['ref_text'])) {
                        $refNo = $allocData['ref_text'];
                    }
                }

                $allRows->push([
                    'date'        => $tx['date'],
                    'type'        => 'Vendor',
                    'ref_no'      => $refNo,
                    'description' => $desc,
                    'debit'       => (float) $tx['debit'],  // Dr = Payment to vendor or Purch Return (We reduce what we owe)
                    'credit'      => (float) $tx['credit'], // Cr = Purchase (We owe them)
                    'sort_date'   => $tx['date'],
                    'sort_id'     => $tx['source_id'] ?? 0,
                ]);
            }
        }

        // Sort all chronologically
        $sorted = $allRows->sortBy([
            ['sort_date', 'asc'],
            ['sort_id', 'asc']
        ])->values();

        $running = $netOpeningBalance;
        $transactions = $sorted->map(function ($row) use (&$running) {
            // Net balance: Dr increases receivable, Cr increases payable
            $running += ($row['debit'] - $row['credit']);
            $statusText = $running >= 0 ? 'Lene hain (Dr / Receivable)' : 'Dene hain (Cr / Payable)';
            return array_merge($row, [
                'balance' => $running,
                'status_label' => $statusText,
                'abs_balance' => abs($running)
            ]);
        });

        return [
            'customer'          => $customer,
            'vendor'            => $vendor,
            'opening_balance'   => $netOpeningBalance,
            'closing_balance'   => $running,
            'closing_status'    => $running >= 0 ? 'Lene hain (Dr)' : 'Dene hain (Cr)',
            'transactions'      => $transactions,
        ];
    }

    /**
     * Create a Receipt Voucher using VoucherMaster + JournalEntry
     */
    public function createReceiptVoucher(
        Customer $customer,
        float $amount,
        int $cashAccountId,
        string $date,
        ?string $description = null,
        $source = null
    ): VoucherMaster {
        return DB::transaction(function () use ($customer, $amount, $cashAccountId, $date, $description) {

            // 1. Generate voucher number
            $voucherNo = $this->generateVoucherNo('receipt');

            // 2. Create VoucherMaster
            $voucher = VoucherMaster::create([
                'voucher_type' => VoucherMaster::TYPE_RECEIPT,
                'voucher_no' => $voucherNo,
                'date' => $date,
                'party_type' => Customer::class,
                'party_id' => $customer->id,
                'total_amount' => $amount,
                'remarks' => $description ?? "Receipt from {$customer->customer_name}",
                'status' => VoucherMaster::STATUS_POSTED,
                'created_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            // 3. Create VoucherDetails (Dr Cash, Cr Receivable)
            $receivableAccountId = $this->getAccountsReceivableId();

            // Debit Cash/Bank
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $cashAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => 'Cash/Bank received',
            ]);

            // Credit Accounts Receivable
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $receivableAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => 'Customer payment received',
            ]);

            // 4. Create Journal Entries
            $journalService = app(JournalEntryService::class);

            // Dr Cash
            $journalService->recordEntry(
                $voucher,
                $cashAccountId,
                $amount,
                0,
                $description ?? "Receipt #{$voucherNo}",
                $date
            );

            // Cr Receivable (with Customer party)
            $journalService->recordEntry(
                $voucher,
                $receivableAccountId,
                0,
                $amount,
                $description ?? "Receipt #{$voucherNo}",
                $date,
                $customer
            );

            return $voucher;
        });
    }

    /**
     * Create a Sale Invoice Voucher
     */
    public function createSaleVoucher(
        Customer $customer,
        float $amount,
        string $invoiceNo,
        string $date
    ): VoucherMaster {
        return DB::transaction(function () use ($customer, $amount, $invoiceNo, $date) {

            $voucherNo = $this->generateVoucherNo('journal');

            $voucher = VoucherMaster::create([
                'voucher_type' => VoucherMaster::TYPE_JOURNAL,
                'voucher_no' => $voucherNo,
                'date' => $date,
                'party_type' => Customer::class,
                'party_id' => $customer->id,
                'total_amount' => $amount,
                'remarks' => "Sale Invoice #{$invoiceNo}",
                'status' => VoucherMaster::STATUS_POSTED,
                'created_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            $receivableAccountId = $this->getAccountsReceivableId();
            $salesAccountId = $this->getSalesRevenueId();

            // Dr Receivable
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $receivableAccountId,
                'debit' => $amount,
                'credit' => 0,
                'narration' => "Sale Invoice #{$invoiceNo}",
            ]);

            // Cr Sales Revenue
            VoucherDetail::create([
                'voucher_master_id' => $voucher->id,
                'account_id' => $salesAccountId,
                'debit' => 0,
                'credit' => $amount,
                'narration' => "Sale Invoice #{$invoiceNo}",
            ]);

            // Journal Entries
            $journalService = app(JournalEntryService::class);

            // Dr Receivable with customer party
            $journalService->recordEntry(
                $voucher,
                $receivableAccountId,
                $amount,
                0,
                "Sale Invoice #{$invoiceNo}",
                $date,
                $customer
            );

            // Cr Sales
            $journalService->recordEntry(
                $voucher,
                $salesAccountId,
                0,
                $amount,
                "Sale Invoice #{$invoiceNo}",
                $date
            );

            return $voucher;
        });
    }

    /**
     * Generate unique voucher number
     */
    private function generateVoucherNo(string $type): string
    {
        $prefix = match ($type) {
            'receipt' => 'RV',
            'payment' => 'PV',
            'expense' => 'EV',
            'journal' => 'JV',
            default => 'V',
        };

        $year = date('Y');
        $lastVoucher = VoucherMaster::where('voucher_type', $type)
            ->where('voucher_no', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastVoucher) {
            $lastNum = (int) substr($lastVoucher->voucher_no, -4);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return "{$prefix}-{$year}-".str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get Accounts Receivable account ID
     */
    public function getAccountsReceivableId(): int
    {
        $account = Account::where('title', 'like', '%Receivable%')
            ->orWhere('account_code', 'AR')
            ->first();

        if (! $account) {
            $account = Account::create([
                'title' => 'Accounts Receivable',
                'account_code' => 'AR',
                'type' => 'Debit',
                'head_id' => null,
                'opening_balance' => 0,
                'status' => 1,
            ]);
        }

        return $account->id;
    }

    /**
     * Get Sales Revenue account ID
     */
    public function getSalesRevenueId(): int
    {
        $account = Account::where('title', 'like', '%Sales%')
            ->orWhere('account_code', 'SALES')
            ->first();

        if (! $account) {
            $account = Account::create([
                'title' => 'Sales Revenue',
                'account_code' => 'SALES',
                'type' => 'Credit', // Income is Credit nature
                'head_id' => null,
                'opening_balance' => 0,
                'status' => 1,
            ]);
        }

        return $account->id;
    }

    /**
     * Get Cash account ID
     */
    public function getCashAccountId(): int
    {
        $account = Account::where('title', 'like', '%Cash%')
            ->orWhere('account_code', 'CASH')
            ->first();

        if (! $account) {
            $account = Account::create([
                'title' => 'Cash Account',
                'account_code' => 'CASH',
                'type' => 'Debit', // Asset is Debit nature
                'head_id' => null,
                'opening_balance' => 0,
                'status' => 1,
            ]);
        }

        return $account->id;
    }

    /**
     * Get Accounts Payable account ID (Liability)
     * Auto-creates if missing.
     */
    public function getAccountsPayableId(): int
    {
        $account = Account::where('title', 'like', '%Payable%')
            ->orWhere('account_code', 'AP')
            ->first();

        if (! $account) {
            \Log::info("BalanceService: 'Accounts Payable' missing, creating it.");
            // Ideally should find a Liability Head, but for now create without head or default
            $account = Account::create([
                'title' => 'Accounts Payable',
                'account_code' => 'AP',
                'type' => 'Credit', // Liability is Cr nature
                'head_id' => null, // Or look for Liability head
                'opening_balance' => 0,
                'status' => 1,
                'is_active' => 1,
            ]);
        }

        return $account->id;
    }

    /**
     * Get Purchase Expense account ID (Expense)
     * Auto-creates if missing.
     */
    public function getPurchaseExpenseId(): int
    {
        $account = Account::where('title', 'like', '%Purchase%')
            ->orWhere('title', 'like', '%Cost of Goods%')
            ->orWhere('account_code', 'PURCHASE')
            ->orWhere('account_code', 'COGS')
            ->first();

        if (! $account) {
            \Log::info("BalanceService: 'Purchase Expense' missing, creating it.");
            $account = Account::create([
                'title' => 'Purchase Expense',
                'account_code' => 'PURCHASE',
                'type' => 'Debit', // Expense is Dr nature
                'head_id' => null, // Or look for Expense head
                'opening_balance' => 0,
                'status' => 1,
                'is_active' => 1,
            ]);
        }

        return $account->id;
    }

    /**
     * Get allocations and formatted summary for a voucher source (Receipt / Payment)
     */
    public function getVoucherAllocationsData($sourceType, $sourceId): array
    {
        $data = [
            'has_allocations' => false,
            'summary' => '',
            'invoices' => [],
            'ref_text' => null,
            'first_url' => null,
            'badges_html' => '',
        ];

        if (empty($sourceType) || empty($sourceId)) {
            return $data;
        }

        try {
            $allocs = collect();
            if ($sourceType === \App\Models\VoucherMaster::class || $sourceType === 'VoucherMaster' || $sourceType === 'App\Models\VoucherMaster') {
                $allocs = \App\Models\VoucherInvoiceAllocation::where('voucher_master_id', $sourceId)->get();
                if ($allocs->isEmpty()) {
                    $vm = \App\Models\VoucherMaster::find($sourceId);
                    if ($vm && preg_match('/Ref:\s*(RV|PV)-(\d+)/i', $vm->remarks ?? '', $m)) {
                        $refId = (int)$m[2];
                        if (strtoupper($m[1]) === 'RV') {
                            $allocs = \App\Models\VoucherInvoiceAllocation::where('receipt_voucher_id', $refId)->get();
                        } else {
                            $allocs = \App\Models\VoucherInvoiceAllocation::where('payment_voucher_id', $refId)->get();
                        }
                    }
                }
            } elseif ($sourceType === \App\Models\ReceiptVoucher::class || $sourceType === 'ReceiptVoucher' || $sourceType === 'App\Models\ReceiptVoucher' || $sourceType === \App\Models\ReceiptsVoucher::class) {
                $allocs = \App\Models\VoucherInvoiceAllocation::where('receipt_voucher_id', $sourceId)->get();
            } elseif ($sourceType === \App\Models\PaymentVoucher::class || $sourceType === 'PaymentVoucher' || $sourceType === 'App\Models\PaymentVoucher') {
                $allocs = \App\Models\VoucherInvoiceAllocation::where('payment_voucher_id', $sourceId)->get();
            }

            if ($allocs->isNotEmpty()) {
                $summaryParts = [];
                $invNumbers = [];
                $badges = [];

                foreach ($allocs as $al) {
                    $num = $al->invoice_no;
                    if (!$num) {
                        $num = $al->sale_id ? ('INV-' . str_pad($al->sale_id, 4, '0', STR_PAD_LEFT)) : ($al->purchase_id ? ('PUR-' . str_pad($al->purchase_id, 4, '0', STR_PAD_LEFT)) : 'REF');
                    }
                    $invNumbers[] = $num;
                    $summaryParts[] = $num . ': Rs ' . number_format((float)$al->allocated_amount, 2);

                    $url = null;
                    if ($al->sale_id) {
                        $url = route('sales.invoice', $al->sale_id);
                    } elseif ($al->purchase_id) {
                        $url = route('purchase.invoice', $al->purchase_id);
                    }

                    if (!$data['first_url'] && $url) {
                        $data['first_url'] = $url;
                    }

                    $data['invoices'][] = [
                        'invoice_no' => $num,
                        'amount' => (float)$al->allocated_amount,
                        'url' => $url,
                    ];

                    if ($url) {
                        $badges[] = '<a href="' . $url . '" target="_blank" class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace text-decoration-none py-1 px-2 shadow-sm me-1 mb-1 d-inline-block" title="View & Print Invoice" style="cursor: pointer;"><i class="fas fa-file-invoice me-1"></i>' . e($num) . ' <i class="fas fa-external-link-alt ms-1" style="font-size: 8.5px;"></i></a>';
                    } else {
                        $badges[] = '<span class="badge bg-light text-primary border font-monospace me-1 mb-1 d-inline-block">' . e($num) . '</span>';
                    }
                }

                $data['has_allocations'] = true;
                $data['summary'] = '(Against ' . implode(', ', $summaryParts) . ')';
                $data['ref_text'] = implode(', ', $invNumbers);
                $data['badges_html'] = implode(' ', $badges);
            }
        } catch (\Exception $e) {
            \Log::error('getVoucherAllocationsData error: ' . $e->getMessage());
        }

        return $data;
    }

    /**
     * Format balance with Dr/Cr indicator
     */
    public static function formatBalance(float $balance): string
    {
        $formatted = number_format(abs($balance), 2);
        $suffix = $balance >= 0 ? 'Dr' : 'Cr';

        return "{$formatted} {$suffix}";
    }
}
