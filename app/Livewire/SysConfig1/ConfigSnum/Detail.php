<?php

namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\{ConfigSnum, ConfigAppl};
use App\Services\SysConfig1\ConfigService;
use Illuminate\Support\Facades\Session;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $applications; // Stores the list of applications
    public $status = '';
    public $isSysConfig1;
    protected $configService;
    public $application; // Selected application
    public $selectedApplication; // Holds the currently selected application
    public $isEnabled;

    public $rules = [
        'inputs.code' => 'required|string|min:1|max:50',
        'inputs.last_cnt' => 'required|integer',
        'inputs.descr' => 'string|min:1|max:255',
    ];

    protected $listeners = [
        'applicationChanged' => 'onApplicationChanged',
    ];
    #endregion

    #region Populate Data Methods
    protected function onPreRender()
    {
        $this->isSysConfig1 = Session::get('app_code') === 'SysConfig1';
        $this->customValidationAttributes = [
            'inputs' => 'Input',
            'inputs.code' => 'Code',
            'inputs.last_cnt' => 'Last Count',
            'inputs.descr' => 'Description',
        ];
        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications(true);

        $this->isEnabled = $this->actionValue === 'Create' ? 'true' : 'false';

        if ($this->isEditOrView()) {
            // Fetch the application based on the additionalParam
            $this->application = ConfigAppl::find($this->additionalParam);
            $this->selectedApplication = $this->additionalParam;

            if ($this->application) {
                $this->object = new ConfigSnum();
                $this->object->setConnection($this->application->code);
                $this->object = $this->object->withTrashed()->find($this->objectIdValue);
                $this->inputs = populateArrayFromModel($this->object);
            }
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->selectedApplication = null;
        $this->object = new ConfigSnum();
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
        // if (!$this->application) {
        //     throw new \Exception("Application not selected. Please select an application.");
        // }

        $this->object->fillAndSanitize($this->inputs);
        if ($this->isEditOrView()) {
            $this->object->setConnection($this->application->code);
        }
        $this->object->save();
    }
    #endregion

    #region Component Events
    public function applicationChanged($applicationId)
    {
        // Find the application by its ID
        $this->application = ConfigAppl::find($applicationId);
        $this->selectedApplication = $applicationId;

        if ($this->application) {
            $this->object->setConnection($this->application->code);
        }
    }
    #endregion
}
