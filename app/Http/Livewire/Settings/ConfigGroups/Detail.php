<?php

namespace App\Http\Livewire\Settings\ConfigGroups;

use Livewire\Component;
use App\Models\ConfigGroup;
use App\Models\ConfigAppl;
use Illuminate\Validation\Rule;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $VersioNumber;
    public $action = 'Create';
    public $objectId;
    public $inputs = [];
    public $applications;
    public $languages;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;

        $applicationsData = ConfigAppl::GetActiveData();

        $this->applications = $applicationsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
                'value' => $data->code,
            ];
        })->toArray();
        $this->inputs['applications'] = $this->applications[0]['value'];

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = ConfigGroup::withTrashed()->find($this->objectId);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs['code'] = $this->object->code;
            $this->inputs['name']  =  $this->object->name;
        } else {
            $this->object = new ConfigGroup();
        }
    }

    public function render()
    {
        return view('livewire.settings.config-groups.edit');
    }

    protected function rules()
    {
        $rules = [
            'inputs.applications' => 'required|string|min:1|max:50',
            'inputs.name' => 'required|string|min:1|max:100',
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('config_groups', 'code')
                    ->ignore($this->object->id)
                    ->where(function ($query) {
                    }),
            ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Group',
        'inputs.*'              => 'Input Group',
        'inputs.code'           => 'Group Code',
        'inputs.applications'      => 'Group Application Code',
        'inputs.name'      => 'Group Name'
    ];

    protected function populateObjectArray()
    {
        return [
            'code' => $this->inputs['code'],
            'appl_code' => $this->inputs['applications'],
            'name' => $this->inputs['name']
        ];
    }

    public function Create()
    {
        try {
            $this->validate();
            $objectData = $this->populateObjectArray();
            $this->object = ConfigGroup::create($objectData);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['name']])
            ]);
            $this->inputs = [];
            $this->inputs['applications'] = $this->applications[0]['value'];
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "User", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Edit()
    {
        try {
            $this->validate();

            if ($this->object) {
                $this->object->updateObject($this->VersioNumber);
                $objectData = $this->populateObjectArray();
                $this->object->update($objectData);
            }

            //DB::commit();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.update', ['object' => $this->object->name])
            ]);
            $this->VersioNumber = $this->object->version_number;
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Disable()
    {
        try {
            $this->object->updateObject($this->VersioNumber);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->dispatchBrowserEvent('refresh');
    }

    public function Enable()
    {
        try {
            $this->object->updateObject($this->VersioNumber);
            $this->object->deleted_at = null;
            $this->object->save();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.enable', ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.enable', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->dispatchBrowserEvent('refresh');
    }
}
