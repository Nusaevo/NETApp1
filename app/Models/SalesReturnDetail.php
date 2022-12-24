<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnDetail extends Model
{
    use HasFactory;
    protected $fillable = ['remark','qty','sales_return_item_id','warehouse_id'];

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse');
    }

    public function sales_return_item(){
        return $this->belongsTo('App\Models\SalesReturnItem');
    }
}
