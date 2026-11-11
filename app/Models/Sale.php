<?php

// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $casts = ['selected_terms' => 'array'];
    protected $fillable = [
        'customer_id', 'sales_officer_id', 'reference', 'po_no', 'po_date', 'total_amount_Words', 'total_bill_amount',
        'total_extradiscount', 'total_net', 'paid_amount', 'due_amount', 'cash', 'card', 'change', 'change_account_id',
        'total_items', 'discount_type', 'sale_status', 'invoice_no', 'is_booking',
        'freight_charges', 'freight_type', 'selected_terms',
        'total_gross', 'total_exclusive_gst', 'total_sales_tax', 'total_further_tax', 'total_bonus_qty',
        'source'
    ];

    public function salesOfficer()
    {
        return $this->belongsTo(SalesOfficer::class, 'sales_officer_id', 'id');
    }

    public function customer_relation()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function change_account()
    {
        return $this->belongsTo(Account::class, 'change_account_id', 'id');
    }

    public function product_relation()
    {
        return $this->belongsTo(Product::class, 'product', 'id');
    }

    public function getIsWalkinAttribute()
    {
        return empty($this->customer_id) || !empty($this->walkin_name);
    }

    public function getIsPosAttribute(): bool
    {
        return ($this->source === 'pos');
    }

    public static function generateInvoiceNo($prefix = null)
    {
        return \App\Models\InvoiceSeries::generateNextNo($prefix);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function journalEntries()
    {
        return $this->morphMany(JournalEntry::class, 'source');
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class, 'sale_id');
    }

    public function invoiceAllocations()
    {
        return $this->hasMany(VoucherInvoiceAllocation::class, 'sale_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!isset($model->is_synced)) {
                $model->is_synced = 0;
            }
        });

        static::saving(function ($model) {
            if ($model->paid_amount === null) {
                $model->paid_amount = (float) (($model->cash ?? 0) + ($model->card ?? 0));
            }
            if ($model->due_amount === null) {
                $model->due_amount = max(0, (float) ($model->total_net ?? 0) - (float) $model->paid_amount);
            }
        });
    }
}
