<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class Unit extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;
    protected $fillable = ['name'];

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

    public function items(){
        return $this->hasMany('App\Models\Item','standard_unit_id');
    }

    public function from_unit() {
        return $this->hasOne('App\Models\ItemUnit', 'unit_id');
      }

    public function to_unit() {
          return $this->hasOne('App\Models\ItemUnit', 'to_unit_id');
    }

    public function item_prices(){
        return $this->hasMany('App\Models\ItemPrice');
    }

    public function origin_unit_convertions(){
        return $this->hasMany('App\Models\UnitConvertion','origin_id');
    }

    public function destination_unit_convertions(){
        return $this->hasMany('App\Models\UnitConvertion','destination_id');
    }

    public function purchase_order_details(){
        return $this->hasMany('App\Models\PurchaseOrderDetail');
    }

    public function sales_order_details(){
        return $this->hasMany('App\Models\SalesOrderDetail');
    }
}
