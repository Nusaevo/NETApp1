<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Helpers\SequenceUtility;
class ConfigMenu extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;

    protected $table = 'config_menus';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
        static::creating(function ($model) {
            $maxId = SequenceUtility::getCurrentSequenceValue($model);
            $model->code = 'MENU' ."_". ($maxId + 1);
        });
    }

    protected $fillable = [
        'code',
        'app_id',
        'app_code',
        'menu_header',
        'sub_menu',
        'menu_caption',
        'link',
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

    public function configAppls()
    {
        return $this->belongsTo('App\Models\ConfigAppl', 'app_id', 'id');
    }
}
