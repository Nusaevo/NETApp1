<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnertrDtl extends BaseModel
{
    // use SoftDeletes;

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'partnerbal_id',
        'partner_id',
        'partner_code',
        'reff_id',
        'reff_type',
        'reff_code',
        'amt',
        'tr_descr',
    ];
}

