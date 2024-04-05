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
            $qty = currencyToNumeric($orderDtl->qty);
            $price = currencyToNumeric($orderDtl->price);
            $orderDtl->amt = $qty * $price;
        });
        static::deleting(function ($orderDtl) {
            DelivDtl::where('trhdr_id', $orderDtl->trhdr_id)
                    ->where('tr_seq', $orderDtl->tr_seq)
                    ->delete();
            BillingDtl::where('trhdr_id', $orderDtl->trhdr_id)
                      ->where('tr_seq', $orderDtl->tr_seq)
                      ->delete();
        });
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
        'price',
        'amt'
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
