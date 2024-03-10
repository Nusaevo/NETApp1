<?php

namespace App\Models\Transactions;
use App\Models\Masters\Material;
use App\Models\BaseModel;

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
        'matl_id',
        'matl_code',
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
        return $this->belongsTo(OrderHdr::class, 'trhdr_id', 'id');
    }
}
