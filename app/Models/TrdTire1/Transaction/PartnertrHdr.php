<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnertrHdr extends BaseModel
{
    use SoftDeletes;


    protected $fillable = [
        'tr_date',
        'tr_type',
        'tr_code',
        'reff_code',
        'curr_id',
        'curr_rate',
        'amt',
        'amt_base',
    ];
}

