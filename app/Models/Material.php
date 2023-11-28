<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class Material extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;

    protected $table = 'materials';

    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
        static::creating(function ($model) {
            $maxId = static::max('id') ?? 0;
            $model->code = 'MATL' ."_". ($maxId + 1);
        });
    }

    protected $fillable = [
        'code',
        'name',
        'descr',
        'type_code',
        'class_code',
        'jwl_carat',
        'jwl_base_matl',
        'jwl_category',
        'jwl_wgt_gold',
        'jwl_supplier_id',
        'jwl_supplier_code',
        'jwl_supplier_id1',
        'jwl_supplier_id2',
        'jwl_supplier_id3',
        'jwl_sides_carat',
        'jwl_sides_cnt',
        'jwl_sides_matl',
        'jwl_selling_price_usd',
        'jwl_selling_price_idr',
        'jwl_sides_calc_method',
        'jwl_matl_price',
        'jwl_sellprc_calc_method',
        'jwl_price_markup_id',
        'jwl_price_markup_code',
        'jwl_selling_price',
        'uom',
        'brand',
        'dimension',
        'wgt',
        'qty_min',
        'taxable',
        'info',
        'status_code',
    ];


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
