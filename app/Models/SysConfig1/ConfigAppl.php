<?php

namespace App\Models\SysConfig1;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;

class ConfigAppl extends BaseModel
{
    protected $table = 'config_appls';
    protected $connection = 'sys-config1';


    public static function boot()
    {
        parent::boot();
        // static::creating(function ($model) {
        //     $maxId = SequenceUtility::getCurrentSequenceValue($model);
        //     $model->code = 'APP' ."_". ($maxId + 1);
        // });
    }

    protected $fillable = [
        'code',
        'name',
        'latest_version',
        'descr',
        'status_code'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function ConfigMenu()
    {
        return $this->hasMany(ConfigMenu::class, 'app_id', 'id');
    }
}
