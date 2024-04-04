<?php

namespace App\Models\SysConfig1;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;

class ConfigMenu extends BaseModel
{
    protected $table = 'config_menus';
    protected $connection = 'sys-config1';

    public static function boot()
    {
        parent::boot();
        // static::creating(function ($model) {
        //     $maxId = SequenceUtility::getCurrentSequenceValue($model);
        //     $model->code = 'MENU' ."_". ($maxId + 1);
        // });
    }

    protected $fillable = [
        'code',
        'app_id',
        'app_code',
        'menu_header',
        'menu_caption',
        'menu_link'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('menu_header', 'asc')
                    ->orderBy('seq', 'asc')
                    ->get();
    }

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }
}
