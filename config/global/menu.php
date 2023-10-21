<?php

return array(
    // Documentation menu
    'documentation' => array(
        // Getting Started
        array(
            'heading' => 'Getting Started',
        ),

        // Overview
        array(
            'title' => 'Overview',
            'path'  => 'documentation/getting-started/overview',
        ),

        // Build
        array(
            'title' => 'Build',
            'path'  => 'documentation/getting-started/build',
        ),

        array(
            'title'      => 'Multi-demo',
            'attributes' => array("data-kt-menu-trigger" => "click"),
            'classes'    => array('item' => 'menu-accordion'),
            'sub'        => array(
                'class' => 'menu-sub-accordion',
                'items' => array(
                    array(
                        'title'  => 'Overview',
                        'path'   => 'documentation/getting-started/multi-demo/overview',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                    array(
                        'title'  => 'Build',
                        'path'   => 'documentation/getting-started/multi-demo/build',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                ),
            ),
        ),

        // File Structure
        array(
            'title' => 'File Structure',
            'path'  => 'documentation/getting-started/file-structure',
        ),

        // Customization
        array(
            'title'      => 'Customization',
            'attributes' => array("data-kt-menu-trigger" => "click"),
            'classes'    => array('item' => 'menu-accordion'),
            'sub'        => array(
                'class' => 'menu-sub-accordion',
                'items' => array(
                    array(
                        'title'  => 'SASS',
                        'path'   => 'documentation/getting-started/customization/sass',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                    array(
                        'title'  => 'Javascript',
                        'path'   => 'documentation/getting-started/customization/javascript',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                ),
            ),
        ),

        // Dark skin
        array(
            'title' => 'Dark Mode Version',
            'path'  => 'documentation/getting-started/dark-mode',
        ),

        // RTL
        array(
            'title' => 'RTL Version',
            'path'  => 'documentation/getting-started/rtl',
        ),

        // Troubleshoot
        array(
            'title' => 'Troubleshoot',
            'path'  => 'documentation/getting-started/troubleshoot',
        ),

        // Changelog
        array(
            'title'            => 'Changelog <span class="badge badge-changelog badge-light-danger bg-hover-danger text-hover-white fw-bold fs-9 px-2 ms-2">v' . theme()->getVersion() . '</span>',
            'breadcrumb-title' => 'Changelog',
            'path'             => 'documentation/getting-started/changelog',
        ),

        // References
        array(
            'title' => 'References',
            'path'  => 'documentation/getting-started/references',
        ),


        // Separator
        array(
            'custom' => '<div class="h-30px"></div>',
        ),

        // Configuration
        array(
            'heading' => 'Configuration',
        ),

        // General
        array(
            'title' => 'General',
            'path'  => 'documentation/configuration/general',
        ),

        // Menu
        array(
            'title' => 'Menu',
            'path'  => 'documentation/configuration/menu',
        ),

        // Page
        array(
            'title' => 'Page',
            'path'  => 'documentation/configuration/page',
        ),

        // Page
        array(
            'title' => 'Add NPM Plugin',
            'path'  => 'documentation/configuration/npm-plugins',
        ),


        // Separator
        array(
            'custom' => '<div class="h-30px"></div>',
        ),

        // General
        array(
            'heading' => 'General',
        ),

        // DataTables
        array(
            'title'      => 'DataTables',
            'classes'    => array('item' => 'menu-accordion'),
            'attributes' => array("data-kt-menu-trigger" => "click"),
            'sub'        => array(
                'class' => 'menu-sub-accordion',
                'items' => array(
                    array(
                        'title'  => 'Overview',
                        'path'   => 'documentation/general/datatables/overview',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                ),
            ),
        ),

        // Remove demos
        array(
            'title' => 'Remove Demos',
            'path'  => 'documentation/general/remove-demos',
        ),


        // Separator
        array(
            'custom' => '<div class="h-30px"></div>',
        ),

        // HTML Theme
        array(
            'heading' => 'HTML Theme',
        ),

        array(
            'title' => 'Components',
            'path'  => '//preview.keenthemes.com/metronic8/demo1/documentation/base/utilities.html',
        ),

        array(
            'title' => 'Documentation',
            'path'  => '//preview.keenthemes.com/metronic8/demo1/documentation/getting-started.html',
        ),
    ),

    // Main menu
    'main'          => array(
        //// Dashboard
        array(
            'title' => 'Home',
            'path'  => '',
            'icon'  => theme()->getSvgIcon("demo1/media/icons/duotune/art/art002.svg", "svg-icon-2"),
        ),

        //// Modules
        array(
            'classes' => array('content' => 'pt-8 pb-2'),
            'content' => '<span class="menu-section text-muted text-uppercase fs-8 ls-1">Modules</span>',
        ),

        // Master
        array(
            'title'      => 'Master',
            'icon'       => array(
                'svg'  => theme()->getSvgIcon("demo1/media/icons/duotune/abstract/abs027.svg", "svg-icon-2"),
                'font' => '<i class="bi bi-person fs-2"></i>',
            ),
            'classes'    => array('item' => 'menu-accordion'),
            'attributes' => array(
                "data-kt-menu-trigger" => "click",
            ),
            'sub'        => array(
                'class' => 'menu-sub-accordion menu-active-bg',
                'items' => array(
                    array(
                        'title'  => 'Customers',
                        'path'   => 'customer',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                    array(
                        'title'  => 'Suppliers',
                        'path'   => 'supplier',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    )
                ),
            ),
        ),
        // Stock Management
        // array(
        //     'title'      => 'Inventory',
        //     'icon'       => array(
        //         'svg'  => theme()->getSvgIcon("demo1/media/icons/duotune/general/gen017.svg", "svg-icon-2"),
        //         'font' => '<i class="bi bi-person fs-2"></i>',
        //     ),
        //     'classes'    => array('item' => 'menu-accordion'),
        //     'attributes' => array(
        //         "data-kt-menu-trigger" => "click",
        //     ),
        //     'sub'        => array(
        //         'class' => 'menu-sub-accordion menu-active-bg',
        //         'items' => array(
        //             array(
        //                 'title'  => 'Stock Produk',
        //                 'path'   => 'inventory/stock_opname',
        //                 'bullet' => '<span class="bullet bullet-dot"></span>',
        //             ),
        //             array(
        //                 'title'  => 'Harga Produk',
        //                 'path'   => 'item/price',
        //                 'bullet' => '<span class="bullet bullet-dot"></span>',
        //             )
        //             // array(
        //             //     'title'  => 'Transfer Item',
        //             //     'path'   => 'warehouse/adjustment',
        //             //     'bullet' => '<span class="bullet bullet-dot"></span>',
        //             // )
        //         ),
        //     ),
        // ),
        // Transaction
        // array(
        //     'title'      => 'Transaction',
        //     'icon'       => array(
        //         'svg'  => theme()->getSvgIcon("demo1/media/icons/duotune/general/gen032.svg", "svg-icon-2"),
        //         'font' => '<i class="bi bi-person fs-2"></i>',
        //     ),
        //     'classes'    => array('item' => 'menu-accordion'),
        //     'attributes' => array(
        //         "data-kt-menu-trigger" => "click",
        //     ),
        //     'sub'        => array(
        //         'class' => 'menu-sub-accordion menu-active-bg',
        //         'items' => array(
        //             array(
        //                 'title'  => 'Purchase Order',
        //                 'path'   => 'purchase_order',
        //                 'bullet' => '<span class="bullet bullet-dot"></span>',
        //             ),
        //             array(
        //                 'title'  => 'Sales Order',
        //                 'path'   => 'sales/order',
        //                 'bullet' => '<span class="bullet bullet-dot"></span>',
        //             ),
        //         ),
        //     ),
        // ),
        array(
            'title'      => 'Setting',
            'icon'       => array(
                'svg'  => theme()->getSvgIcon("demo1/media/icons/duotune/coding/cod001.svg", "svg-icon-2"),
                'font' => '<i class="bi bi-person fs-2"></i>',
            ),
            'classes'    => array('item' => 'menu-accordion'),
            'attributes' => array(
                "data-kt-menu-trigger" => "click",
            ),
            'sub'        => array(
                'class' => 'menu-sub-accordion menu-active-bg',
                'items' => array(
                    array(
                        'title'  => 'User',
                        'path'   => 'users',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                    array(
                        'title'  => 'Group',
                        'path'   => 'config_groups',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                    array(
                        'title'  => 'Application',
                        'path'   => 'store',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    ),
                    array(
                        'title'  => 'Menu',
                        'path'   => 'config_menus',
                        'bullet' => '<span class="bullet bullet-dot"></span>',
                    )
                ),
            ),
        ),



        // Report & Journal
        // array(
        //     'title'      => 'Report & Journal',
        //     'icon'       => array(
        //         'svg'  => theme()->getSvgIcon("demo1/media/icons/duotune/general/gen005.svg", "svg-icon-2"),
        //         'font' => '<i class="bi bi-person fs-2"></i>',
        //     ),
        //     'classes'    => array('item' => 'menu-accordion'),
        //     'attributes' => array(
        //         "data-kt-menu-trigger" => "click",
        //     ),
        //     'sub'        => array(
        //         'class' => 'menu-sub-accordion menu-active-bg',
        //         'items' => array(
        //             array(
        //                 'title'  => 'Journal',
        //                 'path'   => 'account/overview',
        //                 'bullet' => '<span class="bullet bullet-dot"></span>',
        //             ),
        //             array(
        //                 'title'  => 'Account Report',
        //                 'path'   => 'account/settings',
        //                 'bullet' => '<span class="bullet bullet-dot"></span>',
        //             ),
        //             array(
        //                 'title'  => 'Item Report',
        //                 'path'   => 'account/settings',
        //                 'bullet' => '<span class="bullet bullet-dot"></span>',
        //             )
        //         ),
        //     ),
        // ),
    ),

    // Horizontal menu
    'horizontal'    => array(
        // Dashboard
        array(
            'title'   => 'Dashboard',
            'path'    => '',
            'classes' => array('item' => 'me-lg-1'),
        ),
    ),
);
