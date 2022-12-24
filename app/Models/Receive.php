<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Receive extends Model
{
    use HasFactory;
    protected $fillable = ['receive_date','reference_number','purchase_order_id'];
   
    public function purchase_order(){
        return $this->belongsTo('App\Models\PurchaseOrder');
    }

    public function receive_items(){
        return $this->hasMany('App\Models\ReceiveItem');
    }
    
    public function purchase_return_items(){
        return $this->belongsTo('App\Models\PurchaseReturnItem');
    }
}
