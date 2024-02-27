<?php

namespace App\Http\Livewire\Settings\ConfigConsts;

use Livewire\Component;
use App\Models\Settings\ConfigConst;
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
            $this->object = ConfigConst::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
            $this->resetForm();
        }
    }

    public function render()
    {
        return view('livewire.settings.config-consts.edit');
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
            'inputs.str2' => 'string|min:1|max:50'
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

    protected function populateDropdowns()
    {
        $this->refreshApplication();
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
                $this->object = ConfigConst::create($this->inputs);
            } elseif ($this->actionValue == 'Edit') {
                if ($this->object) {
                    $this->object->updateObject($this->VersioNumber);
                    $this->object->update($this->inputs);
                }
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
                'message' => Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['object' => $this->object->menu_caption, 'message' => $e->getMessage()])
            ]);
        }

        $this->dispatchBrowserEvent('refresh');
    }
}
