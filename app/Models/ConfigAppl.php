<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Helpers\SequenceUtility;
class ConfigAppl extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;
    protected $table = 'config_appls';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
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

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function configMenus()
    {
        return $this->hasMany('App\Models\ConfigMenu', 'app_id', 'id');
    }
}
