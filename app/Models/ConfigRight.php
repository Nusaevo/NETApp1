<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ConfigRight extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;
    protected $table = 'config_rights';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

    protected $fillable = [
        'appl_id',
        'group_id',
        'menu_id',
        'appl_code',
        'group_code',
        'menu_code',
        'menu_seq',
        'trustee'
    ];

    public function configGroups()
    {
        return $this->belongsTo('App\Models\ConfigGroup', 'group_id', 'id');
    }

    public function configAppls()
    {
        return $this->belongsTo('App\Models\ConfigAppl', 'appl_id', 'id');
    }

    public function configMenus()
    {
        return $this->belongsTo('App\Models\ConfigMenu', 'menu_id', 'id');
    }
}
