<?php

namespace App\Models;

class OrderDtl extends BaseModel
{

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_seq',
        'item_unit_id',
        'item_name',
        'unit_name',
        'qty',
        'qty_reff',
        'discount',
        'price',
        'amt',
        'status_code',
    ];

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            if ($attribute == "qty") {
                return round($this->attributes[$attribute],0);
            }
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

    public function OrderHdr()
    {
        return $this->belongsTo('App\Models\OrderHdr', 'trhdr_id', 'id');
    }
}
