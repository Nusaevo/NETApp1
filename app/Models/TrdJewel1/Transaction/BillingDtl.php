<?php

namespace App\Models\TrdJewel1\Transaction;
use App\Models\TrdJewel1\Master\Material;
use App\Models\Base\BaseModel;

class BillingDtl extends BaseModel
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
        'dlvdtl_id',
        'dlvhdrtr_type',
        'dlvhdrtr_id',
        'dlvdtltr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'descr',
        'qty',
        'qty_uom',
        'qty_base',
        'price',
        'price_uom',
        'price_base',
        'amt',
        'amt_reff',
        'status_code'
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

    public function BillingHdr()
    {
        return $this->belongsTo(BillingHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }
}
