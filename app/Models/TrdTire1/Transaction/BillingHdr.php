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
    use SoftDeletes;

    public static function getBillCode()
    {
        return self::select('id', 'tr_code')->whereNull('deleted_at');
    }

    protected $fillable = [
        'tr_type',
        'tr_code',
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
        'partnerbal_id',
        'amt',
        'amt_beforetax',
        'amt_tax',
        'amt_adjustdtl',
        'amt_adjusthdr',
        'amt_shipcost',
        'amt_reff',
        'print_date',
    ];

    protected $casts = [
        'curr_rate' => 'float',
        'amt' => 'float',
        'amt_beforetax' => 'float',
        'amt_tax' => 'float',
        'amt_adjustdtl' => 'float',
        'amt_adjusthdr' => 'float',
        'amt_shipcost' => 'float',
        'amt_reff' => 'float',
        'curr_id' => 'integer',
        'payment_term_id' => 'integer',
        'partner_id' => 'integer',
        'partnerbal_id' => 'integer',
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

    public function BillingOrder()
    {
        return $this->hasMany(BillingOrder::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }

    public function PartnerBal()
    {
        return $this->belongsTo(PartnerBal::class, 'partnerbal_id', 'id');
    }

    public static function updAmtReff(int $trhdrId, float $amtReff)
    {
        $trhdr = self::find($trhdrId);
        if ($trhdr) {
            $trhdr->amt_reff += $amtReff;
            $trhdr->save();
        }
    }

}
