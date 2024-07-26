<?php

namespace App\Livewire\SysConfig1\ConfigMenu;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
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
        $this->object = ConfigMenu::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
    }

    public function render()
    {
        return view($this->renderRoute)->layout('layout.app');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    public  $rules = [
            'inputs.app_id' => 'required',
            'inputs.menu_caption' => 'required|string|min:1|max:100',
            'inputs.menu_link' => 'required|string|min:1|max:100',
            // 'inputs.code' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:50',
            //     Rule::unique('sys-config1.config_menus', 'code')->ignore($this->object ? $this->object->id : null),
            // ],
        ];

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.code'           => 'Menu Code',
        'inputs.app_id'      => 'Menu Application',
        'inputs.menu_header'      => 'Menu Header',
        'inputs.menu_caption'      => 'Menu Caption',
        'inputs.menu_link'      => 'Menu link'
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
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
}
