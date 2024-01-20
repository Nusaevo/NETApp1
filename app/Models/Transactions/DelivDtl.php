<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class DelivDtl extends Model
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
        'tr_id',
        'tr_seq',
        'reffdtl_id',
        'reffhdrtr_type',
        'reffhdrtr_id',
        'reffdtltr_seq',
        'matl_id',
        'matl_code',
        'matl_descr',
        'wh_code',
        'qty',
        'qty_reff',
        'status_code',
        'created_by',
        'updated_by',
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

    public function item_warehouses()
    {
        return $this->hasMany('App\Models\ItemWarehouse');
    }

    public function delivHdrs()
    {
        return $this->belongsTo('App\Models\OrderHdr', 'trhdr_id', 'id');
    }
}
