<?php

namespace App\Http\Livewire\Masters\Materials;

use Livewire\Component;
use App\Models\Material;
use App\Models\ItemUnit;
use App\Models\ConfigConst;
use App\Models\IvtBal;
use App\Models\Attachment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;
use Livewire\WithFileUploads;

class Detail extends Component
{
    use WithFileUploads;
    public $object;
    public $object_detail;
    public $VersioNumber;
    public $action = 'Create';
    public $objectId;
    public $materials = [];
    public $material_details = [];
    public $status = '';

    public $actionValue = 'Create';
    public $objectIdValue;

    public $unit_row = 0;
    public $attachment ;
    public $attachments = [];

    public function mount($action, $objectId = null)
    {
        $this->actionValue = Crypt::decryptString($action);

        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $this->objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
            $this->object = Material::withTrashed()->find($this->objectIdValue);
            // $this->object_detail = ItemUnit::ItemId($this->object->id)->get();
            //$this->attachments = $this->object->attachments;
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->materials = populateArrayFromModel($this->object);
            // foreach ($this->object_detail as $index => $detail) {
            //     $this->material_details[$index] = populateArrayFromModel($detail);
            //     $this->material_details[$index]['id'] = $detail->id;
            // }
        } else {
            $this->object = new Material();
        }
    }


    public function render()
    {
        return view('livewire.masters.materials.edit');
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'materials.name' => 'required|string|min:1|max:50'
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'materials'                => 'Input Menu',
        'materials.*'              => 'Input Menu',
        'materials.name'      => 'Name'
    ];

    protected function populateObjectArray()
    {
        $objectData =  populateModelFromForm($this->object, $this->materials);
        return $objectData;
    }

    // public function addAttachment()
    // {
    //     $this->validate([
    //         'attachments.*' => 'file|max:10240',
    //     ]);

    //     foreach ($this->attachments as $attachment) {
    //         $attachmentData = [
    //             'name' => $attachment->getClientOriginalName(),
    //             'path' => $attachment->store('attachments'),
    //             'content_type' => $attachment->getClientMimeType(),
    //             'extension' => $attachment->getClientOriginalExtension(),
    //             'descr' => 'Attachment description',
    //             'attached_objectid' => $this->object->id,
    //             'attached_objecttype' => 'Material',
    //         ];

    //         Attachment::create($attachmentData);
    //     }

    //     // Clear the attachments array after adding them
    //     $this->attachments = [];

    //     // Reload attachments in case you want to display the updated list
    //     $this->attachments = $this->object->attachments;

    //     // Optionally, you can show a success message
    //     $this->dispatchBrowserEvent('notify-swal', [
    //         'type' => 'success',
    //         'message' => 'Attachments added successfully.',
    //     ]);
    // }

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
        DB::beginTransaction();
        try {
            $objectData = $this->populateObjectArray();
            $this->object = Material::create($objectData);
            $warehouse = ConfigConst::GetWarehouse();
            foreach ($warehouse as $warehouse) {
                IvtBal::firstOrCreate(
                    [
                        'matl_id' => $this->object->id,
                        'wh_id' => $warehouse->id,
                        'wh_code' =>  $warehouse->str2
                    ]
                );
            }
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->materials['name']])
            ]);
            // $this->reset('materials');
            // $this->reset('material_details');
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "User", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function Edit()
    {
        $this->validateForms();
        DB::beginTransaction();
        try {
            if ($this->object) {
                $objectData = $this->populateObjectArray();
                $this->object->update($objectData);

                DB::commit();
                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'success',
                    'message' => Lang::get('generic.success.update', ['object' => $this->object->name])
                ]);
                $this->VersioNumber = $this->object->version_number;
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.update', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
    }
}
