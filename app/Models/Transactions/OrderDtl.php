<?php

namespace App\Models\Transactions;


use App\Models\BaseModel;
use App\Models\Masters\Material;


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
        'matl_descr',
        'qty',
        'qty_reff',
        'discount',
        'price',
        'amt',
        'status_code',
    ];

    public function scopeGetByOrderHdr($query, $id)
    {
        return $query->where('trhdr_id', $id);
    }

    public function orderHdrs()
    {
        return $this->belongsTo(OrderHdr::class, 'trhdr_id', 'id');
    }

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }
}
