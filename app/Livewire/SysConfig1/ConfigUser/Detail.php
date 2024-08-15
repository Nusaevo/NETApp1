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
    #region Constant Variables
    public $groups;
    public $rules = [
        'inputs.name' => 'required|string|min:1|max:100'
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];
    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs'                => 'Input User',
            'inputs.*'              => 'Input User',
            'inputs.name'           => 'Nama User',
            'inputs.code'      => 'Login ID',
            'inputs.email'       => 'Email User',
            'inputs.dept'       => 'Department',
            'inputs.phone'       => 'No HP',
            'inputs.newpassword' => 'Password'
        ];

        if($this->isEditOrView())
        {
            $this->object = ConfigUser::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['newpassword'] = "";
            $this->inputs['confirmnewpassword'] = "";
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigUser();
    }


    public function render()
    {
        return view($this->renderRoute);
    }


    #endregion

    #region CRUD Methods

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

    #endregion

    #region Component Events


    #endregion

}
