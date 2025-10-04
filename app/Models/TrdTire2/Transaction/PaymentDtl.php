<?php

namespace App\Models\TrdTire2\Transaction;

use App\Models\TrdTire2\Master\Partner;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;

class PaymentDtl extends BaseModel
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
        'partnerbal_id',
        'billhdrtr_type',
        'billhdrtr_code',
        'billhdr_id',
        'amt',
        'amt_base',
        'status_code'
    ];

    protected $casts = [
        'trhdr_id' => 'integer',
        'tr_seq' => 'integer',
        'partnerbal_id' => 'integer',
        'billhdr_id' => 'integer',
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
    public function paymentHdr()
    {
        return $this->belongsTo(PaymentHdr::class, 'trhdr_id', 'id');
    }


    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }

    public static function getNextTrSeq(int $trhdrId): int
    {
        $lastSeq = self::where('trhdr_id', $trhdrId)->max('tr_seq');
        return ($lastSeq ?? 0) + 1;
    }
    #endregion


}
