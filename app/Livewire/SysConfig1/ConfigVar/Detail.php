<?php

namespace App\Livewire\SysConfig1\ConfigVar;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigVar;
use App\Models\SysConfig1\ConfigAppl;

use App\Services\SysConfig1\ConfigService;

class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;
    public $languages;
    protected $configService;

    public $rules= [
        'inputs.app_id' => 'required',
        'inputs.var_group' => 'required|string|min:1|max:50',
        'inputs.seq' =>  'required',
        'inputs.default_value' => 'required|string|min:1|max:50',
        'inputs.descr' => 'string|min:1|max:200'
    ];

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

        $this->reset('inputs');
        $this->object = new ConfigVar();

        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications();

        if($this->isEditOrView())
        {
            $this->object = ConfigVar::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

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
}
