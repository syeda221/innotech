<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cheque extends Model
{
    use HasFactory;

    protected $fillable = [
        'cheque_no',
        'bank_name',
        'cheque_date',
        'amount',
        'customer_id',
        'customer_payment_id',
        'status', // in_hand, deposited, cleared, bounced
        'deposit_account_id',
        'cleared_at',
        'bounced_at',
        'bounce_reason',
        'note',
        'created_by',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'cleared_at' => 'date',
        'bounced_at' => 'date',
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function payment()
    {
        return $this->belongsTo(CustomerPayment::class, 'customer_payment_id');
    }

    public function depositAccount()
    {
        return $this->belongsTo(Account::class, 'deposit_account_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    // Scopes
    public function scopeInHand($query)
    {
        return $query->where('status', 'in_hand');
    }

    public function scopeCleared($query)
    {
        return $query->where('status', 'cleared');
    }

    public function scopeBounced($query)
    {
        return $query->where('status', 'bounced');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('cheque_date', Carbon::today())
            ->whereIn('status', ['in_hand', 'deposited']);
    }

    public function scopeDueUpcoming($query, $days = 3)
    {
        return $query->whereBetween('cheque_date', [
                Carbon::tomorrow()->toDateString(),
                Carbon::today()->addDays($days)->toDateString(),
            ])
            ->whereIn('status', ['in_hand', 'deposited']);
    }

    public function scopeOverdue($query)
    {
        return $query->whereDate('cheque_date', '<', Carbon::today())
            ->whereIn('status', ['in_hand', 'deposited']);
    }

    // Status Helper
    public function getDueBadgeAttribute()
    {
        if ($this->status === 'cleared') {
            return ['class' => 'bg-success text-white', 'text' => '✓ Cleared in Bank'];
        }
        if ($this->status === 'bounced') {
            return ['class' => 'bg-danger text-white', 'text' => '✗ Bounced'];
        }

        $today = Carbon::today();
        $date = Carbon::parse($this->cheque_date);

        if ($date->isPast() && !$date->isToday()) {
            return ['class' => 'bg-danger text-white', 'text' => '⚠️ Overdue (' . $date->diffForHumans() . ')'];
        } elseif ($date->isToday()) {
            return ['class' => 'bg-warning text-dark fw-bold', 'text' => '🔔 Due Today (Deposit in Bank)'];
        } else {
            $daysLeft = (int) $today->diffInDays($date);
            if ($daysLeft <= 3) {
                return ['class' => 'bg-warning text-dark fw-bold', 'text' => '⏳ Due in ' . $daysLeft . ' Days (Alert)'];
            }
            return ['class' => 'bg-info text-dark', 'text' => '📅 PDC (Due in ' . $daysLeft . ' Days)'];
        }
    }

    /**
     * Reverse any sale payment / invoice allocation tied to this bounced cheque (idempotent).
     */
    public function reverseLinkedSalePayment(): void
    {
        if ($this->status !== 'bounced') {
            return;
        }

        $note = (string) ($this->note ?? '');
        if (str_contains($note, 'Invoice Reversed')) {
            return;
        }

        $remaining = (float) ($this->amount ?? 0);
        if ($remaining <= 0) {
            return;
        }

        $applySaleReversal = function (Sale $sale, float $amount): float {
            $totalNet = (float) ($sale->total_net ?? 0);
            $recordedPaid = (float) ($sale->paid_amount ?? 0);
            $cashCardPaid = min($totalNet, max(0, (float) ($sale->cash ?? 0) + (float) ($sale->card ?? 0)));
            $currentPaid = max($recordedPaid, $cashCardPaid);

            $reverseAmt = min($amount, $currentPaid);
            if ($reverseAmt <= 0) {
                return 0.0;
            }

            $sale->paid_amount = max(0, round($currentPaid - $reverseAmt, 2));
            $sale->cash = max(0, round((float) ($sale->cash ?? 0) - $reverseAmt, 2));
            $sale->due_amount = max(0, round($totalNet - $sale->paid_amount, 2));
            $sale->change = ($sale->cash + (float) ($sale->card ?? 0)) - $totalNet;
            $sale->save();

            return $reverseAmt;
        };

        $adjustedAny = false;

        if (preg_match('/Receipt Voucher\s+([A-Z0-9-]+)/i', $note, $m)) {
            $rvid = trim($m[1]);
            $rv = ReceiptsVoucher::where('rvid', $rvid)->first();
            $vMaster = VoucherMaster::where('voucher_type', VoucherMaster::TYPE_RECEIPT)
                ->where(function ($q) use ($rvid) {
                    $q->where('voucher_no', $rvid)
                      ->orWhere('remarks', 'like', "%{$rvid}%");
                })
                ->first();

            if ($rv || $vMaster) {
                $allocations = VoucherInvoiceAllocation::where(function ($q) use ($rv, $vMaster) {
                    if ($rv) {
                        $q->where('receipt_voucher_id', $rv->id);
                    }
                    if ($vMaster) {
                        $rv ? $q->orWhere('voucher_master_id', $vMaster->id) : $q->where('voucher_master_id', $vMaster->id);
                    }
                })
                ->where('allocated_amount', '>', 0)
                ->orderBy('id', 'desc')
                ->get();

                foreach ($allocations as $alloc) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $take = min($remaining, (float) $alloc->allocated_amount);
                    $sale = $alloc->sale_id ? Sale::find($alloc->sale_id) : null;
                    if ($sale && $take > 0) {
                        $reversed = $applySaleReversal($sale, $take);
                        if ($reversed > 0) {
                            $alloc->allocated_amount = max(0, round((float) $alloc->allocated_amount - $reversed, 2));
                            $alloc->remaining_due = min((float) $sale->total_net, round((float) $alloc->remaining_due + $reversed, 2));
                            $alloc->save();
                            $remaining = max(0, round($remaining - $reversed, 2));
                            $adjustedAny = true;
                        }
                    }
                }
            }
        } elseif (preg_match('/Sale Invoice\s*#([^\s|]+)/i', $note, $m)) {
            $invoiceNo = trim($m[1]);
            $sale = Sale::where('invoice_no', $invoiceNo)->first();
            if ($sale) {
                $reversed = $applySaleReversal($sale, $remaining);
                if ($reversed > 0) {
                    $adjustedAny = true;
                }
            }
        }

        if ($adjustedAny) {
            $this->note = ($note !== '' ? $note . ' | ' : '') . 'Invoice Reversed';
            $this->save();
        }
    }

    /**
     * Reconcile any legacy bounced cheques for a customer that have not yet adjusted linked invoices.
     */
    public static function reconcileUnreversedBouncedForCustomer($customerId): void
    {
        if (empty($customerId)) {
            return;
        }

        $bounced = self::where('customer_id', $customerId)
            ->where('status', 'bounced')
            ->where(function ($q) {
                $q->whereNull('note')
                  ->orWhere('note', 'not like', '%Invoice Reversed%');
            })
            ->get();

        foreach ($bounced as $cheque) {
            $cheque->reverseLinkedSalePayment();
        }
    }
}
