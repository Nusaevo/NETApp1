<?php

use App\Models\ConfigMenu;

if (!function_exists('generateMenu')) {
    function generateMenu() {
        $mainMenu = [
            [
                'title' => 'Home',
                'path'  => '',
                'icon'  => theme()->getSvgIcon("demo1/media/icons/duotune/art/art002.svg", "svg-icon-2"),
            ],
            [
                'classes' => ['content' => 'pt-8 pb-2'],
                'content' => '<span class="menu-section text-muted text-uppercase fs-8 ls-1">Modules</span>',
            ],
        ];

        //Retrieve menu items from the 'config_menus' table using the ConfigMenu model
        $configMenus = ConfigMenu::all();
        $uniqueMenuHeaders = [];
        // Iterate through the $configMenus and create menu items
        foreach ($configMenus as $configMenu) {
            $menuHeader = $configMenu->menu_header;

            // Check if the menu header is unique and hasn't been added before
            if (!in_array($menuHeader, $uniqueMenuHeaders)) {
                $uniqueMenuHeaders[] = $menuHeader;

                $menuItem = [
                    'title' => $menuHeader,
                    'icon'  => [
                        'svg'  => theme()->getSvgIcon("demo1/media/icons/duotune/abstract/abs027.svg", "svg-icon-2"),
                        'font' => '<i class="bi bi-person fs-2"></i>',
                    ],
                    'classes'    => ['item' => 'menu-accordion'],
                    'attributes' => [
                        'data-kt-menu-trigger' => 'click',
                    ],
                    'sub' => [
                        'class' => 'menu-sub-accordion menu-active-bg',
                        'items' => [],
                    ],
                ];

                // Iterate through sub-menu items
                foreach ($configMenus as $subMenu) {
                    if ($subMenu->menu_header === $menuHeader) {
                        $menuItem['sub']['items'][] = [
                            'title'  => $subMenu->menu_caption,
                            'path'   => $subMenu->link,
                            'bullet' => '<span class="bullet bullet-dot"></span>',
                        ];
                    }
                }
                $mainMenu[] = $menuItem;
            }
        }
        return $mainMenu;
    }
}
