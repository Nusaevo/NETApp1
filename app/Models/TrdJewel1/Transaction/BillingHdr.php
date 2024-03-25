<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;

class BillingHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_type',
        'tr_id',
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

    // Define the relationships
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function BillingDtl()
    {
        return $this->hasMany(BillingDtl::class, 'trhdr_id', 'id');
    }
}
