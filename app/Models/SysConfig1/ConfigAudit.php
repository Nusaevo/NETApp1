<?php

namespace App\Models\SysConfig1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Base\BaseModel;
class ConfigAudit extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'config_audits';
    protected $connection = 'sys-config1';
    protected $fillable = [
        'app_code',
        'key_code',
        'log_time',
        'action_code',
        'audit_trail',
    ];

    // If you want to specify default values for some columns, you can use the $attributes property:
    protected $attributes = [
        'app_code' => '',
        'key_code' => '',
        'action_code' => '',
        'audit_trail' => '',
    ];
}
