<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class Item extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;

    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
        static::deleting(function ($item) {
            $item->item_units->each(function ($item_unit) {
                $item_unit->item_warehouses()->delete();
                $item_unit->item_prices()->delete();
                $item_unit->delete();
            });
        });
    }

    protected $fillable = ['name', 'category_item_id'];

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

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
