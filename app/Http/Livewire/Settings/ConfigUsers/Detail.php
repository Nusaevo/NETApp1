<?php

namespace App\Http\Livewire\Settings\ConfigUsers;

use Livewire\Component;
use App\Models\ConfigUser;
use App\Models\ConfigUserInfo;
use Illuminate\Validation\Rule;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $VersioNumber;
    public $action = 'Create'; // Default to Create
    public $objectId;
    public $inputs = ['name' => ''];
    public $group_codes;
    public $languages;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        //$this->group_codes = ConfigGroup::GetConfigGroup();
        // $this->languages = [
        //     ['id' => 'EN', 'name' => 'English (EN)'],
        //     ['id' => 'ID', 'name' => 'Indonesian (ID)'],
        // ];
        // $this->inputs['language'] = 0;
        // $this->inputs['group_codes'] = 0;

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = ConfigUser::withTrashed()->find($this->objectId);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs['code'] = $this->object->code;
            $this->inputs['name'] = $this->object->name;
            $this->inputs['email'] = $this->object->email;
            $this->inputs['dept'] = $this->object->dept;
            $this->inputs['phone'] = $this->object->phone;
            $this->inputs['password'] = "";
            $this->inputs['newpassword'] = "";
        } else {
            $this->object = new ConfigUser();
        }
    }

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
            'inputs.password' => 'string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
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
        'inputs.password' => 'Password',
    ];

    protected function populateObjectArray()
    {
        $objectData = [
            'name' => $this->inputs['name'],
            'email' => $this->inputs['email'],
            'code' => $this->inputs['code'],
            'dept' => $this->inputs['dept'],
            'phone' => $this->inputs['phone'],
        ];

        if (!empty($this->inputs['password'])) {
            $objectData['password'] = bcrypt($this->inputs['password']);
        }

        return $objectData;
    }
    public function Create()
    {
        try {
            $this->validate();
            if (!$this->validatePassword()) {
                return;
            }
            //DB::beginTransaction();
            $objectData = $this->populateObjectArray();
            $this->object = ConfigUser::create($objectData);
            // $newUserInfo = ConfigUser::create([
            //     'user_id' => $newUser->id,
            //     'company'   => $this->inputs['company'],
            //     'phone'              => $this->inputs['phone'],
            //     'language'              => $this->inputs['language']
            // ]);

            //DB::commit();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['name']])
            ]);

            $this->dispatchBrowserEvent('refresh');
        } catch (Exception $e) {
            //DB::rollBack();
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
            if (!$this->validatePassword()) {
                return;
            }

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
            $this->dispatchBrowserEvent('refresh');
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
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.enable', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->dispatchBrowserEvent('refresh');
    }

    protected function validatePassword()
    {
        if (!empty($this->inputs['password'])) {
            if ($this->inputs['password'] !== $this->inputs['newpassword']) {
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
