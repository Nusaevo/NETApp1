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

    protected static function boot()
    {
        parent::boot();

        static::created(function ($billingHdr) {
            // Create PartnerBal
            $partnerBal = PartnerBal::firstOrCreate(
                [
                    'partner_id' => $billingHdr->partner_id,
                ],
            );

            // Create PartnerLog
            PartnerLog::create([
                'trhdr_id' => $billingHdr->id,
                'tr_type' => $billingHdr->tr_type,
                'tr_code' => $billingHdr->tr_code,
                // 'tr_desc' => $billingHdr->tr_desc,
                // 'tr_seq' => 1, // Assuming sequence starts at 1
                // 'trdtl_id' => null, // Assuming no detail ID at this point
                'partner_id' => $billingHdr->partner_id,
                'partner_code' => $billingHdr->partner_code,
                'tr_date' => $billingHdr->tr_date,
                'tr_amt' => 0, // Assuming initial amount is 0
            ]);
        });
    }

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
        'status_code'
    ];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function BillingDtl()
    {
        return $this->hasMany(BillingDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }
    #endregion
}
