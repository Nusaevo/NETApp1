<?php

namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\DB;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;
use App\Enums\Constant;
class ConfigConst extends BaseModel
{
    protected $table = 'config_consts';

    use SoftDeletes;

    const CURRENCY_DOLLAR_ID = '125';
    const CURRENCY_RUPIAH_ID = '124';
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set the database connection based on app_code session
        if (Session::get('app_code') !== 'SysConfig1') {
            $this->connection = Constant::AppConn();
            // Set fillable fields without app_id and app_code if not SysConfig1
            $this->fillable = [
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
        } else {
            // Full fillable fields if app_code is SysConfig1
            $this->fillable = [
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
        }
    }

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
