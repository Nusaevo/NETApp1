<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;

class Warehouse extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;


    protected $fillable = ['name', 'purpose'];

    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

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

    public function warehouse_items()
    {
        return $this->hasMany('App\Models\WarehouseItem');
    }

    public function origin_transfers()
    {
        return $this->hasMany('App\Models\Transfer', 'origin_id');
    }

    public function destination_transfers()
    {
        return $this->hasMany('App\Models\Transfer', 'destination_id');
    }

    public function adjustments()
    {
        return $this->hasMany('App\Models\Adjustment');
    }

    public function receive_details()
    {
        return $this->hasMany('App\Models\ReceiveDetail');
    }

    public function purchase_return_details()
    {
        return $this->hasMany('App\Models\PurchaseReturnDetail');
    }

    public function delivery_details()
    {
        return $this->hasMany('App\Models\DeliveryDetail');
    }

    public function sales_return_details()
    {
        return $this->hasMany('App\Models\SalesReturnDetail');
    }
}
