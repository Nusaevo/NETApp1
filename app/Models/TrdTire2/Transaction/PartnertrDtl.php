<?php

namespace App\Models\TrdTire2\Transaction;

use App\Models\Base\BaseModel;


class PartnertrDtl extends BaseModel
{
    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'partnerbal_id',
        'partner_id',
        'partner_code',
        'reff_id',
        'reff_type',
        'reff_code',
        'amt',
        'tr_descr',
    ];

    protected $casts = [
        'trhdr_id' => 'integer',
        'tr_seq' => 'integer',
        'partnerbal_id' => 'integer',
        'partner_id' => 'integer',
        'reff_id' => 'integer',
        'amt' => 'float',
    ];

    #region Relations
    public function PaymentSrc()
    {
        return $this->belongsTo(PaymentSrc::class, 'reff_id', 'id');
    }
    #endregion

    public static function getNextTrSeq(int $trhdrId): int
    {
        $maxSeq = self::where('trhdr_id', $trhdrId)
            ->where('tr_seq', '>', 0)
            ->max('tr_seq');

        return ($maxSeq ?? 0) + 1;
    }
}
