<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherInvoiceAllocation extends Model
{
    use HasFactory;

    protected $table = 'voucher_invoice_allocations';
    protected $guarded = [];

    protected $casts = [
        'invoice_amount'   => 'decimal:2',
        'previous_due'     => 'decimal:2',
        'allocated_amount' => 'decimal:2',
        'remaining_due'    => 'decimal:2',
        'invoice_date'     => 'date',
    ];

    public function receiptVoucher()
    {
        return $this->belongsTo(ReceiptsVoucher::class, 'receipt_voucher_id');
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    public function voucherMaster()
    {
        return $this->belongsTo(VoucherMaster::class, 'voucher_master_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }
}
