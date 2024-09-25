<?php

namespace App\Models\TrdJewel1\Inventories;

use Illuminate\Database\Eloquent\Model;
use App\Models\TrdJewel1\Master\Material;
use App\Enums\Constant;

class IvtLog extends Model
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
