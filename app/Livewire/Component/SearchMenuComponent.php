<?php

namespace App\Livewire\Component;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigUser;
use Illuminate\Support\Facades\Session;

class SearchMenuComponent extends Component
{
    public $searchTerm = '';
    public $results = [];
    public $userId;
    public $appCode;

    public function mount()
    {
        $this->userId = Auth::id();
        $this->appCode = Session::get('app_code');
    }

    public function onSearchChanged()
    {
        if (!empty($this->searchTerm)) {
            $this->results = $this->fetchConfigMenus($this->userId, $this->appCode, $this->searchTerm);
        } else {
            $this->results = [];
        }
    }

    public function fetchConfigMenus($userId, $app_code, $searchTerm)
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
            ->where(function($query) use ($searchTerm) {
                $query->whereRaw('UPPER(config_menus.menu_caption) like ?', ['%' . strtoupper($searchTerm) . '%']);
            })
            ->select('config_menus.*', 'config_rights.menu_seq')
            ->distinct()
            ->orderBy('config_rights.menu_seq')
            ->get();
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));

        return view($renderRoute, [
            'results' => $this->results,
        ]);
    }
}
