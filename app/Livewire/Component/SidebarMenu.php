<?php

namespace App\Livewire\Component;

use Livewire\Component;
use Illuminate\Support\Facades\{Session, Route, Auth, DB};


class SidebarMenu extends Component
{
    public $menus;
    public $baseRoute;

    public function mount()
    {
        $this->baseRoute = $this->getBaseRoute();
        $this->menus = $this->generateMenu(Auth::user()->code);
    }

    private function getBaseRoute()
    {
        $currentRoute = Route::currentRouteName();
        if (strpos($currentRoute, 'Detail/PrintPdf') !== false) {
            $routeParts = explode('/', $currentRoute);
            array_pop($routeParts); // Remove last part
            return implode('/', $routeParts);
        }
        return $currentRoute;
    }

    private function generateMenu($userId)
    {
        $app_id = Session::get('app_id');
        $app_code = Session::get('app_code');
        $mainMenu = $this->initializeMainMenu($app_code);

        if (empty($app_code) || empty($userId)) {
            return $mainMenu;
        }

        $trusteeData = $this->getTrusteeData($userId, $app_id);

        if (empty($trusteeData)) {
            return $mainMenu;
        }

        foreach ($trusteeData as $configMenu) {
            $this->addMenuItem($mainMenu, $configMenu);
        }

        return $mainMenu;
    }

    private function initializeMainMenu($app_code)
    {
        return [
            [
                'title' => 'Home',
                'path' => $app_code ? str_replace('/', '.', $app_code . '/Home') : '',
                'icon' => '<i class="bi bi-house"></i>',
            ],
        ];
    }

    private function getTrusteeData($userId, $app_id)
    {
        return DB::connection('SysConfig1')->select("
            SELECT * FROM trusteebyuser(?, ?)
        ", [$userId, $app_id]);
    }

    private function addMenuItem(&$mainMenu, $configMenu)
    {
        $route = str_replace('/', '.', $configMenu->menulink);

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
        return [
            'title' => $configMenu->menucaption,
            'path' => str_replace('/', '.', $configMenu->menulink),
            'bullet' => '<span class="bullet bullet-dot"></span>',
            'icon' => '<span class="bullet bullet-dot"></span>',
        ];
    }

    private function addMenuToHeader(&$mainMenu, $menuHeaderKey, $menuItem, $menuHeader)
    {
        if (!isset($mainMenu[$menuHeaderKey])) {
            $mainMenu[$menuHeaderKey] = [
                'title' => $menuHeader,
                'icon' => '<span class="bullet bullet-dot"></span>',
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

        $mainMenu[$menuHeaderKey]['sub']['items'][] = $menuItem;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
