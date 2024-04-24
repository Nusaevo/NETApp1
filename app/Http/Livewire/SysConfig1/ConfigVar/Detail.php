<?php

namespace App\Http\Livewire\SysConfig1\ConfigVar;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigVar;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;
    public $languages;

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = ConfigVar::withTrashed()->find($this->objectIdValue);
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
            'inputs.var_group' => 'required|string|min:1|max:50',
            'inputs.seq' =>  'required',
            'inputs.default_value' => 'required|string|min:1|max:50',
            'inputs.descr' => 'string|min:1|max:200'
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input',
        'inputs.*'              => 'Input',
        'inputs.code'           => 'Var Code',
        'inputs.var_group'           => 'Var Group',
        'inputs.seq'      => 'Var Seq',
        'inputs.default_value'      => 'Default Value',
        'inputs.descr'      => 'Description',
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
    }

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
}
