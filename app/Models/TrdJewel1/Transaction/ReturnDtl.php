<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdJewel1\Master\Material;

class ReturnDtl extends BaseModel
{
    use SoftDeletes;

    protected $table = 'public.return_dtls';

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
        'matl_descr',
        'qty',
        'qty_uom',
        'qty_base',
        'price',
        'price_uom',
        'price_base',
        'amt',
        'status_code',
        'qty_reff',
    ];

    public function ReturnHdr()
    {
        return $this->belongsTo(ReturnHdr::class, 'trhdr_id', 'id');
    }

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

}
