<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ModelTrait;

class SalesOrderDetail extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    protected $fillable = ['discount', 'price', 'qty', 'qty_wo', 'discount', 'item_warehouse_id', 'item_name', 'unit_name', 'sales_order_id'];

    public function sales_order()
    {
        return $this->belongsTo('App\Models\SalesOrder');
    }

    public function item_warehouse()
    {
        return $this->belongsTo('App\Models\ItemWarehouse')->withTrashed();
    }
}
