<?php

namespace App\Models;

use App\Models\Base\BaseModel;


class PartnertrDtl extends BaseModel
{
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