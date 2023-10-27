<?php

namespace App\Http\Livewire\Settings\ConfigApplications;

use Livewire\Component;
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
    public $group_codes;
    public $languages;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = ConfigAppl::withTrashed()->find($this->objectId);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs['code'] = $this->object->code;
            $this->inputs['name'] = $this->object->name;
            $this->inputs['descr'] = $this->object->descr;
            $this->inputs['version'] = $this->object->version;
        } else {
            $this->object = new ConfigAppl();
        }
    }

    public function render()
    {
        return view('livewire.settings.config-applications.edit');
    }

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
                Rule::unique('config_appls', 'code')
                    ->ignore($this->object->id)
                    ->where(function ($query) {
                    }),
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

    protected function populateObjectArray()
    {
        return [
            'name' => $this->inputs['name'],
            'code' => $this->inputs['code'],
            'version' => $this->inputs['version'] ?? "",
            'descr' => $this->inputs['descr'] ?? "",
        ];
    }

    public function Create()
    {
        try {
            $this->validate();
            $objectData = $this->populateObjectArray();
            $this->object = ConfigAppl::create($objectData);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['name']])
            ]);
            $this->inputs = [];
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
