<?php
namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
class ConfigGroupUser extends BaseModel
{
    protected $table = 'config_grpusers';
    use SoftDeletes;

    protected $fillable = [
        'group_id',
        'group_code',
        'user_id',
        'user_code',
        'descr'
    ];

    #region Relations
    #endregion

    #region Attributes
    #endregion
}
