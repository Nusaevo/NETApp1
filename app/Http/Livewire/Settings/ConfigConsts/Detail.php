<?php

namespace App\Http\Livewire\Settings\ConfigConsts;

use Livewire\Component;
use App\Models\ConfigConst;
use App\Models\ConfigAppl;
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
        $this->actionValue = Crypt::decryptString($action);
        $this->refreshApplication();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
            $this->object = ConfigConst::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
            $this->object = new ConfigConst();
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
            'inputs.str1' => 'required|string|min:1|max:50',
            'inputs.str2' => 'string|min:1|max:50'
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.code'           => 'Menu Code',
        'inputs.app_id'      => 'Menu Application',
        'inputs.str1'      => 'Str1',
        'inputs.str2'      => 'Str2'
    ];

    public function refreshApplication()
    {
        $applicationsData = ConfigAppl::GetActiveData();
        if (!$applicationsData->isEmpty()) {
            $this->applications = $applicationsData->map(function ($data) {
                return [
                    'label' => $data->code . ' - ' . $data->name,
                    'value' => $data->id,
                ];
            })->toArray();

            $this->inputs['app_id'] = $this->applications[0]['value'];
        } else {
            $this->applications = [];
            $this->inputs['app_id'] = null;
        }
    }

    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        $application = ConfigAppl::find($this->inputs['app_id']);
        $objectData['app_code'] = $application->code;
        $objectData['group_id'] = 1;
        $objectData['user_id'] = 1;
        return $objectData;
    }

    public function validateForms()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' =>"", 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    public function Create()
    {
        $this->validateForms();
        try {
            $objectData = $this->populateObjectArray();
            $this->object = ConfigConst::create($objectData);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => ""])
            ]);
            $this->reset('inputs');
            $this->refreshApplication();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Edit()
    {
        $this->validateForms();
        try {
            if ($this->object) {
                $this->object->updateObject($this->VersioNumber);
                $objectData = $this->populateObjectArray();
                $this->object->update($objectData);
            }
            //DB::commit();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.update', ['object' => ""])
            ]);
            $this->VersioNumber = $this->object->version_number;
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "", 'message' => $e->getMessage()])
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
                'message' => Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['object' => $this->object->menu_caption, 'message' => $e->getMessage()])
            ]);
        }

        $this->dispatchBrowserEvent('refresh');
    }
}
