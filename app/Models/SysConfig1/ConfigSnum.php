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

    protected $fillable = [
        'code',
        'last_cnt',
        'wrap_low',
        'wrap_high',
        'step_cnt',
        'descr',
        'status_code'
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
        return $this->orderBy('code', 'asc')->get();
    }
}
