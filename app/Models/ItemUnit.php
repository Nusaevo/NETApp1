<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ItemUnit extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;
    protected $fillable = ['unit_id', 'item_id', 'multiplier', 'to_unit_id', 'barcode'];

    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
        static::deleting(function ($itemUnit) {
            $itemUnit->item_warehouses()->delete();
            $itemUnit->item_prices()->delete();
        });
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

    public function scopeIsDuplicate($query, $item_id, $unit_id)
    {
        return $query->where('item_id', $item_id)->where('unit_id', $unit_id);
    }
    public function scopeItemId($query, $id)
    {
        return $query->where('item_id', $id);
    }

    public function scopeItemUnitId($query, $id)
    {
        return $query->where('id', $id);
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Item');
    }

    public function from_unit()
    {
        return $this->belongsTo('App\Models\Unit', 'unit_id');
    }

    public function to_unit()
    {
        return $this->belongsTo('App\Models\Unit', 'to_unit_id');
    }

    public function item_warehouses()
    {
        return $this->hasMany('App\Models\ItemWarehouse');
    }

    public function item_prices()
    {
        return $this->hasMany('App\Models\ItemPrice');
    }
}
