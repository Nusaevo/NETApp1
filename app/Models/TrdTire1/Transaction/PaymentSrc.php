<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Partner;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;

class PaymentSrc extends BaseModel
{
    protected $table = 'payment_srcs';

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'pay_type_id',
        'pay_type_code',
        'bank_id',
        'bank_code',
        'bank_reff',
        'bank_duedt',
        'bank_note',
        'partnerbal_id',
        'reff_id',
        'reff_type',
        'reff_code',
        'amt',
        'amt_base'
    ];

    protected $casts = [
        'trhdr_id' => 'integer',
        'tr_seq' => 'integer',
        'pay_type_id' => 'integer',
        'bank_id' => 'integer',
        'partnerbal_id' => 'integer',
        'reff_id' => 'integer',
        'amt' => 'float',
        'amt_base' => 'float',
    ];

     #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->hasMany(PaymentHdr::class, 'trhdr_id', 'id');
    }
    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }
    public function paymentHdr()
    {
        return $this->belongsTo(PaymentHdr::class, 'trhdr_id', 'id');
    }

    public static function getNextTrSeq(int $trhdrId): int
    {
        $lastSeq = self::where('trhdr_id', $trhdrId)->max('tr_seq');
        return ($lastSeq ?? 0) + 1;
    }

    #endregion


}
