<?php

namespace App\Livewire\Component;

use Illuminate\Support\Facades\{Auth, Session};
use Livewire\Component;
use App\Models\SysConfig1\{ConfigAppl, ConfigUser};
use App\Services\SysConfig1\ConfigService;


class ApplicationComponent extends Component
{
    public $applications;
    public $selectedApplication;
    protected $listeners = [
        'configApplicationChanged'  => 'configApplicationChanged',
    ];
    public function mount()
    {
        $configService = new ConfigService();
        $applicationsData = $configService->getApp();
        $this->applications = $applicationsData->map(function ($data) {
            return [
                'label' => $data->name,
                'value' => $data->code,
            ];
        })->toArray();

        $sessionValue = Session::get('app_code');
        if (!empty($sessionValue)) {
            $this->selectedApplication = $sessionValue;
        } else {
            if (!empty($applicationsData[0])) {
                Session::put('app_code', $applicationsData[0]->code);
                Session::put('database', $applicationsData[0]->db_name);
            }
        }
    }

    public function configApplicationChanged($selectedApplication)
    {
        $selectedApp = ConfigAppl::where('code', $selectedApplication)->first();

        if ($selectedApp) {
            Session::put('app_id', $selectedApp->id);
            Session::put('app_code', $selectedApplication);
            Session::put('database', $selectedApp->db_name);
            $user = ConfigUser::find(Auth::id());

            if ($user) {
                $groupCodes = $user->getGroupCodesBySessionAppCode();
                Session::put('group_codes', $groupCodes);
            }
            return redirect('/' . $selectedApplication . '/Home');
        }

        return redirect('/');
    }



    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
