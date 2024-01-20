<?php
namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Models\BaseModel;

class ConfigRight extends BaseModel
{
    use HasFactory, SoftDeletes;
    use BaseTrait;
    protected $table = 'config_rights';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'app_id',
        'group_id',
        'menu_id',
        'app_code',
        'group_code',
        'menu_code',
        'menu_seq',
        'trustee'
    ];

    public function ConfigGroup()
    {
        return $this->belongsTo(ConfigGroup::class, 'group_id', 'id');
    }

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }

    public function ConfigMenu()
    {
        return $this->belongsTo(ConfigMenu::class, 'menu_id', 'id');
    }
}
