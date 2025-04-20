<?php

namespace App\Livewire\SysConfig1\ConfigVar;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\{ConfigVar, ConfigAppl};
use App\Services\SysConfig1\ConfigService;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $applications;
    public $languages;
    protected $configService;

    public $rules= [
        'inputs.code' => 'required|string|min:1|max:100',
        'inputs.app_id' => 'required',
        'inputs.var_group' => 'required|string|min:1|max:50',
        'inputs.seq' =>  'required',
        'inputs.default_value' => 'required|string|min:1|max:50',
        'inputs.descr' => 'string|min:1|max:200'
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
            'inputs.code'           => 'Var Code',
            'inputs.var_group'           => 'Var Group',
            'inputs.seq'      => 'Var Seq',
            'inputs.default_value'      => 'Default Value',
            'inputs.descr'      => 'Description',
        ];


        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications(true);

        if($this->isEditOrView())
        {
            $this->object = ConfigVar::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->inputs['app_id'] = null;
        $this->object = new ConfigVar();
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
        $application = ConfigAppl::find($this->inputs['app_id']);
        $this->inputs['app_code'] = $application->code;

        $this->object->fill($this->inputs);
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
