<?php

namespace App\Livewire\SysConfig1\ConfigApplication;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigAppl;
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $group_codes;
    public $rules = [
        'inputs.code' => 'required|string|min:1|max:100',
        'inputs.name' => 'required|string|min:1|max:100',
        'inputs.latest_version' => 'required',
        'inputs.descr' => 'string|min:1|max:500',
        'inputs.db_name' => 'required|string|min:1|max:100',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs'                => 'Input Application',
            'inputs.*'              => 'Input Application',
            'inputs.name'           => 'Application Name',
            'inputs.code'      => 'Application Code',
            'inputs.latest_version' => 'Application Version',
            'inputs.descr' => 'Description',
            'inputs.db_name' => 'Database',
        ];
        if($this->isEditOrView())
        {
            $this->object = ConfigAppl::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigAppl();
    }


    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods

    public function changeStatus()
    {
        $this->change();
    }

    public function onValidateAndSave()
    {
        $this->object->fill($this->inputs);

        if($this->object->isDuplicateCode())
        {
            $this->addError('inputs.code', __('generic.error.duplicate_code'));
            throw new Exception(__('generic.error.duplicate_code'));
        }

        $this->object->save();
    }

    #endregion

    #region Components Events

    #endregion
}
