<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\PartnerLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\PartnerBal;

class BillingDeliv extends BaseModel
{

    public static function getBillCode()
    {
        return self::select('id', 'tr_code')->whereNull('deleted_at');
    }

    protected $fillable = [
        'trhdr_id',
        'deliv_id',
        'deliv_type',
        'deliv_code',
        'amt_shipcost',
    ];

    protected $casts = [
        'amt_shipcost' => 'float',
    ];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    // Tambahkan relasi ke OrderHdr
    public function OrderHdr()
    {
        return $this->hasOne(OrderHdr::class, 'tr_code', 'tr_code')->where('tr_type', 'SO');
    }

        // public function BillingDtl()
        // {
        //     return $this->hasMany(BillingDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
        // }

    public function PartnerBal()
    {
        return $this->belongsTo(PartnerBal::class, 'partnerbal_id', 'id');
    }
    #endregion
}
