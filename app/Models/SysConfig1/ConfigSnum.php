<?php
namespace App\Models\SysConfig1;

use App\Models\Base\BaseModel;

class ConfigSnum extends BaseModel
{
    protected $table = 'config_snums';
    protected $connection = 'sys-config1';
    protected $fillable = [
        'app_code',
        'snum_group',
        'last_cnt',
        'wrap_low',
        'wrap_high',
        'step_cnt',
        'remark',
        'status_code',
        'is_active'
    ];
}
