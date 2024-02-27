<?php

namespace App\Http\Livewire\Settings\ConfigUsers;

use Livewire\Component;
use App\Models\Settings\ConfigUser;
use App\Models\Settings\ConfigUserInfo;
use App\Models\Settings\ConfigGroup;
use Illuminate\Validation\Rule;
use App\Models\Settings\ConfigAppl;
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
    public $inputs = ['name' => ''];
    public $groups;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->actionValue = decryptWithSessionKey($action);
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = decryptWithSessionKey($objectId);
            $this->object = ConfigUser::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['newpassword'] = "";
            $this->inputs['confirmnewpassword'] = "";
        } else {
            $this->resetForm();
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
        $userId = optional($this->object)->id;
        $rules = [
            'inputs.name' => 'required|string|min:1|max:100',
            'inputs.email' => [
                'required',
                'string',
                'min:1',
                'max:255',
                Rule::unique('config_users', 'email')
                    ->ignore($userId)
            ],
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('config_users', 'code')
                    ->ignore($userId)
            ],
            // 'inputs.newpassword' => 'string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
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


    public function validateForm()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "object", 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    public function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->reset('inputs');
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number;
            $this->inputs['newpassword'] = "";
            $this->inputs['confirmnewpassword'] = "";
        }
    }

    public function Save()
    {
        $this->validateForm();
        try {
            if (!empty($this->inputs['newpassword'])) {
                $this->inputs['password'] = bcrypt($this->inputs['newpassword']);
            }

            if (!$this->validatePassword()) {
                return;
            }

            if ($this->actionValue == 'Create') {
                $this->object = ConfigUser::create($this->inputs);
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

    protected function validatePassword()
    {
        if ($this->actionValue == 'Create') {
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
