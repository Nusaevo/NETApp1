<?php

namespace App\Livewire\SysConfig1\ConfigConst;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Facades\DB;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;
    public $status = '';

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = ConfigConst::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
    }

    public function render()
    {
        return view($this->renderRoute)->layout('layout.app');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.app_id' => 'required',
            'inputs.const_group' => 'required|string|min:1|max:50',
            'inputs.seq' =>  'required',
            'inputs.str1' => 'required|string|min:1|max:50',
            'inputs.str2' => 'string|min:1|max:50',
            // 'inputs.code' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:50',
            //     Rule::unique('config.config_appls', 'code')->ignore($this->object ? $this->object->id : null),
            // ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input',
        'inputs.*'              => 'Input',
        'inputs.code'           => 'Const Code',
        'inputs.const_group'           => 'Const Group',
        'inputs.seq'      => 'Const Seq',
        'inputs.app_id'      => 'Const Application',
        'inputs.str1'      => 'Str1',
        'inputs.str2'      => 'Str2'
    ];

    public function refreshApplication()
    {
        $applicationsData = ConfigAppl::GetActiveData()->pluck('name', 'id');

        $this->applications = $applicationsData->map(function ($name, $id) {
            return [
                'label' => $id . ' - ' . $name,
                'value' => $id,
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
        $this->object = new ConfigConst();
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
