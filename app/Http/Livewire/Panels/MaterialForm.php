<?php

namespace App\Http\Livewire\Panels;

use Livewire\Component;
use App\Models\Material;
use App\Models\MatlUom;
use App\Models\MatlBom;
use App\Models\ConfigConst;
use App\Models\IvtBal;
use App\Models\IvtBalUnit;

use App\Models\Attachment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class MaterialForm extends Component
{
    use WithFileUploads;
    public $object;
    public $object_uoms;
    public $object_boms;
    public $VersioNumber;
    public $materials = [];
    public $matl_uoms = [];
    public $matl_boms = [];
    public $status = '';

    public $actionValue = 'Create';
    public $objectIdValue;

    public $unit_row = 0;
    public $photo;
    public $appCode = "";

    public $materialCategories;
    public $materialUOMs;



    public function mount($materialActionValue, $materialIDValue = null)
    {
        $this->appCode =  env('APP_NAME', 'DefaultAppName');
        $this->actionValue = $materialActionValue;
        $this->objectIdValue = $materialIDValue;
        $this->populateDropdowns();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $this->objectIdValue) {
            $this->object = Material::withTrashed()->find($this->objectIdValue);
            // $this->object_detail = ItemUnit::ItemId($this->object->id)->get();
            //$this->attachments = $this->object->attachments;

            $this->object_uoms = $this->object->uoms;
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms[0]);
            // foreach ($this->object_detail as $index => $detail) {
            //     $this->material_details[$index] = populateArrayFromModel($detail);
            //     $this->material_details[$index]['id'] = $detail->id;
            // }
        } else {
            $this->object = new Material();
            $this->object_uoms = new MatlUom();
            $this->object_boms = new MatlBom();
        }
    }

    public function refreshCategories()
    {
        $materialCategories = ConfigConst::where('app_code', $this->appCode)
        ->where('const_group', 'MATL_JWL_CATEGORY')
        ->orderBy('seq')
        ->get();

        if (!$materialCategories->isEmpty()) {
            $this->materialCategories = $materialCategories->map(function ($data) {
                return [
                    'label' => $data->str1,
                    'value' => $data->str1,
                ];
            })->toArray();

            $this->materials['jwl_category'] = $this->materialCategories[0]['value'];
        } else {
            $this->materialCategories = [];
            $this->materials['jwl_category'] = null;
        }
    }

    public function refreshUOMs()
    {
        $materialUOMs = ConfigConst::where('app_code', $this->appCode)
        ->where('const_group', 'MATL_UOM')
        ->orderBy('seq')
        ->get();

        if (!$materialUOMs->isEmpty()) {
            $this->materialUOMs = $materialUOMs->map(function ($data) {
                return [
                    'label' => $data->str1,
                    'value' => $data->str1,
                ];
            })->toArray();
            $this->matl_uoms['name'] = $this->materialUOMs[0]['value'];
        } else {
            $this->materialUOMs = [];
            $this->matl_uoms['name'] = null;
        }
    }

    public function render()
    {
        return view('livewire.panels.material-form');
    }


    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'materials.jwl_category' => 'required|string|min:1|max:50',
            'materials.name' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('materials', 'name')
                    ->ignore($this->object->id)
                    ->where(function ($query) {
                    }),
            ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'materials'                => 'Input Material',
        'materials.*'              => 'Input Material',
        'materials.name'      => 'Nama Material'
    ];

    protected function populateDropdowns()
    {
        $this->refreshCategories();
        $this->refreshUOMs();
    }

    protected function populateObjectArray($object,$formArray)
    {
        $objectData =  populateModelFromForm($object, $formArray);
        return $objectData;
    }

    public function validateForms()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => $this->object->name ?? "material", 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    public function Create()
    {
        $this->validateForms();
        DB::beginTransaction();
        try {
            $materialData = $this->populateObjectArray($this->object,$this->materials);
            $this->object = Material::create($materialData);

            $this->saveAttachment();
            $materialUOMData = $this->populateObjectArray($this->object_uoms,$this->matl_uoms);
            $materialUOMData['matl_id'] = $this->object->id;
            $materialUOMData['matl_code'] = $this->object->code;
            $this->object_uoms = MatlUom::create($materialUOMData);


            $warehouse = ConfigConst::GetWarehouse();
            foreach ($warehouse as $warehouse) {
                $inventoryBal = IvtBal::firstOrCreate(
                    [
                        'matl_id' => $this->object->id,
                        'wh_id' => $warehouse->id,
                        'wh_code' =>  $warehouse->str2
                    ]
                );
                IvtBalUnit::firstOrCreate(
                    [
                        'ivt_id' =>  $inventoryBal->id,
                        'matl_id' => $this->object->id,
                        'wh_id' => $warehouse->id,
                        'matl_uom_id' =>$this->object_uoms->id,
                        'uom' =>$this->object_uoms->name,
                    ]
                );
            }

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.create', ['object' => $this->materials['name']])
            ]);
            $this->reset('materials');
            $this->reset('matl_uoms');
            $this->populateDropdowns();
            DB::commit();
            $this->emit('materialCreated', $this->object);
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
                $this->object->updateObject($this->VersioNumber);
                $materialData = $this->populateObjectArray($this->object,$this->materials);
                $this->object->update($materialData);
                $this->saveAttachment();

                $materialUOMData = $this->populateObjectArray($this->object_uoms[0],$this->matl_uoms);
                $this->object_uoms[0]->update($materialUOMData);

                DB::commit();
                $this->dispatchBrowserEvent('notify-swal', [
                    'type' => 'success',
                    'message' => Lang::get('generic.success.update', ['object' => $this->object->name])
                ]);
                $this->VersioNumber = $this->object->version_number;
                $this->populateDropdowns();
                $this->emit('materialUpdated', $this->object);
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.update', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
    }

    public function saveAttachment()
    {
        if ($this->photo) {
            $attachmentsPath = storage_path('attachments');
            if (!file_exists($attachmentsPath)) {
                mkdir($attachmentsPath, 0777, true);
            }
            $filename = $this->photo->getClientOriginalName();
            $filePath = 'attachments/' . $filename;
            $fullPath = $attachmentsPath . '/' . $filename;
            $counter = 1;
            while (file_exists($fullPath)) {
                $filename = pathinfo($this->photo->getClientOriginalName(), PATHINFO_FILENAME)
                            . '_' . $counter . '.'
                            . $this->photo->getClientOriginalExtension();
                $filePath = 'attachments/' . $filename;
                $fullPath = $attachmentsPath . '/' . $filename;
                $counter++;
            }

            $this->photo->storeAs('attachments', $filename, 'public');

            $attachmentData = [
                'name' => $filename,
                'path' => $filePath,
                'seq' => 1,
                'content_type' => $this->photo->getClientMimeType(),
                'extension' => $this->photo->getClientOriginalExtension(),
                'attached_objectid' =>  $this->object->id,
                'attached_objecttype' => class_basename($this->object)
            ];

            $existingAttachment = Attachment::where('attached_objectid', $this->object->id)
                                            ->where('attached_objecttype', class_basename($this->object))
                                            ->first();

            if ($existingAttachment) {
                $existingAttachment->update($attachmentData);
            } else {
                Attachment::create($attachmentData);
            }
        }
    }
}
