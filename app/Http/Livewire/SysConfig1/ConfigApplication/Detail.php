<?php

namespace App\Http\Livewire\SysConfig1\ConfigApplication;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Validation\Rule;

class Detail extends BaseComponent
{
    public $inputs = [];
    public $group_codes;

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = ConfigAppl::withTrashed()->find($this->objectIdValue);
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
            'inputs.name' => 'required|string|min:1|max:100',
            'inputs.version' => 'string|min:1|max:15',
            'inputs.descr' => 'string|min:1|max:500',
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('sys-config1.config_appls', 'code')->ignore($this->object ? $this->object->id : null),
            ],

        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Application',
        'inputs.*'              => 'Input Application',
        'inputs.name'           => 'Application Name',
        'inputs.code'      => 'Application Code',
        'inputs.version' => 'Application Version',
        'inputs.descr' => 'Description',
    ];

    protected function onPopulateDropdowns()
    {

    }

    protected function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigAppl();
    }

    public function onValidateAndSave()
    {
        $this->object->fill($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
}
