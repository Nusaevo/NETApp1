<?php

namespace App\Livewire\SysConfig1\ConfigConst;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;
use App\Services\Auth\OtpService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
class Detail extends BaseComponent
{
    #region Constant Variables
    public $applications; // Stores the list of applications
    public $status = '';
    public $isSysConfig1;
    protected $configService;
    public $application;
    public $selectedApplication; // Holds the currently selected application
    public $isEnabled;

    // Cookie management properties
    public $showCookieManagement = false;
    public $deviceTrustStatus = false;
    public $cookieInfo = [];
    protected $otpService;

    public $rules = [
        'inputs.const_group' => 'required|string|min:1|max:50',
        'inputs.seq' => 'required',
        'inputs.str1' => 'string|min:1|max:50',
        'inputs.str2' => 'string|min:1|max:50',
    ];

    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'clearDeviceTrust' => 'clearDeviceTrust',
        'setDeviceTrust' => 'setDeviceTrust',
    ];
    #endregion

    #region Lifecycle Methods
    protected function initializeServices()
    {
        // Initialize services
        if (!$this->configService) {
            $this->configService = new ConfigService();
        }

        if (!$this->otpService) {
            $this->otpService = new OtpService();
        }
    }
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        // Initialize services first
        $this->initializeServices();

        $this->isSysConfig1 = Session::get('app_code') === 'SysConfig1';
        // $this->customValidationAttributes = [
        //     'inputs' => 'Input',
        //     'inputs.*' => 'Input',
        //     'inputs.const_group' => 'Const Group',
        //     'inputs.seq' => 'Const Seq',
        //     'inputs.str1' => 'Str1',
        //     'inputs.str2' => 'Str2',
        // ];

        $this->applications = $this->configService->getActiveApplications(true);

        $this->isEnabled = $this->actionValue === 'Create' ? 'true' : 'false';


        if ($this->isEditOrView()) {
            // Fetch the application based on the additionalParam
            $this->application = ConfigAppl::find($this->additionalParam);
            $this->selectedApplication = $this->additionalParam;
            if ($this->application) {
                $this->object = new ConfigConst();
                $this->object->setConnection($this->application->code);
                $this->object = $this->object->withTrashed()->find($this->objectIdValue);
                $this->inputs = populateArrayFromModel($this->object);
            }
        } else {
            // For Create action, set up connection based on app type
            if (!$this->isSysConfig1) {
                $this->object->setConnection(Session::get('app_code'));
                // For non-SysConfig1, find application if additionalParam exists
                if ($this->additionalParam) {
                    $this->application = ConfigAppl::find($this->additionalParam);
                    $this->selectedApplication = $this->additionalParam;
                }
            }
        }

        // Check if we should show cookie management
        $this->checkCookieManagement();
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->selectedApplication = null;
        $this->object = new ConfigConst();
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods
    public function onValidateAndSave()
    {
        $this->object->fill($this->inputs);
        if ($this->isEditOrView()) {
            $this->object->setConnection($this->application->code);
        } else {
            if ($this->selectedApplication) {
                $this->application = ConfigAppl::find($this->selectedApplication);
                $this->object->setConnection($this->application->code);
            } elseif (!$this->isSysConfig1) {
                $this->object->setConnection(Session::get('app_code'));
            }
        }
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
    #endregion

    #region Components Events

    public function applicationChanged()
    {
        // Find the application by its ID
        $this->application = ConfigAppl::find($this->selectedApplication);

        if ($this->application) {
            $this->object->setConnection($this->application->code);
        }

        // Check cookie management after application change
        $this->checkCookieManagement();
    }

    /**
     * Check if cookie management should be shown
     */
    public function checkCookieManagement()
    {
        // Ensure services are initialized
        $this->initializeServices();

        $this->showCookieManagement = false;

        // Show cookie management for TrdTire1 application with specific groups
        if ($this->application && $this->application->code === 'TrdTire1') {
            $constGroup = $this->inputs['const_group'] ?? '';
            if (in_array($constGroup, ['OTP_EMAILS', 'EXCLUDED_OTP_GROUPS'])) {
                $this->showCookieManagement = true;
                $this->loadCookieInfo();
            }
        }
    }

    /**
     * Load current cookie information
     */
    public function loadCookieInfo()
    {
        // Ensure OtpService is initialized
        if (!$this->otpService) {
            $this->otpService = new OtpService();
        }

        // Force re-read from current request
        $this->deviceTrustStatus = $this->otpService->isDeviceTrusted();

        // Get fresh cookie value from request
        $cookieValue = request()->cookie(OtpService::DEVICE_TRUST_COOKIE);

        $this->cookieInfo = [
            'device_trust_cookie' => OtpService::DEVICE_TRUST_COOKIE,
            'is_trusted' => $this->deviceTrustStatus,
            'cookie_lifetime' => OtpService::COOKIE_LIFETIME_DAYS . ' hari',
            'cookie_value' => $cookieValue ?? 'Tidak ada',
        ];

        // Force Livewire to re-render the component
        $this->dispatch('$refresh');
    }

    /**
     * Clear device trust cookie
     */
    public function clearDeviceTrust()
    {
        // Ensure OtpService is initialized
        if (!$this->otpService) {
            $this->otpService = new OtpService();
        }

        $this->otpService->clearDeviceTrust();

        // Force immediate refresh with delay to allow cookie to be cleared
        $this->dispatch('cookie-updated', ['action' => 'cleared']);

        // Refresh cookie info after a small delay
        $this->loadCookieInfo();

        $this->dispatch('show-alert', [
            'type' => 'success',
            'message' => '✅ Device trust berhasil dihapus! Device akan memerlukan OTP pada login berikutnya.'
        ]);
    }

    /**
     * Set device as trusted
     */
    public function setDeviceTrust()
    {
        // Ensure OtpService is initialized
        if (!$this->otpService) {
            $this->otpService = new OtpService();
        }

        $this->otpService->setDeviceAsTrusted();

        // Force immediate refresh with delay to allow cookie to be set
        $this->dispatch('cookie-updated', ['action' => 'set']);

        // Refresh cookie info after a small delay
        $this->loadCookieInfo();

        $this->dispatch('show-alert', [
            'type' => 'success',
            'message' => '🛡️ Device berhasil ditandai sebagai trusted! OTP akan di-skip pada login berikutnya.'
        ]);
    }

    /**
     * Refresh cookie information
     */
    public function refreshCookieInfo()
    {
        // Force a complete reload of cookie info
        $this->loadCookieInfo();

        $statusText = $this->deviceTrustStatus ? 'Trusted ✅' : 'Not Trusted ❌';

        $this->dispatch('show-alert', [
            'type' => 'info',
            'message' => "🔄 Informasi cookie diperbarui. Status: {$statusText}"
        ]);
    }

    /**
     * Force refresh component to get latest cookie state
     */
    public function forceRefresh()
    {
        $this->loadCookieInfo();
        $this->render();
    }
    public function updated($propertyName)
    {
        // Check cookie management when const_group changes
        if ($propertyName === 'inputs.const_group') {
            $this->checkCookieManagement();
        }
    }
}
