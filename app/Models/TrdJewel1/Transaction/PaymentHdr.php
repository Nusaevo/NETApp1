<?php

namespace App\Models\TrdJewel1\Transaction;;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\TrdJewel1\Transaction\PaymentDtl;

class PaymentHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
    }
    protected $fillable = [
        'tr_id',
        'tr_type',
        'tr_id',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'bank_id',
        'bank_code',
        'bank_reff',
        'bank_due',
        'bank_rcv',
        'bank_rcv_base',
        'bank_note',
        'curr_id',
        'curr_rate',
        'status_code'
    ];

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function PaymentDtl()
    {
        return $this->hasMany(PaymentDtl::class, 'trhdr_id', 'id');
    }

    public static function getByCreatedByAndTrType($createdBy, $trType)
    {
        return self::where('created_by', $createdBy)->where('tr_type', $trType)->get();
    }
}
