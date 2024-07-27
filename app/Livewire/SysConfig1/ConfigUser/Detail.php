<?php

namespace App\Livewire\SysConfig1\ConfigUser;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigUserInfo;
use App\Models\SysConfig1\ConfigGroup;
use Illuminate\Validation\Rule;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Facades\DB;

class Detail extends BaseComponent
{
    public $object;
    public $VersionNumber;
    public $actionValue = 'Create';
    public $objectIdValue;
    public $inputs = ['name' => ''];
    public $groups;
    public $status = '';

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
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
        return view($this->renderRoute);
    }

    public $rules = [
            'inputs.name' => 'required|string|min:1|max:100',
            // 'inputs.email' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:255',
            //     'email',
            //     Rule::unique('sys-config1.config_users', 'email')->ignore($this->object ? $this->object->id : null),
            // ],
            // 'inputs.code' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:50',
            //     Rule::unique('sys-config1.config_users', 'code')->ignore($this->object ? $this->object->id : null),
            // ],
            // 'inputs.newpassword' => 'string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'
        ];

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

        $this->object->fillAndSanitize($this->inputs);
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
                throw new Exception(__('generic.error.password_must_be_filled'));
            }
        }
        if (!empty($this->inputs['newpassword'])) {
            if ($this->inputs['newpassword'] !== $this->inputs['confirmnewpassword']) {
                throw new Exception(__('generic.error.password_mismatch'));
            }
        }
    }
}
