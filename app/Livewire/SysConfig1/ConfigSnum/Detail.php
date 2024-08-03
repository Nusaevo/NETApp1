<?php

namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;
    protected $configService;

    public $rules = [
        'inputs.app_id' => 'required',
        'inputs.code' => 'required',
    ];

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

        $this->reset('inputs');
        $this->object = new ConfigSnum();

        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications();
        if($this->isEditOrView())
        {
            $this->object = ConfigSnum::withTrashed()->find($this->objectIdValue);
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
