<?php

namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Lang;
use Exception;
use Illuminate\Support\Facades\DB;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = ConfigSnum::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.app_id' => 'required',
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('sys-config1.config_snums', 'code')->ignore($this->object ? $this->object->id : null),
            ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input',
        'inputs.*'              => 'Input',
        'inputs.code'           => 'Code',
        'inputs.last_cnt'      => 'Last Count',
        'inputs.wrap_low'      => 'Wrap Low',
        'inputs.wrap_high'      => 'Wrap High',
        'inputs.step_cnt'      => 'Step Count',
        'inputs.descr'      => 'Description'
    ];

    public function refreshApplication()
    {
        $applicationsData = ConfigAppl::GetActiveData();
        $this->applications = $applicationsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
        $this->inputs['app_id'] = null;
    }

    protected function onPopulateDropdowns()
    {
        $this->refreshApplication();
    }

    protected function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigSnum();
    }

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
