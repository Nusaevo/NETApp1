<?php

namespace App\Http\Livewire\Settings\ConfigUsers;

use Livewire\Component;
use App\Models\ConfigUser;
use App\Models\ConfigUserInfo;
use App\Models\ConfigGroup;
use Illuminate\Validation\Rule;
use App\Models\ConfigAppl;

use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $VersioNumber;
    public $action = 'Create';
    public $objectId;
    public $inputs = ['name' => ''];
    public $groups;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = ConfigUser::withTrashed()->find($this->objectId);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['newpassword'] = "";
            $this->inputs['confirmnewpassword'] = "";
        } else {
            $this->object = new ConfigUser();
        }
    }
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    public function render()
    {
        return view('livewire.settings.config-users.edit');
    }
    protected function rules()
    {
        $rules = [
            'inputs.name' => 'required|string|min:1|max:100',
            'inputs.email' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('config_users', 'email')
                    ->ignore($this->object->id)
                    ->where(function ($query) {
                        // You can add additional conditions here if needed.
                    }),
            ],
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('config_users', 'code')
                    ->ignore($this->object->id)
                    ->where(function ($query) {
                        // You can add additional conditions here if needed.
                    }),
            ],
            'inputs.newpassword' => 'string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input User',
        'inputs.*'              => 'Input User',
        'inputs.name'           => 'Nama User',
        'inputs.code'      => 'Login ID',
        'inputs.email'       => 'Email User',
        'inputs.dept'       => 'Department',
        'inputs.phone'       => 'No HP',
        'inputs.newpassword' => 'Password'
    ];


    public function validateForms()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        if (!empty($this->inputs['newpassword'])) {
            $objectData['password'] = bcrypt($this->inputs['newpassword']);
        }
        return $objectData;
    }


    public function Create()
    {
        $this->validateForms();
        try {
            if (!$this->validatePassword()) {
                return;
            }
            //DB::beginTransaction();
            $objectData = $this->populateObjectArray();
            $this->object = ConfigUser::create($objectData);
            //DB::commit();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['name']])
            ]);
            $this->reset('inputs');
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->inputs['name'], 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Edit()
    {
        $this->validateForms();
        try {
            if (!$this->validatePassword()) {
                return;
            }

            if ($this->object) {
                $this->object->updateObject($this->VersioNumber);
                $objectData = $this->populateObjectArray();
                $this->object->update($objectData);
            }

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.update', ['object' => $this->object->name])
            ]);
            $this->VersioNumber = $this->object->version_number;
            $this->inputs['newpassword'] = "";
            $this->inputs['confirmnewpassword'] = "";
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->name, 'message' => $e->getMessage()])
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
                'message' => Lang::get($messageKey, ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }

        $this->dispatchBrowserEvent('refresh');
    }

    protected function validatePassword()
    {
        if ($this->action == 'Create') {
            if (empty($this->inputs['newpassword'])) {
                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'error',
                    'title' => Lang::get('generic.error.title'),
                    'message' => Lang::get('generic.error.password_must_be_filled')
                ]);
                return false;
            }
        }
        if (!empty($this->inputs['newpassword'])) {
            if ($this->inputs['newpassword'] !== $this->inputs['confirmnewpassword']) {
                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'error',
                    'title' => Lang::get('generic.error.title'),
                    'message' => Lang::get('generic.error.password_mismatch')
                ]);
                return false;
            }
        }
        return true;
    }
}
