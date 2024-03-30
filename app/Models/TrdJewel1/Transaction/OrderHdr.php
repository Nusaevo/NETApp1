<?php

namespace App\Models\TrdJewel1\Transaction;
use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Master\Partner;
class OrderHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_type',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'sales_id',
        'sales_code',
        'deliv_by',
        'payment_term_id',
        'payment_term',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
    ];

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'trhdr_id', 'id');
    }

    public function ReturnHdr()
    {
        return $this->hasMany(ReturnHdr::class, 'tr_id', 'id');
    }

    public static function getByCreatedByAndTrType($createdBy, $trType)
    {
        return self::where('created_by', $createdBy)->where('tr_type', $trType)->get();
    }

    public function isOrderReturnCreated(): bool
    {
        foreach ($this->OrderDtl as $orderDtl) {
            if ($orderDtl->qty_reff !== $orderDtl->qty) {
                return true;
            }
        }

        return false;
    }
}
