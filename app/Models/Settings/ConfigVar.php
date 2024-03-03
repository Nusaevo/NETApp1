<?php
namespace App\Models\Settings;
use App\Helpers\SequenceUtility;
use App\Models\BaseModel;

class ConfigVar extends BaseModel
{
    protected $table = 'config_vars';
    protected $connection = 'config';

    protected $fillable = [
        'code',
        'app_id',
        'app_code',
        'var_group',
        'descr',
        'seq',
        'type_code',
        'default_value'
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
