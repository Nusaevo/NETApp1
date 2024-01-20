<?php
namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Helpers\SequenceUtility;
use App\Models\BaseModel;

class ConfigVar extends BaseModel
{
    use HasFactory, SoftDeletes;
    use BaseTrait;
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

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
        static::creating(function ($model) {
            $maxId = SequenceUtility::getCurrentSequenceValue($model);
            $model->code = 'VAR' ."_". ($maxId + 1);
        });
    }

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }
}
