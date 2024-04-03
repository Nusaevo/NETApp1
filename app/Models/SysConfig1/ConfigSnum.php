<?php
namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;

class ConfigSnum extends BaseModel
{
    protected $table = 'config_snums';
    protected $connection = 'sys-config1';

    protected $fillable = [
        'code',
        'app_id',
        'app_code',
        'last_cnt',
        'wrap_low',
        'wrap_high',
        'step_cnt',
        'descr',
        'status_code'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }
}
