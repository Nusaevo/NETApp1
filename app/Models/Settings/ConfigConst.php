<?php

namespace App\Models\Settings;
use App\Models\BaseModel;

class ConfigConst extends BaseModel
{
    protected $table = 'config_consts';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'const_group',
        'app_id',
        'app_code',
        'group_id',
        'group_code',
        'user_id',
        'user_code',
        'seq',
        'str1',
        'str2',
        'num1',
        'num2',
        'note1',
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('str1', 'asc')->get();
    }

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }

    public function scopeGetWarehouse()
    {
        return $this->where('const_group', 'WAREHOUSE_LOC')
                    ->orderBy('seq', 'asc')
                    ->get();
    }
}
