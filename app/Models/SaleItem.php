<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'warehouse_id', 'product_id', 'batch_id', 'batch_no', 'serials',
        'brand_id', 'category_id', 'sub_category_id', 'unit_id',
        'qty', 'price', 'total',
        'discount_percent', 'discount_amount',
        'color', 'total_pieces', 'loose_pieces',
        'price_per_piece', 'price_per_m2',
        'bonus_qty', 'mfg_date', 'exp_date', 'gross_amount', 'exclusive_gst_amount',
        'sales_tax_percent', 'sales_tax_amount', 'further_tax_percent', 'further_tax_amount',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
