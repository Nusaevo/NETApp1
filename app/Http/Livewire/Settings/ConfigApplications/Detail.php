<?php

namespace App\Http\Livewire\Settings\ConfigApplications;

use App\Http\Livewire\Components\BaseComponent;
use App\Models\Settings\ConfigAppl;
use Illuminate\Validation\Rule;
use Lang;
use Exception;
use DB;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $group_codes;

    protected function onLoad()
    {
        $this->object = ConfigAppl::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
    }

    public function render()
    {
        return view('livewire.settings.config-applications.edit');
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
                Rule::unique('config.config_appls', 'code')->ignore($this->object ? $this->object->id : null),
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
