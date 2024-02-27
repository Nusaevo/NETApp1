<?php
namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Models\BaseModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;


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
        'group_id',
        'menu_id',
        'group_code',
        'menu_code',
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

    public static function getPermissions()
    {
        $permissions = ['create' => false, 'read' => false, 'update' => false, 'delete' => false];

        $userId = Auth::check() ? Auth::user()->id : null;
        $appCode = config('app.name');
        $firstSegment = Request::segment(1);

        if (!empty($firstSegment) && !is_null($userId)) {
            $configGroup = ConfigGroup::where('user_id', $userId)
                            ->where('app_code', $appCode)
                            ->first();

            if ($configGroup) {
                $configMenu = ConfigMenu::where('link', $firstSegment)->first();

                if ($configMenu) {
                    $configRight = self::where('menu_id', $configMenu->id)
                                    ->where('group_id', $configGroup->id)
                                    ->first();

                    if ($configRight) {
                        // Parsing the trustee string
                        $trustee = $configRight->trustee;
                        $permissions['create'] = strpos($trustee, 'C') !== false;
                        $permissions['read'] = strpos($trustee, 'R') !== false;
                        $permissions['update'] = strpos($trustee, 'U') !== false;
                        $permissions['delete'] = strpos($trustee, 'D') !== false;
                    }
                }
            }
        }

        return $permissions;
    }

    public static function getPermissionsByMenu($menu)
    {
        $permissions = ['create' => false, 'read' => false, 'update' => false, 'delete' => false];

        $userId = Auth::check() ? Auth::user()->id : null;
        $appCode = config('app.name');
        if (!is_null($userId)) {
            $configGroup = ConfigGroup::where('user_id', $userId)
                            ->where('app_code', $appCode)
                            ->first();

            if ($configGroup) {
                $configMenu = ConfigMenu::where('link', $menu)->first();

                if ($configMenu) {
                    $configRight = self::where('menu_id', $configMenu->id)
                                    ->where('group_id', $configGroup->id)
                                    ->first();

                    if ($configRight) {
                        // Parsing the trustee string
                        $trustee = $configRight->trustee;
                        $permissions['create'] = strpos($trustee, 'C') !== false;
                        $permissions['read'] = strpos($trustee, 'R') !== false;
                        $permissions['update'] = strpos($trustee, 'U') !== false;
                        $permissions['delete'] = strpos($trustee, 'D') !== false;
                    }
                }
            }
        }

        return $permissions;
    }
}
