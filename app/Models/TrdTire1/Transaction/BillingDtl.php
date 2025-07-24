<?php

namespace App\Models\TrdTire1\Transaction;
use App\Models\TrdTire1\Master\Material;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;

class BillingDtl extends BaseModel
{

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'dlvdtl_id',
        'dlvhdr_id',
        'dlvhdrtr_type',
        'dlvhdrtr_code',
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
        'amt_beforetax',
        'amt_tax',
        'amt_shipcost',
        'status_code',
    ];

    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
                     ->where('tr_type', $trType);
    }

    #region Relations
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function BillingHdr()
    {
        return $this->belongsTo(BillingHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }
    #endregion
}
