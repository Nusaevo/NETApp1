<?php

namespace App\Http\Livewire\Config\ConfigConst;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\Config\ConfigConst;
use App\Models\Config\ConfigAppl;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;
    public $status = '';

    protected function onLoad()
    {
        $this->object = ConfigConst::withTrashed()->find($this->objectIdValue);
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
            'inputs.const_group' => 'required|string|min:1|max:50',
            'inputs.seq' =>  'required|integer|min:0|max:9999999999',
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
        $this->object->fill($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
}
