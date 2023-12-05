<?php

namespace App\Http\Livewire\Settings\ConfigRights;

use Livewire\Component;
use App\Models\ConfigGroup;
use App\Models\ConfigAppl;
use App\Models\ConfigMenu;
use App\Models\ConfigRight;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $VersioNumber;
    public $actionValue = 'Create';
    public $objectIdValue;
    public $inputs = [];
    public $applications;
    public $groups;
    public $menus;
    public $status = '';
    public $trustee;

    public function mount($action, $objectId = null)
    {
         $this->actionValue = Crypt::decryptString($action);

        $applicationsData = ConfigAppl::GetActiveData();
        $this->applications = $applicationsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
                'value' => $data->code,
            ];
        })->toArray();
        $this->inputs['applications'] = $this->applications[0]['value'];
        $this->populateApplication($this->inputs['applications']);

        $this->trustee = [
            'R' => 'Read',
            'C' => 'Create',
            'U' => 'Update',
            'D' => 'Delete'
        ];
        $this->inputs['trustee'] = [
            'C' => false,
            'R' => false,
            'U' => false,
            'D' => false,
        ];

        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
            $this->object = ConfigRight::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs['application_code'] = $this->object->application_code;
            $this->inputs['group_code'] = $this->object->group_code;
            $this->inputs['menu_code'] = $this->object->menu_code;
            $this->inputs['menu_seq'] = $this->object->menu_seq;
            $trusteeValues = $this->object->trustee;
            $this->inputs['trustee'] = [
                'C' => strpos($trusteeValues, 'C') !== false,
                'R' => strpos($trusteeValues, 'R') !== false,
                'U' => strpos($trusteeValues, 'U') !== false,
                'D' => strpos($trusteeValues, 'D') !== false,
            ];
        } else {
            $this->object = new ConfigGroup();
        }
    }

    public function loadGroupsAndMenus()
    {
        if (!empty($this->inputs['applications'] )) {
            $this->populateApplication($this->inputs['applications']);
        } else {
            $this->groups = [];
            $this->menus = [];
        }
    }

    public function populateApplication($appcode)
    {
        $groupsData = ConfigGroup::where('app_code', $appcode)->get();
        $this->groups = $groupsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
                'value' => $data->code,
            ];
        })->toArray();
        $this->inputs['groups'] = $this->groups[0]['value'];

        $menusData = ConfigMenu::where('app_code', $appcode)->get();
        $this->menus = $menusData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->menu_caption,
                'value' => $data->code,
            ];
        })->toArray();
        $this->inputs['menus'] = $this->menus[0]['value'];
    }


    public function render()
    {
        return view('livewire.settings.config-rights.edit');
    }

    protected function rules()
    {
        $rules = [
            'inputs.applications' => [
                'required',
                'string',
                'min:1',
                'max:50'
            ],
            'inputs.groups' => [
                'required',
                'string',
                'min:1',
                'max:50'
            ],
            'inputs.menus' => [
                'required',
                'string',
                'min:1',
                'max:50'
            ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Group',
        'inputs.*'              => 'Input Group',
        'inputs.code'           => 'Group Code',
        'inputs.applications'      => 'Application Code',
        'inputs.groups'      => 'Group Code',
        'inputs.menus'      => 'Menu Code',
        'inputs.menu_seq'      => 'Menu Seq'
    ];

    protected function populateObjectArray()
    {
        $trustee = [];
        if ($this->inputs['trustee']['C']) {
            $trustee[] = 'C';
        }
        if ($this->inputs['trustee']['R']) {
            $trustee[] = 'R';
        }
        if ($this->inputs['trustee']['U']) {
            $trustee[] = 'U';
        }
        if ($this->inputs['trustee']['D']) {
            $trustee[] = 'D';
        }

        return [
            'app_code' => $this->inputs['applications'],
            'group_code' => $this->inputs['groups'],
            'menu_code' => $this->inputs['menus'],
            'menu_seq' => $this->inputs['menu_seq'],
            'trustee' => implode('', $trustee),
        ];
    }

    public function Create()
    {
        try {
            $this->validate();
            $objectData = $this->populateObjectArray();
            $this->object = ConfigRight::create($objectData);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['menus']])
            ]);
            $this->inputs = [];
            $this->inputs['applications'] = $this->applications[0]['value'];
            $this->inputs['menus'] = $this->menus[0]['value'];
            $this->inputs['groups'] = $this->groups[0]['value'];
        } catch (Exception $e) {
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

            if ($this->object) {
                $this->object->updateObject($this->VersioNumber);
                $objectData = $this->populateObjectArray();
                $this->object->update($objectData);
            }

            //DB::commit();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.update', ['object' => $this->object->menu_code])
            ]);
            $this->VersioNumber = $this->object->version_number;
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->menu_code, 'message' => $e->getMessage()])
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
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->menu_code])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->menu_code, 'message' => $e->getMessage()])
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
                'message' => Lang::get('generic.success.enable', ['object' => $this->object->menu_code])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.enable', ['object' => $this->object->menu_code, 'message' => $e->getMessage()])
            ]);
        }
        $this->dispatchBrowserEvent('refresh');
    }
}
