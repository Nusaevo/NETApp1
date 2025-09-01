<?php

namespace App\Livewire\TrdJewel1\Master\Category;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigAppl;
use App\Services\SysConfig1\ConfigService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
class Detail extends BaseComponent
{
    #region Constant Variables
    public $applications; // Stores the list of applications
    public $status = '';
    public $isSysConfig1;
    protected $configService;
    public $application;
    public $selectedApplication; // Holds the currently selected application
    public $isEnabled;

    public $rules = [
        'inputs.const_group' => 'required|string|in:MMATL_CATEGL1,MMATL_CATEGL2',
        'inputs.seq' => 'required|integer',
        'inputs.str1' => 'required|string|min:1|max:50',
        'inputs.str2' => 'required|string|min:1|max:100',
        'inputs.note1' => 'nullable|string|max:255',
    ];

    protected $listeners = [
        'changeStatus' => 'changeStatus',
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->isSysConfig1 = Session::get('app_code') === 'SysConfig1';
        
        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications(true);

        $this->isEnabled = $this->actionValue === 'Create' ? 'true' : 'false';

        // Force to use TrdJewel1 application for categories
        if (!$this->isSysConfig1) {
            // Get TrdJewel1 application ID from session
            $this->selectedApplication = Session::get('app_id');
            $this->application = ConfigAppl::find($this->selectedApplication);
        } else {
            // If SysConfig1, find TrdJewel1 application ID
            $jewel1App = ConfigAppl::where('code', 'TrdJewel1')->first();
            $this->selectedApplication = $jewel1App ? $jewel1App->id : 1;
            $this->application = $jewel1App;
        }

        if ($this->isEditOrView()) {
            // Use TrdJewel1 connection for edit/view
            if ($this->application) {
                $this->object = new ConfigConst();
                $this->object->setConnection($this->application->code);
                $this->object = $this->object->withTrashed()->find($this->objectIdValue);
                
                // Validate that we only edit MMATL_CATEGL1 or MMATL_CATEGL2
                if ($this->object && !in_array($this->object->const_group, ['MMATL_CATEGL1', 'MMATL_CATEGL2'])) {
                    abort(403, 'Access denied. Only Material Category 1 and 2 can be edited.');
                }
                
                $this->inputs = populateArrayFromModel($this->object);
            }
        } else {
            // For Create action, set up connection for TrdJewel1
            if ($this->application) {
                $this->object->setConnection($this->application->code);
                // Set default const_group for new records
                $this->inputs['const_group'] = 'MMATL_CATEGL1'; // Default to category 1
            }
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new ConfigConst();
        
        // Reset to TrdJewel1 connection and default category
        if ($this->application) {
            $this->object->setConnection($this->application->code);
            $this->inputs['const_group'] = 'MMATL_CATEGL1'; // Default to category 1
        }
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
        // Validate that const_group is only MMATL_CATEGL1 or MMATL_CATEGL2
        if (!in_array($this->inputs['const_group'] ?? '', ['MMATL_CATEGL1', 'MMATL_CATEGL2'])) {
            throw new Exception('Only Material Category 1 and 2 are allowed.');
        }

        // Ensure we're working with TrdJewel1 connection
        if (!$this->application || $this->application->code !== 'TrdJewel1') {
            throw new Exception('Only TrdJewel1 application is allowed for category management.');
        }

        $this->object->fill($this->inputs);
        $this->object->setConnection($this->application->code);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
    #endregion
}
