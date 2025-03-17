<?php

namespace App\Models\TrdTire1\Master;

use App\Enums\Constant;
use App\Models\Base\BaseModel;

class SalesReward extends BaseModel
{
    protected $table = 'sales_rewards';

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'code',
        'descrs',
        'grp',
        'matl_id',
        'matl_code',
        'qty',
        'reward',
        'beg_date',
        'end_date',
    ];

}
