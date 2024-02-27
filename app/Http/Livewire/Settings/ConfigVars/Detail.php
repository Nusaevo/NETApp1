<?php

namespace App\Http\Livewire\Settings\ConfigVars;

use Livewire\Component;
use App\Models\Settings\ConfigVar;
use App\Models\Settings\ConfigAppl;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;


class Detail extends Component
{
    public $object;
    public $VersioNumber;
    public $actionValue = 'Create';
    public $objectIdValue;
    public $inputs = [];
    public $applications;
    public $languages;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->actionValue = decryptWithSessionKey($action);
        $this->populateDropdowns();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = decryptWithSessionKey($objectId);
            $this->object = ConfigVar::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
        }
    }

    public function render()
    {
        return view('livewire.settings.config-vars.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.app_id' => 'required',
            'inputs.var_group' => 'required|string|min:1|max:50',
            'inputs.seq' =>  'required|integer|min:0|max:9999999999',
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

    protected function populateDropdowns()
    {
        $this->refreshApplication();
    }

    public function validateForm()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' =>"object", 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    public function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->reset('inputs');
            $this->populateDropdowns();
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number;
        }
    }

    public function Save()
    {
        $this->validateForm();

        try {
            $application = ConfigAppl::find($this->inputs['app_id']);
            $this->inputs['app_code'] = $application->code;
            if ($this->actionValue == 'Create') {
                $this->object = ConfigVar::create($this->inputs);
            } elseif ($this->actionValue == 'Edit') {
                if ($this->object) {
                    $this->object->updateObject($this->VersioNumber);
                    $this->object->update($this->inputs);
                }
            }
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.save', ['object' => $this->object->menu_caption])
            ]);
            $this->resetForm();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.save', ['object' => $this->object->menu_caption, 'message' => $e->getMessage()])
            ]);
        }
    }

    public function changeStatus()
    {
        try {
            $this->object->updateObject($this->VersioNumber);

            if ($this->object->deleted_at) {
                $this->object->deleted_at = null;
                $messageKey = 'generic.success.enable';
            } else {
                $this->object->delete();
                $messageKey = 'generic.success.disable';
            }

            $this->object->save();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get($messageKey, ['object' => ""])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['object' => $this->object->var_group, 'message' => $e->getMessage()])
            ]);
        }

        $this->dispatchBrowserEvent('refresh');
    }
}
