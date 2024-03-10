<?php

namespace App\Http\Livewire\Settings\ConfigUsers;

use App\Http\Livewire\Components\BaseComponent;
use App\Models\Settings\ConfigUser;
use App\Models\Settings\ConfigUserInfo;
use App\Models\Settings\ConfigGroup;
use Illuminate\Validation\Rule;
use App\Models\Settings\ConfigAppl;
use Illuminate\Support\Facades\Crypt;

use Lang;
use Exception;
use DB;

class Detail extends BaseComponent
{
    public $object;
    public $VersioNumber;
    public $actionValue = 'Create';
    public $objectIdValue;
    public $inputs = ['name' => ''];
    public $groups;
    public $status = '';

    protected function onLoad()
    {
        $this->object = ConfigUser::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['newpassword'] = "";
        $this->inputs['confirmnewpassword'] = "";
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
                'email',
                Rule::unique('config.config_users', 'email')->ignore($this->object ? $this->object->id : null),
            ],
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('config.config_users', 'code')->ignore($this->object ? $this->object->id : null),
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

    protected function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigUser();
    }

    protected function onPopulateDropdowns()
    {
    }

    public function onValidateAndSave()
    {
        if (!empty($this->inputs['newpassword'])) {
            $this->inputs['password'] = bcrypt($this->inputs['newpassword']);
        }

        $this->validatePassword();

        $this->object->fill($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
       $this->change();
    }

    protected function validatePassword()
    {
        if ($this->object->isNew()) {
            if (empty($this->inputs['newpassword'])) {
                throw new Exception(Lang::get('generic.error.password_must_be_filled'));
            }
        }
        if (!empty($this->inputs['newpassword'])) {
            if ($this->inputs['newpassword'] !== $this->inputs['confirmnewpassword']) {
                throw new Exception(Lang::get('generic.error.password_mismatch'));
            }
        }
    }
}
