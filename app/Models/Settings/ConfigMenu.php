<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Helpers\SequenceUtility;
use App\Models\BaseModel;

class ConfigMenu extends BaseModel
{
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
        'status_code',
        'seq'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('menu_header', 'asc')
                    ->orderBy('seq', 'asc')
                    ->get();
    }

    public function configAppls()
    {
        return $this->belongsTo('App\Models\Settings\ConfigAppl', 'app_id', 'id');
    }
}
