<?php

namespace App\Livewire\SysConfig1\ConfigConst;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Facades\DB;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;
    public $status = '';
    protected $configService;

    public $rules= [
        'inputs.app_id' => 'required',
        'inputs.const_group' => 'required|string|min:1|max:50',
        'inputs.seq' =>  'required',
        'inputs.str1' => 'required|string|min:1|max:50',
        'inputs.str2' => 'string|min:1|max:50',
        'inputs.code' => [ 'required'],
    ];

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs'                => 'Input',
            'inputs.*'              => 'Input',
            'inputs.code'           => 'Const Code',
            'inputs.const_group'           => 'Const Group',
            'inputs.seq'      => 'Const Seq',
            'inputs.app_id'      => 'Const Application',
            'inputs.str1'      => 'Str1',
            'inputs.str2'      => 'Str2'
        ];
        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications();
        $this->inputs['app_id'] = null;

        if($this->isEditOrView())
        {
            $this->object = ConfigConst::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigConst();
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    public function onValidateAndSave()
    {
        $application = ConfigAppl::find($this->inputs['app_id']);
        $this->inputs['app_code'] = $application->code;
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
}
