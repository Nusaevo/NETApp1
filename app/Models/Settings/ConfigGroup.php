<?php
namespace App\Models\Settings;
use App\Helpers\SequenceUtility;
use App\Models\BaseModel;

class ConfigGroup extends BaseModel
{
    protected $table = 'config_groups';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $maxId = SequenceUtility::getCurrentSequenceValue($model);
            $model->code = 'GROUP' ."_". ($maxId + 1);
        });
    }

    protected $fillable = [
        'code',
        'app_id',
        'app_code',
        'user_id',
        'user_code',
        'name',
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

    public function ConfigUser()
    {
        return $this->belongsTo(ConfigUser::class, 'user_id', 'id');
    }
}
