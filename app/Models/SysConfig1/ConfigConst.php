<?php

namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\DB;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Database\Eloquent\SoftDeletes;
class ConfigConst extends BaseModel
{
    protected $table = 'config_consts';

    use SoftDeletes;

    const CURRENCY_DOLLAR_ID = '125';
    const CURRENCY_RUPIAH_ID = '124';
    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            if ($model->const_group === 'MMATL_CATEGL1') {
                ConfigSnum::create([
                    'code' => "MMATL_".$model->str1."_LASTID",
                    'app_id' => $model->app_id,
                    'app_code' => $model->app_code,
                    'last_cnt' => 1,
                    'wrap_low' => 1,
                    'wrap_high' => 99999999,
                    'step_cnt' => 1,
                    'descr' => "Serial Number untuk Barang dengan Category " . $model->str1
                ]);
            }
        });
    }

    protected $fillable = [
        'const_group',
        'app_id',
        'app_code',
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
    #region Relations

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }

    #endregion

    #region Attributes
    #endregion

    public function scopeGetActiveData()
    {
        return $this->orderBy('str1', 'asc')->get();
    }

}
