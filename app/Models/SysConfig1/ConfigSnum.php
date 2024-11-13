<?php
namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use Illuminate\Support\Facades\Session;
use App\Enums\Constant;
class ConfigSnum extends BaseModel
{
    protected $table = 'config_snums';

    use SoftDeletes;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (Session::get('app_code') !== 'SysConfig1') {
            $this->connection = Constant::AppConn();
            $this->fillable = [
                'code',
                'last_cnt',
                'wrap_low',
                'wrap_high',
                'step_cnt',
                'descr',
                'status_code'
            ];
        } else {
            $this->fillable = [
                'code',
                'app_id',
                'app_code',
                'last_cnt',
                'wrap_low',
                'wrap_high',
                'step_cnt',
                'descr',
                'status_code'
            ];
        }
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
        return $this->orderBy('code', 'asc')->get();
    }
}
