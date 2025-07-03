<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\PartnerLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\PartnerBal;

class BillingHdr extends BaseModel
{

    public static function getBillCode()
    {
        return self::select('id', 'tr_code')->whereNull('deleted_at');
    }

    protected $fillable = [
        'tr_code',
        'tr_type',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'payment_term_id',
        'payment_term',
        'payment_due_days',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
        'print_date',
        'total_amt'
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

    public function BillingDtl()
    {
        return $this->hasMany(BillingDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }
    #endregion
}
