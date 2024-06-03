<?php
namespace App\Models\SysConfig1;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;


class ConfigRight extends BaseModel
{
    protected $table = 'config_rights';
    protected $connection = 'sys-config1';

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
        'trustee',
        'app_id',
        'app_code'
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

    public static function getPermissionsByMenu($menu, $appCode = null)
    {
        $permissions = ['create' => false, 'read' => false, 'update' => false, 'delete' => false];

        // Use the provided app_code or fallback to the session.
        $appCode = $appCode ?: session('app_code');

        if (Auth::check()) {
            $userId = Auth::id();

            // First, get the user's group IDs
            // $userGroupIds = ConfigUser::find($userId)
            // ->ConfigGroup()
            // ->pluck('config_groups.id');

            // if ($userGroupIds->isEmpty()) {
            //     return $permissions;
            // }
            // // Find the menu based on the menu_link and app_code
            // $configMenu = ConfigMenu::where('menu_link', $menu)
            //                         ->whereHas('ConfigAppl', function ($query) use ($appCode) {
            //                             $query->where('app_code', $appCode);
            //                         })
            //                         ->first();
            // if (!$configMenu) {
            //     return $permissions;
            // }

            // // Now, find the ConfigRight based on the user's group and the found menu
            // $configRight = ConfigRight::whereIn('group_id', $userGroupIds)
            //                         ->where('menu_id', $configMenu->id)
            //                         ->first();


            $configRight = ConfigRight::join('config_menus', 'config_rights.menu_id', '=', 'config_menus.id')
            ->join('config_appls', 'config_menus.app_id', '=', 'config_appls.id')
            ->join('config_grpusers', 'config_rights.group_id', '=', 'config_grpusers.group_id')
            ->join('config_users', 'config_grpusers.user_id', '=', 'config_users.id')
            ->where('config_users.id', $userId)
            ->where('config_menus.menu_link', $menu)
            ->where('config_appls.code', $appCode)
            ->select('config_rights.*')
            ->first();
            if (!$configRight) {
                return $permissions;
            }

            if ($configRight) {
                // Parsing the trustee string
                $trustee = $configRight->trustee;
                $permissions['create'] = strpos($trustee, 'C') !== false;
                $permissions['read'] = strpos($trustee, 'R') !== false;
                $permissions['update'] = strpos($trustee, 'U') !== false;
                $permissions['delete'] = strpos($trustee, 'D') !== false;
            }
        }
        return $permissions;
    }

    public static function saveRights($configGroup, $selectedMenus)
    {
        try {
            // Retrieve all existing rights for the group to decide which to update, delete, or create
            $existingRights = static::where('group_id', $configGroup->id)->get()->keyBy('menu_id');

            // Determine which menu IDs need to be deleted
            $menuIdsToDelete = array_diff($existingRights->keys()->toArray(), array_keys($selectedMenus));

            // Delete rights that are not in the selectedMenus anymore
            if (!empty($menuIdsToDelete)) {
                static::where('group_id', $configGroup->id)
                      ->whereIn('menu_id', $menuIdsToDelete)
                      ->forceDelete();
            }

            foreach ($selectedMenus as $menuId => $permissions) {
                $trustee = static::prepareTrusteeString($permissions);

                // Ensure the ConfigMenu exists
                $configMenu = ConfigMenu::find($menuId);
                if (!$configMenu) {
                    throw new \Exception("ConfigMenu with ID {$menuId} not found.");
                }

                if (isset($existingRights[$menuId])) {
                    // Update existing right
                    $existingRight = $existingRights[$menuId];
                    $existingRight->update([
                        'menu_seq' => !empty($permissions['menu_seq']) ? $permissions['menu_seq'] : 0,
                        'trustee' => $trustee,
                        'group_code' => $configGroup->code,
                        'menu_code' => $configMenu->code,
                        'app_id' => $configGroup->app_id,
                        'app_code' => $configGroup->app_code,
                    ]);
                } else {
                    static::create([
                        'group_id' => $configGroup->id,
                        'menu_id' => $menuId,
                        'menu_seq' => !empty($permissions['menu_seq']) ? $permissions['menu_seq'] : 0,
                        'trustee' => $trustee,
                        'group_code' => $configGroup->code,
                        'menu_code' => $configMenu->code,
                        'app_id' => $configGroup->app_id,
                        'app_code' => $configGroup->app_code,
                    ]);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

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
