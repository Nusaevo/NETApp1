<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;

    public static function boot()
    {
        parent::boot();

        // static::deleting(function ($Item) {
        //     foreach ($Item->item_units as $itemunit) {
        //         $itemunit->delete();
        //     }
        //     //  $Item->item_units->delete();
        // });
    }

    protected $fillable = ['name', 'category_item_id'];

    public function category_item()
    {
        return $this->belongsTo('App\Models\CategoryItem');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\Unit', 'standard_unit_id');
    }

    public function item_units()
    {
        return $this->hasMany('App\Models\ItemUnit');
    }

    public function item_prices()
    {
        return $this->hasMany('App\Models\ItemPrice');
    }

    public function warehouse_items()
    {
        return $this->hasMany('App\Models\WarehouseItem');
    }

    public function transfer_items()
    {
        return $this->hasMany('App\Models\TransferItem');
    }

    public function adjustment_items()
    {
        return $this->hasMany('App\Models\AdjustmentItem');
    }

    public function purchase_order_details()
    {
        return $this->hasMany('App\Models\PurchaseOrderDetail');
    }

    public function sales_order_details()
    {
        return $this->hasMany('App\Models\SalesOrderDetail');
    }

    public function receive_items()
    {
        return $this->hasMany('App\Models\ReceiveItem');
    }

    public function purchase_return_items()
    {
        return $this->belongsTo('App\Models\PurchaseReturnItem');
    }

    public function sales_return_items()
    {
        return $this->hasMany('App\Models\SalesReturnItem');
    }

    public function delivery_items()
    {
        return $this->hasMany('App\Models\DeliveryItem');
    }
}
