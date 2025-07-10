<?php

namespace App\Livewire\SysConfig1\ConfigConst;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;
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

    public $rules = [
        'inputs.const_group' => 'required|string|min:1|max:50',
        'inputs.seq' => 'required',
        'inputs.str1' => 'required|string|min:1|max:50',
        'inputs.str2' => 'string|min:1|max:50',
    ];

    protected $listeners = [
        'changeStatus' => 'changeStatus',
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->isSysConfig1 = Session::get('app_code') === 'SysConfig1';
        $this->customValidationAttributes = [
            'inputs' => 'Input',
            'inputs.*' => 'Input',
            'inputs.const_group' => 'Const Group',
            'inputs.seq' => 'Const Seq',
            'inputs.str1' => 'Str1',
            'inputs.str2' => 'Str2',
        ];
        $this->configService = new ConfigService();
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
    }
    #endregion
}
