<?php

use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigGroup;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
if (!function_exists('generateMenu')) {
    function generateMenu($userId)
    {
        $app_code = Session::get('app_code');
        $mainMenu = initializeMainMenu($app_code);

        if (!empty($userId)) {
            try {
                $configMenus = fetchConfigMenus($userId, $app_code);
                if ($configMenus->isEmpty()) {
                    return $mainMenu;
                }
                $mainMenu = buildMenuItems($mainMenu, $configMenus);

            } catch (QueryException $e) {
                // Handle the case where the config_menus table doesn't exist
                handleQueryException($e);
            }
        }

        return $mainMenu;
    }

    function initializeMainMenu($app_code)
    {
        return [
            [
                'title' => 'Home',
                'path' => $app_code ? $app_code.'/Home' : '',
                'icon' => theme()->getSvgIcon("demo1/media/icons/duotune/art/art002.svg", "svg-icon-2"),
            ],
        ];
    }

    function fetchConfigMenus($userId, $app_code)
    {
        return ConfigMenu::query()
            ->join('config_rights', 'config_menus.id', '=', 'config_rights.menu_id')
            ->joinSub(
                ConfigUser::find($userId)
                    ->ConfigGroup()
                    ->where('app_code', $app_code)
                    ->select('config_groups.id'),
                'user_groups',
                'config_rights.group_id',
                '=',
                'user_groups.id'
            )
            ->where('config_menus.app_code', $app_code)
            ->where('config_rights.trustee', 'like', '%R%')
            ->select('config_menus.*', 'config_rights.menu_seq')
            ->distinct()
            ->orderBy('config_rights.menu_seq')
            ->get();
    }

    function buildMenuItems($mainMenu, $configMenus)
    {
        
        foreach ($configMenus as $configMenu) {
            if (!Route::has(str_replace('/', '.', $configMenu->menu_link))) {
                continue;
            }
            $menuHeader = $configMenu->menu_header;
            $menuItem = createMenuItem($configMenu);

            if (trim($menuHeader) !== '') {
                $mainMenu = addMenuItemWithHeader($mainMenu, $menuHeader, $menuItem);
            } else {
                $mainMenu[] = $menuItem;
            }
        }

        return $mainMenu;
    }

    function createMenuItem($configMenu)
    {
        return [
            'title' => $configMenu->menu_caption,
            'path' => $configMenu->menu_link,
            'bullet' => '<span class="bullet bullet-dot"></span>',
        ];
    }

    function addMenuItemWithHeader($mainMenu, $menuHeader, $menuItem)
    {
        if (!isset($mainMenu[$menuHeader])) {
            $mainMenu[$menuHeader] = [
                'title' => $menuHeader,
                'icon' => [
                    'svg' => theme()->getSvgIcon("demo1/media/icons/duotune/abstract/abs027.svg", "svg-icon-2"),
                    'font' => '<i class="bi bi-person fs-2"></i>',
                ],
                'classes' => ['item' => 'menu-accordion'],
                'attributes' => [
                    'data-kt-menu-trigger' => 'click',
                ],
                'sub' => [
                    'class' => 'menu-sub-accordion menu-active-bg',
                    'items' => [],
                ],
            ];
        }
        $mainMenu[$menuHeader]['sub']['items'][] = $menuItem;

        return $mainMenu;
    }

    function handleQueryException($e)
    {
        // Log the error or handle it as needed
        // Example: Log::error('Database table not found: ' . $e->getMessage());
    }
}
