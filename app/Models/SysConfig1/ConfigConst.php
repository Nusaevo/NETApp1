<?php

namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

class ConfigConst extends BaseModel
{
    protected $table = 'config_consts';

    use SoftDeletes;

    const CURRENCY_DOLLAR_ID = '125';
    const CURRENCY_RUPIAH_ID = '124';

    protected $fillable = [
        'const_group',
        'group_id',
        'group_code',
        'user_id',
        'user_code',
        'seq',
        'str1',
        'str2',
        'num1',
        'num2',
        'note1',
    ];

    protected static $serialNumberMappings = [
        'TrdJewel1' => [
            'MMATL_CATEGL1' => "Serial Number untuk Category pada TrdJewel1"
        ],
        'TrdTire1' => [
            'MMATL_MERK' => "Serial Number untuk Merk pada TrdTire1",
        ]
    ];

    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $appCode = session('app_code');

            if ($appCode && isset(self::$serialNumberMappings[$appCode])) {
                $appMapping = self::$serialNumberMappings[$appCode];

                if (isset($appMapping[$model->const_group])) {
                    $description = $appMapping[$model->const_group];

                    ConfigSnum::create([
                        'code' => "MMATL_" . $model->str1 . "_LASTID",
                        'last_cnt' => 0,
                        'wrap_low' => 1,
                        'wrap_high' => 99999999,
                        'step_cnt' => 1,
                        'descr' => $description . " " . $model->str1
                    ]);
                }
            } else {
                \Log::warning("App code not found or invalid in session for ConfigConst creation.");
            }
        });
    }


    #region Relations

    // public function ConfigAppl()
    // {
    //     return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    // }

    #endregion

    #region Attributes
    #endregion

    public function scopeGetActiveData()
    {
        return $this->orderBy('str1', 'asc')->get();
    }

}
