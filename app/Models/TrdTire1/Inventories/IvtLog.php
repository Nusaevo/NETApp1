<?php

namespace App\Models\TrdTire1\Inventories;

use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Enums\Constant;
use App\Models\Base\BaseModel;

class IvtLog extends BaseModel
{
    protected $table = 'ivt_logs';
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'trdtl_id',
        'ivt_id',
        'matl_id',
        'matl_uom',
        'wh_id',
        'batch_code',
        'reff_id',
        'tr_date',
        'qty',
        'price',
        'amt',
        'tr_desc',
        'price_cogs',
        'amt_cogs',
        'qty_running',
        'amt_running',
        'process_flag',
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
