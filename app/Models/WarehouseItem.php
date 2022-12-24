<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseItem extends Model
{
    use HasFactory;
    protected $fillable = ['qty','qty_defect','unit_id','item_id','warehouse_id'];

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse');
    }

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

    public function warehouse_details(){
        return $this->hasMany('App\Models\WarehouseDetail');
    }
}
