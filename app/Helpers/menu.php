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
        $mainMenu = [
            [
                'title' => 'Home',
                'path' => $app_code ? $app_code.'/Home' : '',
                'icon' => theme()->getSvgIcon("demo1/media/icons/duotune/art/art002.svg", "svg-icon-2"),
            ],
            // [
            //     'classes' => ['content' => 'pt-8 pb-2'],
            //     'content' => '<span class="menu-section text-muted text-uppercase fs-8 ls-1">Modules</span>',
            // ],
        ];

        if (!empty($userId)) {
            try {

                $userGroups = ConfigUser::find($userId)
                ->ConfigGroup()
                ->where('app_code', $app_code)
                ->pluck('config_groups.id');

                if ($userGroups->isNotEmpty()) {
                    $configMenus = ConfigMenu::query()
                    ->join('config_rights', 'config_menus.id', '=', 'config_rights.menu_id')
                    ->whereIn('config_rights.group_id', $userGroups)
                    ->where('config_menus.app_code', $app_code)
                    ->where('config_rights.trustee', 'like', '%R%')
                    ->select('config_menus.*', 'config_rights.menu_seq')
                    ->distinct()
                    ->orderBy('config_rights.menu_seq')
                    ->get();

                    if ($configMenus->isEmpty()) {
                        return $mainMenu;
                    }
                    $uniqueMenuHeaders = [];
                    // $groupedMenus = [];

                    // foreach ($configMenus as $configMenu) {
                    //     $appName = $configMenu->ConfigAppl->name;
                    //     $menuHeader = $configMenu->menu_header;

                    //     if (!isset($groupedMenus[$appName])) {
                    //         $groupedMenus[$appName] = [];
                    //     }

                    //     if (!isset($groupedMenus[$appName][$menuHeader])) {
                    //         $groupedMenus[$appName][$menuHeader] = [];
                    //     }

                    //     $groupedMenus[$appName][$menuHeader][] = $configMenu;
                    // }

                    // $mainMenu = [];

                    // foreach ($groupedMenus as $appName => $menuHeaders) {
                    //     $appMenuItem = [
                    //         'title' => $appName,
                    //         'icon' => [
                    //             'svg' => theme()->getSvgIcon("demo1/media/icons/duotune/abstract/abs027.svg", "svg-icon-2"),
                    //             'font' => '<i class="bi bi-person fs-2"></i>',
                    //         ],
                    //         'classes' => ['item' => 'menu-accordion show'],
                    //         'attributes' => [
                    //             'data-kt-menu-trigger' => 'click',
                    //         ],
                    //         'sub' => [
                    //             'class' => 'menu-sub-accordion menu-active-bg',
                    //             'items' => [],
                    //         ],
                    //     ];

                    //     foreach ($menuHeaders as $menuHeader => $configMenus) {
                    //         $menuItem = [
                    //             'title' => $menuHeader,
                    //             'icon' => [
                    //                 'svg' => theme()->getSvgIcon("demo1/media/icons/duotune/abstract/abs027.svg", "svg-icon-2"),
                    //                 'font' => '<i class="bi bi-person fs-2"></i>',
                    //             ],
                    //             'classes' => ['item' => 'menu-accordion'],
                    //             'attributes' => [
                    //                 'data-kt-menu-trigger' => 'click',
                    //             ],
                    //             'sub' => [
                    //                 'class' => 'menu-sub-accordion menu-active-bg',
                    //                 'items' => [],
                    //             ],
                    //         ];

                    //         foreach ($configMenus as $subMenu) {
                    //             $menuItem['sub']['items'][] = [
                    //                 'title' => $subMenu->menu_caption,
                    //                 'path' => $subMenu->link,
                    //                 'bullet' => '<span class="bullet bullet-dot"></span>',
                    //             ];
                    //         }

                    //         $appMenuItem['sub']['items'][] = $menuItem;
                    //     }

                    //     $mainMenu[] = $appMenuItem;
                    // }
                    foreach ($configMenus as $configMenu) {
                        if (!Route::has(str_replace('/', '.', $configMenu->menu_link))) {
                            continue;
                        }
                        $menuHeader = $configMenu->menu_header;
                        // Create and add the menu item
                        $menuItem = [
                            'title' => $configMenu->menu_caption,
                            'path' => $configMenu->menu_link,
                            'bullet' => '<span class="bullet bullet-dot"></span>',
                        ];

                        // Check if the menu header is not empty and not a string empty
                        if (trim($menuHeader) !== '') {
                            // If the menu header is not already added, create a new top-level menu item
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
                        } else {
                            $mainMenu[] = $menuItem;
                        }
                    }

                }
            } catch (QueryException $e) {
                // Handle the case where the config_menus table doesn't exist
                // You can log this error or handle it as needed
                // Example: Log::error('Database table not found: ' . $e->getMessage());
            }
        }

        return $mainMenu;
    }
}
