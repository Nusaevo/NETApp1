<?php

namespace App\Livewire\Component;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Support\Facades\Session;
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
        $appIds = $configService->getAppIds();
        $applicationsData = ConfigAppl::whereIn('id', $appIds)->orderBy('id')->get();
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
                $this->selectedApplication = $applicationsData[0]->code;
                Session::put('app_code', $this->selectedApplication);
            }
        }
    }

    public function configApplicationChanged($selectedApplication)
    {
        Session::put('app_code', $selectedApplication);
        return redirect($selectedApplication ? '/' . $selectedApplication . '/Home' : '/');
    }

    public function render()
    {
        return view('livewire.component.application-component');
    }
}
