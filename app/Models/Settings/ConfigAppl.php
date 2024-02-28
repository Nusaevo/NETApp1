<?php

namespace App\Models\Settings;
use App\Helpers\SequenceUtility;
use App\Models\BaseModel;

class ConfigAppl extends BaseModel
{
    protected $table = 'config_appls';
    protected $connection = 'config';


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
        'version',
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
