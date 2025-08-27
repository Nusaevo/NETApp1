<?php

namespace App\Livewire\Component;

use Livewire\Component;
use Illuminate\Support\Facades\{Session, Route, Auth, DB};

class BootstrapSidebarMenu extends Component
{
    public $menus;
    public $baseRoute;

    public function mount()
    {
        // Clear any cached menu data to ensure fresh generation
        $this->menus = [];
        
        $this->baseRoute = $this->getBaseRoute();
        $this->menus = $this->generateMenu(Auth::user()->code);
        
        if (count($this->menus) <= 1) {
            abort(403, 'Anda tidak memiliki izin akses. Mohon hubungi administrator.');
        }
        
        // Log current application for debugging
        \Log::info('BootstrapSidebarMenu: Generated menu for app', [
            'app_code' => Session::get('app_code'),
            'app_id' => Session::get('app_id'),
            'menu_count' => count($this->menus)
        ]);
    }

    private function getBaseRoute()
    {
        $currentRoute = Route::currentRouteName();
        if (strpos($currentRoute, 'Detail/PrintPdf') !== false) {
            $routeParts = explode('/', $currentRoute);
            array_pop($routeParts);
            return implode('/', $routeParts);
        }
        return $currentRoute;
    }

    private function generateMenu($userId)
    {
        // Get fresh session data
        $app_id = Session::get('app_id');
        $app_code = Session::get('app_code');
        
        \Log::info('BootstrapSidebarMenu: Generating menu', [
            'user_id' => $userId,
            'app_code' => $app_code,
            'app_id' => $app_id
        ]);
        
        $mainMenu = $this->initializeMainMenu($app_code);

        if (empty($app_code) || empty($userId)) {
            \Log::warning('BootstrapSidebarMenu: Missing app_code or user_id, returning basic menu');
            return $mainMenu;
        }

        $trusteeData = $this->getTrusteeData($userId, $app_id);

        if (empty($trusteeData)) {
            \Log::info('BootstrapSidebarMenu: No trustee data found, returning basic menu');
            return $mainMenu;
        }

        \Log::info('BootstrapSidebarMenu: Found trustee data', ['count' => count($trusteeData)]);

        foreach ($trusteeData as $configMenu) {
            $this->addMenuItem($mainMenu, $configMenu);
        }

        return $mainMenu;
    }

    private function initializeMainMenu($app_code)
    {
        $mainMenu = [
            [
                'title' => 'Home',
                'path' => $app_code ? str_replace('/', '.', $app_code . '/Home') : '',
                'icon' => 'bi-house-door',
                'type' => 'single'
            ],
        ];

        // Add Settings menu for authenticated users
        if (Auth::check()) {
            $mainMenu['settings'] = [
                'title' => 'Settings',
                'icon' => 'bi-gear',
                'type' => 'accordion',
                'items' => [
                    [
                        'title' => 'My Profile',
                        'path' => 'user-management.users.show',
                        'params' => ['user' => Auth::id()],
                        'icon' => 'bi-person',
                        'type' => 'single'
                    ],
                    [
                        'title' => 'Account Settings',
                        'path' => 'user-management.users.edit',
                        'params' => ['user' => Auth::id()],
                        'icon' => 'bi-gear',
                        'type' => 'single'
                    ],
                    [
                        'title' => 'User Management',
                        'path' => 'user-management.users.index',
                        'params' => [],
                        'icon' => 'bi-people',
                        'type' => 'single'
                    ],
                ]
            ];
        }

        return $mainMenu;
    }

    private function getTrusteeData($userId, $app_id)
    {
        return DB::connection('SysConfig1')->select("
            SELECT * FROM trusteebyuser(?, ?)
        ", [$userId, $app_id]);
    }

    private function addMenuItem(&$mainMenu, $configMenu)
    {
        $raw = $configMenu->menulink;
        $path = parse_url($raw, PHP_URL_PATH) ?: $raw;
        $route = str_replace('/', '.', trim($path, '/'));

        if (!Route::has($route)) {
            return;
        }

        $menuItem = $this->createMenuItem($configMenu);

        if (trim($configMenu->menuhdr) !== '') {
            $menuHeaderKey = strtolower(trim($configMenu->menuhdr));
            $this->addMenuToHeader($mainMenu, $menuHeaderKey, $menuItem, $configMenu->menuhdr);
        } else {
            $mainMenu[] = $menuItem;
        }
    }

    private function createMenuItem($configMenu)
    {
        $link = $configMenu->menulink;
        [$rawPath, $query] = array_pad(explode('?', $link, 2), 2, null);
        $routeName = str_replace('/', '.', $rawPath);

        $params = [];
        if ($query) {
            parse_str($query, $params);
        }

        return [
            'title' => $configMenu->menucaption,
            'path' => $routeName,
            'params' => $params,
            'icon' => $this->getMenuIcon($configMenu->menucaption),
            'type' => 'single'
        ];
    }

    private function addMenuToHeader(&$mainMenu, $menuHeaderKey, $menuItem, $menuHeader)
    {
        if (!isset($mainMenu[$menuHeaderKey])) {
            $mainMenu[$menuHeaderKey] = [
                'title' => $menuHeader,
                'icon' => $this->getMenuIcon($menuHeader),
                'type' => 'accordion',
                'items' => [],
            ];
        }

        $mainMenu[$menuHeaderKey]['items'][] = $menuItem;
    }

    private function getMenuIcon($title)
    {
        // Map menu titles to Bootstrap icons
        $iconMap = [
            'Home' => 'bi-house-door',
            'Serial' => 'bi-list-ol',
            'Application' => 'bi-app-indicator',
            'Menu' => 'bi-menu-button-wide',
            'User' => 'bi-people',
            'Group' => 'bi-collection',
            'Constant' => 'bi-gear',
            'Variant' => 'bi-layers',
            'Master' => 'bi-database',
            'Transaction' => 'bi-receipt',
            'Report' => 'bi-file-earmark-bar-graph',
            'Setting' => 'bi-gear',
            'System' => 'bi-cpu',
            'Config' => 'bi-sliders',
        ];

        // Check for exact match first
        if (isset($iconMap[$title])) {
            return $iconMap[$title];
        }

        // Check for partial matches
        $titleLower = strtolower($title);
        foreach ($iconMap as $key => $icon) {
            if (strpos($titleLower, strtolower($key)) !== false) {
                return $icon;
            }
        }

        // Default icon
        return 'bi-circle';
    }

    public function render()
    {
        return view('livewire.component.bootstrap-sidebar-menu');
    }
}
