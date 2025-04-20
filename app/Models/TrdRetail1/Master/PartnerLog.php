<?php

namespace App\Models\TrdRetail1\Master;

use App\Models\TrdRetail1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class PartnerLog extends BaseModel
{
    protected $table = 'partner_logs';
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_id',
        'tr_seq',
        'trdtl_id',
        'partner_id',
        'partner_code',
        'tr_date',
        'tr_amt',
        'tr_desc',
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function delivDtl()
    {
        return $this->belongsTo(DelivDtl::class, 'trdtl_id');
    }
}
