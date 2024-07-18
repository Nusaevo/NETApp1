<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdJewel1\Master\Material;

class ReturnDtl extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
        // static::created(function ($returnDtl) {
        //     $orderDtl = $returnDtl->OrderDtl;
        //     $orderDtlQtyReff = ceil(currencyToNumeric($orderDtl->qty_reff));
        //     $returnQty = (float)$returnDtl->qty;
        //     $newQtyReff = $orderDtlQtyReff - $returnQty;
        //     $orderDtl->qty_reff = number_format($newQtyReff, 2);
        //     $orderDtl->save();
        // });

        // static::deleting(function ($returnDtl) {
        //     $orderDtl = $returnDtl->OrderDtl;
        //     $orderDtlQtyReff = ceil(currencyToNumeric($orderDtl->qty_reff));
        //     $returnQty = (float)$returnDtl->qty;
        //     $newQtyReff = $orderDtlQtyReff + $returnQty;
        //     $orderDtl->qty_reff = number_format($newQtyReff, 2);
        //     $orderDtl->save();
        // });
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

    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'dlvdtl_id', 'id');
    }

    public function scopeGetByOrderHdr($query, $id)
    {
        return $query->where('trhdr_id', $id);
    }

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

}
