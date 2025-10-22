<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\PartnerLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\PartnerBal;

class AuditLogs extends BaseModel
{
    use SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'group_code',
        'event_code',
        'event_time',
        'key_value',
        'audit_trail',
    ];

    protected $casts = [
        'audit_trail' => 'array',
    ];

}
