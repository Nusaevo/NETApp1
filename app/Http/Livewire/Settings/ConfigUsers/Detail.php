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
    public $user;
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

        $this->languages = [
            ['id' => 'EN', 'name' => 'English (EN)'],
            ['id' => 'ID', 'name' => 'Indonesian (ID)'],
        ];
        $this->inputs['language'] = 0;
        $this->inputs['group_codes'] = 0;

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->user = ConfigUser::withTrashed()->find($this->objectId);
            $this->status = $this->user->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->user->version_number;
            $this->inputs['code'] = $this->user->code;
            $this->inputs['name'] = $this->user->name;
            $this->inputs['email'] = $this->user->email;
            $this->inputs['dept'] = $this->user->dept;
            $this->inputs['phone'] = $this->user->phone;
            $this->inputs['password'] = "";
            $this->inputs['newpassword'] = "";
        } else {
            $this->user = new ConfigUser();
        }
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
                    ->ignore($this->user->id)
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
                    ->ignore($this->user->id)
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

    public function Create()
    {
        try {
            $this->validate();
            if (!$this->validatePassword()) {
                return;
            }
            //DB::beginTransaction();
            $newUser =   ConfigUser::create([
                'name' => $this->inputs['name'],
                'email' => $this->inputs['email'],
                'code' => $this->inputs['code'],
                'dept' => $this->inputs['dept'],
                'phone' => $this->inputs['phone'],
                'password' => bcrypt($this->inputs['password']),
            ]);
            // $newUserInfo = ConfigUser::create([
            //     'user_id' => $newUser->id,
            //     'company'   => $this->inputs['company'],
            //     'phone'              => $this->inputs['phone'],
            //     'language'              => $this->inputs['language']
            // ]);

            //DB::commit();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => "User " . $this->inputs['name']])
            ]);

            $this->dispatchBrowserEvent('refresh');
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "User ", 'message' => $e->getMessage()])
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

            if ($this->user) {
                $this->user->updateObject($this->VersioNumber);
                $userUpdateData = [
                    'name' => $this->inputs['name'],
                    'email' => $this->inputs['email'],
                    'code' => $this->inputs['code'],
                    'dept' => $this->inputs['dept'],
                    'phone' => $this->inputs['phone'],
                ];

                if (!empty($this->inputs['password'])) {
                    $userUpdateData['password'] = bcrypt($this->inputs['password']);
                }

                $this->user->update($userUpdateData);
            }

            //DB::commit();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.update', ['object' => "User " . $this->user->name])
            ]);
            $this->dispatchBrowserEvent('refresh');
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "User ", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Disable()
    {
        try {
            $this->user->updateObject($this->VersioNumber);
            $this->user->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.disable', ['object' => "User " . $this->user->name])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => "User " . $this->user->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->dispatchBrowserEvent('refresh');
    }

    public function Enable()
    {
        try {
            $this->user->updateObject($this->VersioNumber);
            $this->user->deleted_at = null;
            $this->user->save();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.enable', ['object' => "User " . $this->user->name])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.enable', ['object' => "User " . $this->user->name, 'message' => $e->getMessage()])
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

    public function render()
    {
        return view('livewire.settings.config-users.edit');
    }
}
