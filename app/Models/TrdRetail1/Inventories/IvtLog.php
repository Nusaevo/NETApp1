<?php

namespace App\Models\TrdRetail1\Inventories;

use App\Models\Base\BaseModel;

class IvtLog extends BaseModel
{
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
        'ivt_id',
        'matl_id',
        'matl_code',
        'matl_uom',
        'wh_id',
        'wh_code',
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
        'status_code'
    ];
}
