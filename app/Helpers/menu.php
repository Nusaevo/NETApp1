<?php

use App\Models\Settings\ConfigMenu;
use App\Models\Settings\ConfigUser;
use App\Models\Settings\ConfigGroup;
use App\Models\Settings\ConfigRight;
use Illuminate\Database\QueryException;

if (!function_exists('generateMenu')) {
    function generateMenu($authCode)
    {
        $mainMenu = [
            [
                'title' => 'Home',
                'path' => '',
                'icon' => theme()->getSvgIcon("demo1/media/icons/duotune/art/art002.svg", "svg-icon-2"),
            ],
            [
                'classes' => ['content' => 'pt-8 pb-2'],
                'content' => '<span class="menu-section text-muted text-uppercase fs-8 ls-1">Modules</span>',
            ],
        ];

        $userId = Auth::check() ? Auth::user()->id : '';
        if (!empty($userId)) {
            try {
                $userGroups = ConfigUser::find($userId)->ConfigGroup()->pluck('config_groups.id');

                if ($userGroups) {
                    $menuIds = ConfigRight::whereIn('group_id', $userGroups)->pluck('menu_id');
                    $configMenus = ConfigMenu::whereIn('id', $menuIds)->get()->sortBy('seq');
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
                        $menuHeader = $configMenu->menu_header;

                        if (!in_array($menuHeader, $uniqueMenuHeaders)) {
                            $uniqueMenuHeaders[] = $menuHeader;

                            // Create and add the menu item
                            $menuItem = [
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

                            foreach ($configMenus as $subMenu) {
                                if ($subMenu->menu_header === $menuHeader) {
                                    $menuItem['sub']['items'][] = [
                                        'title' => $subMenu->menu_caption,
                                        'path' => $subMenu->link,
                                        'bullet' => '<span class="bullet bullet-dot"></span>',
                                    ];
                                }
                            }

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
