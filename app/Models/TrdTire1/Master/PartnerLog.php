<?php

namespace App\Models\TrdTire1\Master;

use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class PartnerLog extends BaseModel
{
    protected $table = 'partner_logs';

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_date',
        'trdtl_id',
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'partner_id',
        'partner_code',
        'reff_id',
        'reffhdr_id',
        'reff_type',
        'reff_code',
        'tr_amt',
        'tramt_adjusthdr',
        'tramt_shipcost',
        'partnerbal_id',
        'amt',
        'curr_id',
        'curr_code',
        'curr_rate',
        'tr_desc',
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }
}
