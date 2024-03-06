<?php

namespace App\Http\Livewire\Settings\ConfigApplications;

use Livewire\Component;
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
    public $group_codes;
    public $status = '';
    public $page = 'Config Application';

    public function mount($action, $objectId = null)
    {
        $this->actionValue = decryptWithSessionKey($action);
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = decryptWithSessionKey($objectId);
            $this->object = ConfigAppl::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
            $this->resetForm();
        }
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

    protected function validateForm()
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

    protected function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->reset('inputs');
            $this->object = new ConfigAppl();
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number ?? null;
        }
    }

    public function Save()
    {
        $this->validateForm();
        try {
            // if ($this->actionValue == 'Create') {
            //     $this->object = ConfigAppl::create($this->inputs);
            // } elseif ($this->actionValue == 'Edit') {
            //     if ($this->object) {
            //         $this->object->updateObject($this->VersioNumber);
            //         $this->object->update($this->inputs);
            //     }

            if ($this->object) {
                $this->object->updateObject($this->VersioNumber);
                $this->object->fill($this->inputs);
                $this->object->save();
            }

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.save', ['object' => $this->inputs['name']])
            ]);
            $this->resetForm();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.save', ['object' => $this->inputs['name'], 'message' => $e->getMessage()])
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
                'message' => Lang::get($messageKey, ['object' => $this->inputs['name']])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['object' => $this->inputs['name'], 'message' => $e->getMessage()])
            ]);
        }

        $this->dispatchBrowserEvent('refresh');
    }
}
