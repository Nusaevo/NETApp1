<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;

    protected $fillable = ['name'];

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
