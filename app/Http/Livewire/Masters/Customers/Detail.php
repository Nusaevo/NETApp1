<?php

namespace App\Http\Livewire\Masters\Customers;

use Livewire\Component;
use App\Models\Partner;
use App\Models\PriceCategory;
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
    public $status = '';

    public function mount($action, $objectId = null)
    {
        $this->actionValue = Crypt::decryptString($action);
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
            $this->object = Partner::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
        } else {
            $this->inputs['grp'] = 'CUST';
            $this->object = new Partner();
        }
    }


    public function render()
    {
        return view('livewire.masters.customers.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.name' => 'required|string|min:1|max:50',
            'inputs.address' => 'string|min:1|max:50',
            'inputs.city' => 'string|min:1|max:20',
            'inputs.country' => 'string|min:1|max:20',
            'inputs.postal_code' => 'string|min:1|max:10',
            'inputs.contact_person' => 'string|min:1|max:255',
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.code'           => 'Customer Code',
        'inputs.name'      => 'Name',
        'inputs.address'      => 'Address',
        'inputs.city'      => 'City',
        'inputs.country'      => 'Country',
        'inputs.postal_code'      => 'Postal Code',
        'inputs.contact_person'      => 'Contact',
    ];

    public function validateForm()
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

    public function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->reset('inputs');
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number;
        }
    }

    public function Save()
    {
        $this->validateForm();

        try {
            if ($this->actionValue == 'Create') {
                $this->object = Partner::create($this->inputs);
            } elseif ($this->actionValue == 'Edit') {
                if ($this->object) {
                    $this->object->updateObject($this->VersioNumber);
                    $this->object->update($this->inputs);
                }
            }
            $this->resetForm();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.save', ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.save', ['object' => $this->object->name, 'message' => $e->getMessage()])
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
