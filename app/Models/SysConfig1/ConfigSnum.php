<?php
namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use Illuminate\Support\Facades\Session;
use App\Enums\Constant;
use App\Models\SysConfig1\Base\SysConfig1BaseModel;

class ConfigSnum extends SysConfig1BaseModel
{
    protected $table = 'config_snums';

    use SoftDeletes;
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $sessionAppCode = Session::get('app_code');
        $this->connection = $sessionAppCode;
    }

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
