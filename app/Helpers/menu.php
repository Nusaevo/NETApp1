<?php

use App\Models\ConfigMenu;
use Illuminate\Database\QueryException;

if (!function_exists('generateMenu')) {
    function generateMenu($authCode) {
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

        try {
            $appName = env('APP_NAME', 'DefaultAppName');
            $configMenus = ConfigMenu::where('appl_code', $appName)->get();

            if ($configMenus->isEmpty()) {
                return $mainMenu;
            }

            $uniqueMenuHeaders = [];

            foreach ($configMenus as $configMenu) {
                $menuHeader = $configMenu->menu_header;

                // Check permissions based on auth code
                $allowed = false;

                if ($authCode === 'andryhuang') {
                    $allowed = true; // Show all menus
                }

                if ($allowed) {
                    // Check if the menu header already exists in $uniqueMenuHeaders
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

        return $mainMenu;
    }
}
