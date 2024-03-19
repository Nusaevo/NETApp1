<?php

namespace App\Http\Livewire\Config\ConfigMenu;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\Config\ConfigMenu;
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

    protected function onLoad()
    {
        $this->object = ConfigMenu::withTrashed()->find($this->objectIdValue);
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
            'inputs.menu_header' => 'required|string|min:1|max:100',
            'inputs.menu_caption' => 'required|string|min:1|max:100',
            'inputs.link' => 'required|string|min:1|max:100',
            'inputs.seq' => 'required',
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('config.config_menus', 'code')->ignore($this->object ? $this->object->id : null),
            ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.code'           => 'Menu Code',
        'inputs.app_id'      => 'Menu Application',
        'inputs.menu_header'      => 'Menu Header',
        'inputs.sub_menu'      => 'Sub Menu',
        'inputs.menu_caption'      => 'Menu Caption',
        'inputs.seq'      => 'Menu Seq',
        'inputs.link'      => 'Menu link'
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
        $this->object = new ConfigMenu();
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
