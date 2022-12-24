<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;
    protected $fillable = ['transaction_date','reference_number','total_tax','total_amount','total_discount','tax_percentage','supplier_name','supplier_id'];

    public function supplier(){
        return $this->belongsTo('App\Models\Supplier');
    }

    public function purchase_order_details(){
        return $this->hasMany('App\Models\PurchaseOrderDetail');
    }

    public function invoice_details(){
        return $this->hasMany('App\Models\InvoiceDetail');
    }
}
