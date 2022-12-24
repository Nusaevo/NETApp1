<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryDetail extends Model
{
    use HasFactory;
    protected $fillable = ['remark','qty','warehouse_id','delivery_item_id'];

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse');
    }

    public function delivery_item(){
        return $this->belongsTo('App\Models\DeliveryItem');
    }
}
