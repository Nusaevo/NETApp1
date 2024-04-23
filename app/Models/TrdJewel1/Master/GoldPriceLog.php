<?php

namespace App\Models\TrdJewel1\Master;

use App\Models\Base\BaseModel;

class GoldPriceLog extends BaseModel
{
    protected $table = 'goldprice_logs';

    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'log_date',
        'curr_id',
        'curr_rate',
        'goldprice_curr',
        'goldprice_basecurr',
    ];
}
