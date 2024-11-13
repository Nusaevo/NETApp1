<?php

namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;
use Exception;
use Illuminate\Support\Facades\Session;


class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $applications;
    protected $configService;
    public $isSysConfig1;

    public $rules = [
        'inputs.app_id' => 'required',
        'inputs.code' => 'required|string|min:1|max:100',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->isSysConfig1 = Session::get('app_code') === 'SysConfig1';
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
        $this->applications = $this->configService->getActiveApplications(true);
        if($this->isEditOrView())
        {
            $this->object = ConfigSnum::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }

        if (!$this->isSysConfig1) {
            $this->inputs['app_id'] = Session::get('app_id');
        }
    }


    public function onReset()
    {
        $this->reset('inputs');
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
        if ($this->isSysConfig1) {
            $application = ConfigAppl::find($this->inputs['app_id']);
            $this->inputs['app_code'] = $application->code;
        }

        $this->object->fillAndSanitize($this->inputs);
        if($this->object->isDuplicateCode())
        {
            $this->addError('inputs.code', __('generic.error.duplicate_code'));
            throw new Exception(__('generic.error.duplicate_code'));
        }
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
