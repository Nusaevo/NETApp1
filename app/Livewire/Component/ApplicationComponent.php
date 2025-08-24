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
            if (!empty($applicationsData) && $applicationsData->count() > 0) {
                $firstApp = $applicationsData->first();
                $this->selectedApplication = $firstApp->code;
                Session::put('app_code', $firstApp->code);
                Session::put('database', $firstApp->db_name);
                Session::put('app_id', $firstApp->id);
            }
        }
    }

    public function configApplicationChanged($selectedApplication)
    {
        try {
            \Log::info('ApplicationComponent: Switch requested for app: ' . $selectedApplication);

            $selectedApp = ConfigAppl::where('code', $selectedApplication)->first();

            if ($selectedApp) {
                \Log::info('ApplicationComponent: Found app data', ['app' => $selectedApp->toArray()]);

                // Update session values
                Session::put('app_id', $selectedApp->id);
                Session::put('app_code', $selectedApplication);
                Session::put('database', $selectedApp->db_name);

                // Update component property
                $this->selectedApplication = $selectedApplication;

                $user = ConfigUser::find(Auth::id());
                if ($user) {
                    $groupCodes = $user->getGroupCodesBySessionAppCode();
                    Session::put('group_codes', $groupCodes);
                    \Log::info('ApplicationComponent: Updated group codes', ['codes' => $groupCodes]);
                }

                // Save session to ensure it's persisted
                Session::save();

                \Log::info('ApplicationComponent: Session updated, doing direct redirect');

                // Build the home path based on the application
                $homePath = '/' . $selectedApplication . '/Home';

                \Log::info('ApplicationComponent: Built home path: ' . $homePath);

                // Direct redirect without JavaScript event - for testing
                return redirect()->to($homePath);

            } else {
                \Log::error('ApplicationComponent: Application not found: ' . $selectedApplication);

                $this->dispatch('notify-swal', [
                    'type' => 'error',
                    'message' => 'Application not found: ' . $selectedApplication
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('ApplicationComponent: Switch error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('notify-swal', [
                'type' => 'error',
                'message' => 'Failed to switch application: ' . $e->getMessage()
            ]);
        }
    }



    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
