<?php

namespace App\Http\Livewire\Settings\ConfigGroups;

use Livewire\Component;
use App\Models\Settings\ConfigGroup;
use App\Models\Settings\ConfigAppl;
use App\Models\Settings\ConfigUser;
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
    public $status = '';
    public $applications;
    public $users;

    public function mount($action, $objectId = null)
    {
        $this->actionValue = Crypt::decryptString($action);

        $this->refreshApplication();
        $this->refreshUser();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
            $this->object = ConfigGroup::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
        }
    }

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

    public function refreshUser()
    {
        $usersData = ConfigUser::GetActiveData();
        if (!$usersData->isEmpty()) {
            $this->users = $usersData->map(function ($data) {
                return [
                    'label' => $data->code . ' - ' . $data->name,
                    'value' => $data->id,
                ];
            })->toArray();
            $this->inputs['user_id']='';
        } else {
            $this->users = [];
            $this->inputs['user_id'] = null;
        }
    }


    public function render()
    {
        return view('livewire.settings.config-groups.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.app_id' =>  'required',
            'inputs.user_id' =>  'required',
            'inputs.name' => 'required|string|min:1|max:100',
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Group',
        'inputs.*'              => 'Input Group',
        'inputs.code'           => 'Group Code',
        'inputs.app_id'      => 'Application',
        'inputs.user_id'      => 'User',
        'inputs.name'      => 'Group Name'
    ];

    public function validateForm()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->inputs['name'], 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    public function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->reset('inputs');
            $this->refreshApplication();
            $this->refreshUser();
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number;
        }
    }

    public function Save()
    {
        $this->validateForm();

        try {
            $application = ConfigAppl::find($this->inputs['app_id']);
            $user = ConfigUser::find($this->inputs['user_id']);

            $this->inputs['app_code'] = $application->code;
            $this->inputs['user_code'] =  $user->code;
            if ($this->actionValue == 'Create') {
                $this->object = ConfigGroup::create($this->inputs);
            } elseif ($this->actionValue == 'Edit') {
                if ($this->object) {
                    $this->object->updateObject($this->VersioNumber);
                    $this->object->update($this->inputs);
                }
            }
            $this->resetForm();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.save', ['object' => $this->inputs['name']])
            ]);
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
