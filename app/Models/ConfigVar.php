<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;

class ConfigVar extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;
    protected $table = 'config_vars';
    protected $connection = 'config';

    protected $fillable = [
        'code',
        'appl_id',
        'appl_code',
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
            $maxId = static::max('id') ?? 0;
            $model->code = 'VAR' ."_". ($maxId + 1);
        });
    }

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

    public function configAppls()
    {
        return $this->belongsTo('App\Models\ConfigAppl', 'appl_id', 'id');
    }
}
