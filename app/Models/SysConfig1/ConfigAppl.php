<?php

namespace App\Models\SysConfig1;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SysConfig1\Base\SysConfig1BaseModel;

class ConfigAppl extends SysConfig1BaseModel
{
    protected $table = 'config_appls';

    use SoftDeletes;

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
        'status_code',
        'db_name',
        'seq'
    ];

    #region Relations

    public function ConfigMenu()
    {
        return $this->hasMany(ConfigMenu::class, 'app_id', 'id');
    }

    #endregion

    #region Attributes
    #endregion

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }
}
