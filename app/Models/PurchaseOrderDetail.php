<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    use HasFactory;
    protected $fillable = ['price','qty','discount','item_name','unit_name','purchase_order_id','item_id','unit_id'];

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

    public function purchase_order(){
        return $this->belongsTo('App\Models\PurchaseOrder');
    }
    
    public function unit(){
        return $this->belongsTo('App\Models\Unit');
    }
}
