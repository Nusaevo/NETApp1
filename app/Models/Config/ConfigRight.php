<?php
namespace App\Models\Config;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Models\Base\BaseModel;
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
        'menu_seq',
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
        // $appCode = config('app.name');
        $firstSegment = Request::segment(1);

        if (!empty($firstSegment) && !is_null($userId)) {
            $configGroup = ConfigUser::find($userId)->ConfigGroup()->pluck('config_groups.id');

            if ($configGroup) {
                $configMenu = ConfigMenu::where('menu_link', $firstSegment)->first();

                if ($configMenu) {
                    $configRight = ConfigRight::whereIn('group_id', $configGroup)
                    ->where('menu_id', $configMenu->id)
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
        $permissions = ['create' => true, 'read' => true, 'update' => true, 'delete' => true];

        $userId = Auth::check() ? Auth::user()->id : null;
        // $appCode = config('app.name');
        if (!is_null($userId)) {

            $configGroup = ConfigUser::find($userId)->ConfigGroup()->pluck('config_groups.id');

            if ($configGroup) {
                $configMenu = ConfigMenu::where('menu_link', $menu)->first();

                if ($configMenu) {
                    $configRight = ConfigRight::whereIn('group_id', $configGroup)
                    ->where('menu_id', $configMenu->id)
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

    public static function saveRights($groupId, $selectedMenus, $groupCode = null)
    {
        static::where('group_id', $groupId)
            ->whereNotIn('menu_id', array_keys($selectedMenus))
            ->delete();

        foreach ($selectedMenus as $menuId => $permissions) {
            $trustee = static::prepareTrusteeString($permissions); // Assume prepareTrusteeString is moved or adapted for the model

            // Update or create ConfigRight for each menuId with necessary data
            static::updateOrCreate(
                ['group_id' => $groupId, 'menu_id' => $menuId],
                [
                    'trustee' => $trustee,
                    'group_code' => $groupCode ?? ConfigGroup::find($groupId)->code ?? '',
                    'menu_code' => ConfigMenu::where('id', $menuId)->value('code') ?? '',
                ]
            );
        }
    }

    // Adapt or move prepareTrusteeString to handle permissions
    protected static function prepareTrusteeString($permissions)
    {
        $trustee = '';
        $trustee .= $permissions['create'] ? 'C' : '';
        $trustee .= $permissions['read'] ? 'R' : '';
        $trustee .= $permissions['update'] ? 'U' : '';
        $trustee .= $permissions['delete'] ? 'D' : '';
        return $trustee;
    }

}
