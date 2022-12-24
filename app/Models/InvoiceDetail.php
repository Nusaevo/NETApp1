<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;
    protected $fillable = ['amount','tax','discount','purchase_order_id','sales_order_id'];
   
    public function sales_order(){
        return $this->belongsTo('App\Models\SalesOrder');
    }

    public function purchase_order(){
        return $this->belongsTo('App\Models\PurchaseOrder');
    }

    public function invoice(){
        return $this->belongsTo('App\Models\Invoice');
    }
}
