<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\PartnerLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\PartnerBal;

class BillingOrder extends BaseModel
{


    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'reffdtl_id',
        'reffhdr_id',
        'reffhdrtr_type',
        'reffhdrtr_code',
        'reffdtltr_seq',
        'matl_descr',
        'qty',
        'qty_uom',
        'qty_base',
        'amt',
        'amt_beforetax',
        'amt_tax',
        'amt_adjustdtl',
    ];

    protected $casts = [
        'qty' => 'float',
        'qty_base' => 'float',
    ];

    #region Relations
    // public function Partner()
    // {
    //     return $this->belongsTo(Partner::class, 'partner_id', 'id');
    // }

    // // Tambahkan relasi ke OrderHdr
    // public function OrderHdr()
    // {
    //     return $this->hasOne(OrderHdr::class, 'tr_code', 'tr_code')->where('tr_type', 'SO');
    // }

    // public function BillingDtl()
    // {
    //     return $this->hasMany(BillingDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    // }

    // public function PartnerBal()
    // {
    //     return $this->belongsTo(PartnerBal::class, 'partnerbal_id', 'id');
    // }
    #endregion

    public static function getNextTrSeq($trhdrId)
    {
        $lastSeq = self::where('trhdr_id', $trhdrId)->max('tr_seq');
        return ($lastSeq ?? 0) + 1;
    }
}
