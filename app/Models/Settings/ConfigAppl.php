<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Helpers\SequenceUtility;
use App\Models\BaseModel;

class ConfigAppl extends BaseModel
{
    protected $table = 'config_appls';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $maxId = SequenceUtility::getCurrentSequenceValue($model);
            $model->code = 'APP' ."_". ($maxId + 1);
        });
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

    public function configMenus()
    {
        return $this->hasMany('App\Models\Settings\ConfigMenu', 'app_id', 'id');
    }
}
