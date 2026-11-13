<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'cnic',
        'ntn_number',
        'strn_number',
        'address',
        'party_type',
        'linked_customer_id',
        'opening_balance',
        'province',
        'city',
        'area',
        'zone'
    ];

      
    public function linkedCustomer()
    {
        return $this->belongsTo(Customer::class, 'linked_customer_id');
    }
    public function ledger()
    {
        return $this->hasOne(VendorLedger::class);
    }
}
