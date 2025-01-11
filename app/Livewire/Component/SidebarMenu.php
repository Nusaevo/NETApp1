<?php
namespace App\Livewire\Component;

use Livewire\Component;
use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigUser;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $mainMenu = [];
        if (empty($app_code)) {
            return $mainMenu;
        }
        $mainMenu = [
            [
                'title' => 'Home',
                'path' => $app_code ? str_replace('/', '.', $app_code.'/Home') : '',
                'icon' => '<i class="bi bi-house"></i>',
            ],
        ];

        if (!empty($userId)) {
            // $configMenus = ConfigMenu::query()
            // ->join('config_rights', 'config_menus.id', '=', 'config_rights.menu_id')
            // ->joinSub(
            //     ConfigUser::find($userId)
            //         ->configGroup()
            //         ->where('app_code', $app_code)
            //         ->select('config_groups.id'),
            //     'user_groups',
            //     'config_rights.group_id',
            //     '=',
            //     'user_groups.id'
            // )
            // ->where('config_menus.app_code', $app_code)
            // ->where('config_rights.trustee', 'like', '%R%')
            // ->select('config_menus.*', 'config_rights.menu_seq')
            // ->distinct()
            // ->orderBy('config_rights.menu_seq')
            // ->get();
            $trusteeData = DB::connection('SysConfig1')->select("
                SELECT * FROM trusteebyuser(?, ?)
            ", [$userId, $app_id]);


            if (empty($trusteeData)) {
                return $mainMenu;
            }

            foreach ($trusteeData as $configMenu) {
                $route = str_replace('/', '.', $configMenu->menulink);
                if (!Route::has($route)) {
                    continue;
                }

                $menuHeader = $configMenu->menuhdr;
                $menuItem = [
                    'title' => $configMenu->menucaption,
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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
