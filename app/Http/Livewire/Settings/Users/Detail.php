<?php

namespace App\Http\Livewire\Settings\Users;

use Livewire\Component;
use App\Models\User;
use App\Models\UserInfo;
use App\Models\ConfigGroup;
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

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        $this->group_codes = ConfigGroup::GetConfigGroup();

        $this->languages = [
            ['id' => 'EN', 'name' => 'English (EN)'],
            ['id' => 'ID', 'name' => 'Indonesian (ID)'],
        ];
        $this->inputs['group_codes'] = 0;
        $this->inputs['language'] = 0;

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->user = User::find($this->objectId);
            $this->VersioNumber = $this->user->version_number;
            $this->inputs['name'] = $this->user->name;
            $this->inputs['email'] = $this->user->email;
            $this->inputs['company'] = $this->user->info->company;
            $this->inputs['phone'] = $this->user->info->phone;
            $this->inputs['language'] = $this->user->info->language;
            $this->inputs['group_codes'] = $this->user->code;
        } else {
            $this->user = new User();
        }
    }

    // Validation rules and other methods...
    protected function rules()
    {
        $_unique_exception = $this->action === 'Edit' ? ',' . $this->objectId : '';
        return [
            'inputs.name' => 'required|string|min:1|max:1',
            'inputs.email' => 'required|string|min:1|max:128',
        ];
    }

    protected $validationAttributes = [
        'inputs.name'           => 'Name',
        'inputs.email'       => 'Email'
    ];

    public function Create()
    {
        try {
            $this->validate();


            DB::beginTransaction();
            $newUser =   User::create([
                'name' => $this->inputs['name'],
                'email' => $this->inputs['email'],
                'code' => $this->inputs['group_codes'],
                'password' => bcrypt($this->inputs['password']),
            ]);
            $index = 0;
            $newUserInfo = UserInfo::create([
                'user_id' => $newUser->id,
                'company'   => $this->inputs['company'],
                'phone'              => $this->inputs['phone'],
                'language'              => $this->inputs['language']
            ]);

            DB::commit();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'title' => Lang::get('generic.success.title'),
                'message' => Lang::get('generic.success.create', ['object' => "User " . $this->user->name])
            ]);
            $this->dispatchBrowserEvent('refresh');
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'title' => Lang::get('generic.error.title'),
                'message' => Lang::get('generic.error.create', ['object' => "User ", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Edit()
    {
        try {
            $this->validate();

            $password = "";

            if (!empty($this->inputs['password'])) {
                if ($this->inputs['password'] != $this->inputs['newpassword']) {
                    $this->dispatchBrowserEvent('notify-swal', [
                        'type' => 'error',
                        'title' => Lang::get('generic.error.title'),
                        'message' => "Password tidak sama!"
                    ]);
                    $password = $this->inputs['password'];
                } else {
                    $password = bcrypt($this->inputs['password']);
                }
            }

            DB::beginTransaction();

            if ($this->user) {
                $this->user->updateObject($this->VersioNumber);
                $userUpdateData = [
                    'name' => $this->inputs['name'],
                    'email' => $this->inputs['email'],
                ];

                if (!empty($password)) {
                    $userUpdateData['password'] = $password;
                }

                $this->user->update($userUpdateData);

                if ($this->user->info) {
                    $this->user->info->update([
                        'company' => $this->inputs['company'],
                        'phone' => $this->inputs['phone'],
                        'language' => $this->inputs['language'],
                    ]);
                }
            }

            DB::commit();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'title' => Lang::get('generic.success.title'),
                'message' => Lang::get('generic.success.update', ['object' => "User " . $this->user->name])
            ]);
            $this->dispatchBrowserEvent('refresh');
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'title' => Lang::get('generic.error.title'),
                'message' => Lang::get('generic.error.create', ['object' => "User ", 'message' => $e->getMessage()])
            ]);
        }
    }


    public function render()
    {
        return view('livewire.settings.users.edit');
    }
}
