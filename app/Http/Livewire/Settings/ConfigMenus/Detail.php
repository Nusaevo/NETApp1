<?php

namespace App\Http\Livewire\Settings\ConfigMenus;

use Livewire\Component;
use App\Models\Settings\ConfigMenu;
use App\Models\Settings\ConfigAppl;
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
    public $languages;
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->actionValue = Crypt::decryptString($action);
        $this->refreshApplication();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
            $this->object = ConfigMenu::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
            $this->object = new ConfigMenu();
        }
    }

    public function render()
    {
        return view('livewire.settings.config-menus.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.app_id' => 'required',
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
        'inputs.app_id'      => 'Menu Application',
        'inputs.menu_header'      => 'Menu Header',
        'inputs.sub_menu'      => 'Sub Menu',
        'inputs.menu_caption'      => 'Menu Caption',
        'inputs.link'      => 'Menu link'
    ];

    public function refreshApplication()
    {
        $applicationsData = ConfigAppl::GetActiveData();
        if (!$applicationsData->isEmpty()) {
            $this->applications = $applicationsData->map(function ($data) {
                return [
                    'label' => $data->code . ' - ' . $data->name,
                    'value' => $data->id,
                ];
            })->toArray();

            $this->inputs['app_id'] = $this->applications[0]['value'];
        } else {
            $this->applications = [];
            $this->inputs['app_id'] = null;
        }
    }

    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        $application = ConfigAppl::find($this->inputs['app_id']);
        $objectData['app_code'] = $application->code;
        return $objectData;
    }

    public function validateForms()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    public function Create()
    {
        $this->validateForms();
        try {
            $objectData = $this->populateObjectArray();
            $this->object = ConfigMenu::create($objectData);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['menu_caption']])
            ]);
            $this->reset('inputs');
            $this->refreshApplication();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "User", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Edit()
    {
        $this->validateForms();
        try {
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

    public function changeStatus()
    {
        try {
            $this->object->updateObject($this->VersioNumber);

            if ($this->object->deleted_at) {
                $this->object->deleted_at = null;
                $messageKey = 'generic.success.enable';
            } else {
                $this->object->delete();
                $messageKey = 'generic.success.disable';
            }

            $this->object->save();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get($messageKey, ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['object' => $this->object->menu_caption, 'message' => $e->getMessage()])
            ]);
        }

        $this->dispatchBrowserEvent('refresh');
    }
}
