<?php
namespace App\Livewire\Component;

use Livewire\Component;
use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigUser;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

class SidebarMenu extends Component
{
    public $menus;
    public $baseRoute;

    public function mount()
    {
        $this->baseRoute = $this->getBaseRoute();
        $this->menus = $this->generateMenu(Auth::id());
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
        $app_code = Session::get('app_code');
        $mainMenu = [
            [
                'title' => 'Home',
                'path' => $app_code ? str_replace('/', '.', $app_code.'/Home') : '',
                'icon' => '<i class="bi bi-house"></i>',
            ],
        ];

        if (!empty($userId)) {
            $configMenus = ConfigMenu::query()
                ->join('config_rights', 'config_menus.id', '=', 'config_rights.menu_id')
                ->joinSub(
                    ConfigUser::find($userId)
                        ->configGroup()
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

            if ($configMenus->isEmpty()) {
                return $mainMenu;
            }

            foreach ($configMenus as $configMenu) {
                $route = str_replace('/', '.', $configMenu->menu_link);
                if (!Route::has($route)) {
                    continue;
                }
                $menuHeader = $configMenu->menu_header;
                $menuItem = [
                    'title' => $configMenu->menu_caption,
                    'path' => $route,
                    'bullet' => '<span class="bullet bullet-dot"></span>',
                    'icon' => '<span class="bullet bullet-dot"></span>',
                ];

                if (trim($menuHeader) !== '') {
                    $menuHeaderKey = strtolower(trim($menuHeader));

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
                } else {
                    $mainMenu[] = $menuItem;
                }

            }
        }

        return $mainMenu;
    }

    public function render()
    {
        return view('livewire.component.sidebar-menu');
    }
}
