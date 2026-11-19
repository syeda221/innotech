<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'decimal:2',
        'discount'      => 'decimal:2',
        'additional_discount' => 'decimal:2',
        'extra_cost'    => 'decimal:2',
        'net_amount'    => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'due_amount'    => 'decimal:2',
    ];

    public function branch()   { return $this->belongsTo(Branch::class); }
    public function warehouse(){ return $this->belongsTo(Warehouse::class); }
    public function vendor()   { return $this->belongsTo(Vendor::class, 'vendor_id'); }
    public function items()    { return $this->hasMany(PurchaseItem::class); }
    public function returns()  { return $this->hasMany(PurchaseReturn::class); }
    public function invoiceAllocations() { return $this->hasMany(VoucherInvoiceAllocation::class, 'purchase_id'); }

    /**
     * Get list of all available purchase invoice series.
     */
    public static function getAvailableSeries(): array
    {
        $customList = Setting::get('purchase_invoice_series_list');
        if (is_string($customList)) {
            $decoded = json_decode($customList, true);
            if (is_array($decoded)) {
                $customList = $decoded;
            }
        }
        if (!is_array($customList) || empty($customList)) {
            $customList = [
                ['prefix' => 'GRN', 'padding' => 3, 'name' => 'GRN', 'is_default' => 1],
                ['prefix' => 'PUR', 'padding' => 4, 'name' => 'PUR', 'is_default' => 0],
            ];
        } else {
            // Replace any INV with PUR per user request (PUR denotes Purchase)
            $hasPur = false;
            foreach ($customList as &$item) {
                if (strtoupper($item['prefix'] ?? '') === 'INV') {
                    $item['prefix'] = 'PUR';
                    $item['name'] = 'PUR';
                }
                if (strtoupper($item['prefix'] ?? '') === 'PUR') {
                    $hasPur = true;
                }
            }
            if (!$hasPur) {
                $customList[] = ['prefix' => 'PUR', 'padding' => 4, 'name' => 'PUR', 'is_default' => 0];
            }
        }
        return $customList;
    }

    /**
     * Generate the next formatted invoice number for a given prefix in purchases table.
     */
    public static function generateInvoiceNo($prefix = null): string
    {
        $seriesList = self::getAvailableSeries();
        $defaultPrefix = 'GRN';
        foreach ($seriesList as $s) {
            if (!empty($s['is_default'])) {
                $defaultPrefix = strtoupper(trim($s['prefix']));
                break;
            }
        }

        $prefix = strtoupper(trim($prefix ?: $defaultPrefix));

        // Determine padding
        $padding = ($prefix === 'GRN') ? 3 : 4;
        foreach ($seriesList as $s) {
            if (strtoupper($s['prefix'] ?? '') === $prefix) {
                if (!empty($s['padding'])) {
                    $padding = (int) $s['padding'];
                }
                break;
            }
        }

        // Find highest existing invoice number in purchases table for this prefix
        $purchases = self::withTrashed()
            ->where('invoice_no', 'LIKE', $prefix . '-%')
            ->orderBy('id', 'desc')
            ->take(100)
            ->get(['invoice_no']);

        $highestNum = 0;
        foreach ($purchases as $p) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/i', trim($p->invoice_no), $m)) {
                $n = (int) $m[1];
                if ($n > $highestNum) {
                    $highestNum = $n;
                }
            }
        }

        $manualNext = (int) Setting::get('purchase_series_next_' . strtolower($prefix), 1);
        $nextNum = max($manualNext, $highestNum + 1);

        // Ensure uniqueness
        do {
            $candidate = $prefix . '-' . str_pad($nextNum, $padding, '0', STR_PAD_LEFT);
            $exists = self::withTrashed()->where('invoice_no', $candidate)->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $candidate;
    }

    /**
     * Increment series counter after purchase creation
     */
    public static function incrementCounterForInvoice($invoiceNo): void
    {
        if (empty($invoiceNo)) return;

        if (preg_match('/^([A-Z0-9]+)-(\d+)$/i', trim($invoiceNo), $m)) {
            $prefix = strtoupper($m[1]);
            $num = (int) $m[2];

            $current = (int) Setting::get('purchase_series_next_' . strtolower($prefix), 1);
            if ($num >= $current) {
                Setting::set('purchase_series_next_' . strtolower($prefix), $num + 1, 'purchase', "Next Number for {$prefix} Purchase Series", 'integer');
            }
        }
    }
}
