<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\{Partner, PartnerLog, PartnerBal};
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class BillingHdr extends BaseModel
{
    use SoftDeletes;
    protected static function boot()
    {
        parent::boot();
        static::created(function ($billingHdr) {
            PartnerBal::firstOrCreate([
                'partner_id' => $billingHdr->partner_id,
            ]);
            PartnerLog::create([
                'trhdr_id' => $billingHdr->id,
                'tr_type' => $billingHdr->tr_type,
                'tr_code' => $billingHdr->tr_code,
                'partner_id' => $billingHdr->partner_id,
                'partner_code' => $billingHdr->partner_code,
                'tr_date' => $billingHdr->tr_date,
                'tr_amt' => $billingHdr->tr_amt ?? 0,
            ]);
        });
    }

    protected $fillable = [
        'tr_id',
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
