<?php

namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;
use Exception;


class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $applications;
    protected $configService;

    public $rules = [
        'inputs.app_id' => 'required',
        'inputs.code' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs'                => 'Input',
            'inputs.*'              => 'Input',
            'inputs.code'           => 'Code',
            'inputs.last_cnt'      => 'Last Count',
            'inputs.wrap_low'      => 'Wrap Low',
            'inputs.wrap_high'      => 'Wrap High',
            'inputs.step_cnt'      => 'Step Count',
            'inputs.descr'      => 'Description'
        ];

        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications();
        if($this->isEditOrView())
        {
            $this->object = ConfigSnum::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }
    }


    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigSnum();
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        $application = ConfigAppl::find($this->inputs['app_id']);
        $this->inputs['app_code'] = $application->code;
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }

    #endregion

    #region Component Events


    #endregion
}
