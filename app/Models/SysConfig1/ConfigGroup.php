<?php
namespace App\Models\SysConfig1;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigGroup extends BaseModel
{
    protected $table = 'config_groups';
    use SoftDeletes;

    public static function boot()
    {
        parent::boot();
        // static::creating(function ($model) {
        //     $maxId = SequenceUtility::getCurrentSequenceValue($model);
        //     $model->code = 'GROUP' ."_". ($maxId + 1);
        // });
    }

    protected $fillable = [
        'code',
        'app_id',
        'app_code',
        'descr',
        'status_code'
    ];

    #region Relations

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }

    public function ConfigRight()
    {
        return $this->hasMany(ConfigRight::class, 'group_id', 'id');
    }

    public function ConfigGroupUser()
    {
        return $this->hasMany(ConfigGroupUser::class, 'group_id', 'id');
    }
    // public function ConfigUser()
    // {
    //     return $this->belongsTo(ConfigUser::class, 'user_id', 'id');
    // }

    public function ConfigUser()
    {
        return $this->belongsToMany(ConfigUser::class, 'config_grpusers', 'group_id', 'user_id');
    }

    #endregion

    #region Attributes
    #endregion

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }
}
