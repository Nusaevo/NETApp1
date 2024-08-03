<?php
namespace App\Models\SysConfig1;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ConfigVar extends BaseModel
{
    protected $table = 'config_vars';
    protected $connection = 'sys-config1';
    use SoftDeletes;

    use SoftDeletes;
        protected $fillable = [
        'code',
        'app_id',
        'app_code',
        'var_group',
        'descr',
        'seq',
        'type_code',
        'default_value'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }
}
