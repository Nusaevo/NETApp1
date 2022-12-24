<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    use HasFactory;
    protected $fillable = ['item_id','receive_id','purchase_return_id'];

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }
    
    public function receive(){
        return $this->belongsTo('App\Models\Receive');
    }

    public function purchase_return(){
        return $this->belongsTo('App\Models\PurchaseReturn');
    }

    public function purchase_return_details(){
        return $this->belongsTo('App\Models\PurchaseReturnDetail');
    }
}
