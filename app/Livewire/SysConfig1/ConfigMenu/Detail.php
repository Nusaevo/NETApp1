<?php

namespace App\Livewire\SysConfig1\ConfigMenu;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;
use Exception;

class Detail extends BaseComponent
{

    #region Constant Variables
    public $inputs = [];
    public $applications;
    protected $configService;

    public $rules = [
        'inputs.code' => 'required|string|min:1|max:100',
        'inputs.app_id' => 'required',
        'inputs.menu_caption' => 'required|string|min:1|max:100',
        'inputs.menu_link' => 'required|string|min:1|max:100',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs'                => 'Input Menu',
            'inputs.*'              => 'Input Menu',
            'inputs.code'           => 'Menu Code',
            'inputs.app_id'      => 'Menu Application',
            'inputs.menu_header'      => 'Menu Header',
            'inputs.menu_caption'      => 'Menu Caption',
            'inputs.menu_link'      => 'Menu link'
        ];
        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications();

        if($this->isEditOrView())
        {
            $this->object = ConfigMenu::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->inputs['app_id'] = null;
        $this->object = new ConfigMenu();
    }


    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        $application = ConfigAppl::find($this->inputs['app_id']);
        $this->inputs['app_code'] = $application->code;
        $this->object->fillAndSanitize($this->inputs);

        // if($this->object->isDuplicateCode())
        // {
        //     $this->addError('inputs.code', __('generic.error.duplicate_code'));
        //     throw new Exception(__('generic.error.duplicate_code'));
        // }
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }

    #endregion

    #region Component Events


    #endregion

}
