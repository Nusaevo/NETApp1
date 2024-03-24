<?php

namespace App\Http\Livewire\Component;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\Config\ConfigAppl;
use App\Models\Config\ConfigUser;
use App\Models\Config\ConfigGroup;
use Illuminate\Support\Facades\Session;

class ApplicationComponent extends Component
{
    public $applications;
    public $selectedApplication;

    public function mount()
    {
        $appIds = getAppIds();
        $applicationsData = ConfigAppl::whereIn('id', $appIds)->orderBy('id')->get();
        $this->applications = $applicationsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
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

    public function applicationChanged($selectedApplication)
    {
        Session::put('app_code', $selectedApplication);
        return redirect($selectedApplication ? '/' . $selectedApplication . '/Home' : '/');
    }

    public function render()
    {
        return view('livewire.component.application-component');
    }
}
