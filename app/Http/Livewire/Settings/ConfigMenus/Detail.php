<?php

namespace App\Http\Livewire\Settings\ConfigMenus;

use Livewire\Component;
use App\Models\ConfigMenu;
use App\Models\ConfigAppl;
use Illuminate\Validation\Rule;
use Lang;
use Exception;
use DB;

class Detail extends Component
{
    public $object;
    public $VersioNumber;
    public $action = 'Create';
    public $objectId;
    public $inputs = [];
    public $applications;
    public $languages;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;

        $applicationsData = ConfigAppl::GetActiveData();

        $this->applications = $applicationsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
                'value' => $data->code,
            ];
        })->toArray();
        $this->inputs['applications'] = $this->applications[0]['value'];

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = ConfigMenu::withTrashed()->find($this->objectId);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs['code'] = $this->object->code;
            $this->inputs['applications']  =  $this->object->appl_code;
            $this->inputs['menu_header'] = $this->object->menu_header;
            $this->inputs['sub_menu'] = $this->object->sub_menu;
            $this->inputs['menu_caption'] = $this->object->menu_caption;
            $this->inputs['link'] = $this->object->link;
        } else {
            $this->object = new ConfigMenu();
        }
    }

    public function render()
    {
        return view('livewire.settings.config-menus.edit');
    }

    protected function rules()
    {
        $rules = [
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('config_appls', 'code')
                    ->ignore($this->object->id)
                    ->where(function ($query) {
                    }),
            ],
            'inputs.applications' => 'required|string|min:1|max:50',
            'inputs.menu_header' => 'required|string|min:1|max:100',
            'inputs.sub_menu' => 'string|min:1|max:100',
            'inputs.menu_caption' => 'required|string|min:1|max:100',
            'inputs.link' => 'required|string|min:1|max:100',
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.code'           => 'Menu Code',
        'inputs.applications'      => 'Menu Application Code',
        'inputs.menu_header'      => 'Menu Header',
        'inputs.sub_menu'      => 'Sub Menu',
        'inputs.menu_caption'      => 'Menu Caption',
        'inputs.link'      => 'Menu link'
    ];

    protected function populateObjectArray()
    {
        return [
            'code' => $this->inputs['code'],
            'appl_code' => $this->inputs['applications'],
            'menu_header' => $this->inputs['menu_header'],
            'sub_menu' => $this->inputs['sub_menu'] ?? "",
            'menu_caption' => $this->inputs['menu_caption'],
            'link' => $this->inputs['link']
        ];
    }

    public function Create()
    {
        try {
            $this->validate();
            $objectData = $this->populateObjectArray();
            $this->object = ConfigMenu::create($objectData);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['menu_caption']])
            ]);
            $this->inputs = [];
            $this->inputs['applications'] = $this->applications[0]['value'];
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
                'message' => Lang::get('generic.success.update', ['object' => $this->object->menu_caption])
            ]);
            $this->VersioNumber = $this->object->version_number;
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->menu_caption, 'message' => $e->getMessage()])
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
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->menu_caption])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->menu_caption, 'message' => $e->getMessage()])
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
                'message' => Lang::get('generic.success.enable', ['object' => $this->object->menu_caption])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.enable', ['object' => $this->object->menu_caption, 'message' => $e->getMessage()])
            ]);
        }
        $this->dispatchBrowserEvent('refresh');
    }
}
