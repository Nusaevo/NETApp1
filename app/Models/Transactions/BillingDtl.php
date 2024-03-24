<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Models\TrdJewel1\Master\Material;

class OrderDtl extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    use BaseTrait;

    protected static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_seq',
        'matl_id',
        'matl_code',
        'matl_descr',
        'qty',
        'qty_reff',
        'discount',
        'price',
        'amt',
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

    public function scopeGetByOrderHdr($query, $id)
    {
        return $query->where('trhdr_id', $id);
    }

    public function item_units()
    {
        return $this->hasMany('App\Models\ItemUnit');
    }

    public function orderHdrs()
    {
        return $this->belongsTo('App\Models\OrderHdr', 'trhdr_id', 'id');
    }

    public function materials()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }
}
