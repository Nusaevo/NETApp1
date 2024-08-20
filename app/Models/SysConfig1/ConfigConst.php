<?php

namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\DB;
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
