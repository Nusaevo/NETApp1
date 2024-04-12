<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Material;

class DelivDtl extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
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
        'matl_uom',
        'matl_descr',
        'wh_code',
        'qty',
        'qty_reff',
        'status_code'
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

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function DelivHdr()
    {
        return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id');
    }
}
