<?php

namespace App\Http\Livewire\Masters\Warehouses;

use Livewire\Component;
use App\Models\Partner;
use App\Models\PriceCategory;
use App\Models\Warehouse;
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
    public $status = '';

    public $purposes;

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        $this->refreshPurpose();
        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->object = Warehouse::withTrashed()->find($this->objectId);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
            $this->object = new Warehouse();
        }
    }


    public function render()
    {
        return view('livewire.masters.warehouses.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.name' => 'required|string|min:1|max:50',
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.name'      => 'Name',
    ];

    public function refreshPurpose()
    {
        $purposesArray = [
            ['label' => 'In', 'value' => 'in'],
            ['label' => 'Out', 'value' => 'out'],
            ['label' => 'Store', 'value' => 'store'],
        ];

        if (!empty($purposesArray)) {
            $this->purposes = $purposesArray;
            $this->inputs['purpose'] = $purposesArray[0]['value'];
        } else {
            $this->purposes = [];
            $this->inputs['purpose'] = null;
        }
    }


    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->inputs);
        $objectData['grp'] = 'CUST';
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
            $this->object = Partner::create($objectData);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->inputs['name']])
            ]);
            $this->inputs = [];
            $this->inputs['purpose'] = $purposesArray[0]['value'];
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
                'message' => Lang::get('generic.success.update', ['object' => $this->object->name])
            ]);
            $this->VersioNumber = $this->object->version_number;
        } catch (Exception $e) {
            //DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->name, 'message' => $e->getMessage()])
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
