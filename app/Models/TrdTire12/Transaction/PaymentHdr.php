<?php

namespace App\Models\TrdTire1\Transaction;;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Transaction\PaymentDtl;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Traits\BaseTrait;
class PaymentHdr extends BaseModel
{
    use SoftDeletes;

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

    #region Relations
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
    #endregion
}
