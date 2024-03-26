<?php

namespace App\Models\TrdJewel1\Transaction;
use App\Models\TrdJewel1\Master\Material;
use App\Models\Base\BaseModel;

class OrderDtl extends BaseModel
{

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($orderDtl) {
            $newAmt = $orderDtl->qty * $orderDtl->price;
            $orderDtl->amt = $newAmt;
        });
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'qty',
        'qty_reff',
        'amt',
        'price'
    ];

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            if ($attribute == "qty") {
                return currencyToNumeric($this->attributes[$attribute]);
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
