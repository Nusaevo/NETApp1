<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetail extends Model
{
    use HasFactory;
    protected $fillable = ['remark','qty','purchase_return_item_id','warehouse_id'];

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse');
    }

    public function purchase_return_item(){
        return $this->belongsTo('App\Models\PurchaseReturnItem');
    }
}
