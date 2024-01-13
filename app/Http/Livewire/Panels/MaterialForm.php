<?php

namespace App\Http\Livewire\Panels;

use Livewire\Component;
use App\Models\Material;
use App\Models\MatlUom;
use App\Models\MatlBom;
use App\Models\Settings\ConfigConst;
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
    public $matl_boms_array = [];
    public $status = '';

    public $actionValue = 'Create';
    public $objectIdValue;

    public $unit_row = 0;
    public $photo;
    public $appCode = "";

    public $materialCategories;
    public $materialUOMs;
    public $baseMaterials;
    public $selectedBomKey;

    public $deletedItems = [];
    public $newItems = [];
    public $bom_row = 0;
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

            $this->object_uoms = $this->object->uoms[0];
            $this->object_boms = $this->object->boms;
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms);
            foreach ($this->object_boms as $key => $detail) {
                $this->refreshCategories($this->bom_row);
                $this->refreshBaseMaterials($this->bom_row);
                $formattedDetail = populateArrayFromModel($detail);
                array_push($this->matl_boms_array, $formattedDetail);
                $this->matl_boms[$key] =  $formattedDetail;
                $this->bom_row++;
            }
        } else {
            $this->object = new Material();
            $this->object_uoms = new MatlUom();
            $this->object_boms = [];
            $this->matl_boms_array = [];
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

    public function refreshCategories($key)
    {
        $materialCategories = ConfigConst::where('app_code', $this->appCode)
        ->where('const_group', 'MATL_JWL_CATEGORY')
        ->orderBy('seq')
        ->get();

        if (!$materialCategories->isEmpty()) {
            $this->materialCategories = $materialCategories->map(function ($data) {
                return [
                    'label' => $data->str1,
                    'value' => $data->id,
                ];
            })->toArray();
            $this->matl_boms[$key]['base_category_id'] = $this->materialCategories[0]['value'];
        } else {
            $this->materialCategories = [];
            $this->matl_boms[$key]['base_category_id'] = null;
        }
    }

    public function refreshBaseMaterials($key)
    {
        $baseMaterials = ConfigConst::where('app_code', $this->appCode)
        ->where('const_group', 'MATL_JWL_BASE_MATL')
        ->orderBy('seq')
        ->get();

        if (!$baseMaterials->isEmpty()) {
            $this->baseMaterials = $baseMaterials->map(function ($data) {
                return [
                    'label' => $data->str1,
                    'value' => $data->id,
                ];
            })->toArray();
            $this->matl_boms[$key]['base_matl_id'] = $this->baseMaterials[0]['value'];
        } else {
            $this->baseMaterials = [];
            $this->matl_boms[$key]['base_matl_id'] = null;
        }
    }

    public function render()
    {
        return view('livewire.panels.material-form');
    }


    protected $listeners = [
        'changeStatus'  => 'changeStatus'
    ];

    protected function rules()
    {
        $rules = [
            // 'materials.jwl_category' => 'required|string|min:1|max:50',
            'materials.jwl_buying_price' => 'required|integer|min:0|max:9999999999',
            'materials.jwl_selling_price' =>'required|integer|min:0|max:9999999999',
            'matl_uoms.barcode' =>'required|integer|min:0|max:9999999999',

            // 'materials.name' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:50',
            //     Rule::unique('materials', 'name')
            //         ->ignore($this->object->id)
            //         ->where(function ($query) {
            //         }),
            // ],
            // 'materials.descr' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:200',
            //     Rule::unique('materials', 'descr')
            //         ->ignore($this->object->id)
            //         ->where(function ($query) {
            //         }),
            // ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'materials'                => 'Input Material',
        'materials.*'              => 'Input Material',
        // 'materials.name'      => 'Nama Material',
        'materials.descr'      => 'Description Material',
        'matl_uoms.barcode'      => 'Barcode Material',
        'materials.jwl_buying_price'      => 'Buying Price Material',
        'materials.jwl_selling_price'      => 'Selling Price Material'
    ];

    protected function populateDropdowns()
    {
        $this->refreshUOMs();
    }

    protected function populateObjectArray($object,$formArray)
    {
        $objectData =  populateModelFromForm($object, $formArray);
        return $objectData;
    }

    public function validateForm()
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

    public function Save()
    {
        $this->validateForm();
        DB::beginTransaction();
        try {
            // Determine if it's a create or edit operation
            $isNewMaterial = $this->object->isNew();

            // Populate Material Data
            $this->materials['descr'] = $this->getMaterialDescriptionsFromBOMs();
            $materialData = $this->populateObjectArray($this->object, $this->materials);
            if ($this->object->isNew()) {
                $this->object = Material::create($materialData);
            } else {
                $this->object->updateObject($this->VersioNumber);
                $this->object->update($materialData);
            }

            // Save Attachment
            $this->saveAttachment();

            // Populate and Save/Update Material UOMs
            $materialUOMData = $this->populateObjectArray($this->object_uoms, $this->matl_uoms);
            $materialUOMData['matl_id'] = $this->object->id;
            $materialUOMData['matl_code'] = $this->object->code;

            if ($isNewMaterial) {
                $this->object_uoms = MatlUom::create($materialUOMData);
            } else {
                $this->object_uoms->updateObject($this->VersioNumber);
                $this->object_uoms->update($materialUOMData);
            }

            // Handle BOMs
            foreach ($this->matl_boms as $index => $bomData) {
                if($isNewMaterial)
                {
                    $this->object_boms[$index] =  new MatlBom();
                }
                $bomData = $this->populateObjectArray($this->object_boms[$index], $bomData);
                $bomData['matl_id'] = $this->object->id;
                $bomData['matl_code'] = $this->object->id;
                $bomData['seq'] = $index + 1;
                $bom = MatlBom::find($this->object_boms[$index]->id);
                if ($bom) {
                    $bom->updateObject($this->VersioNumber);
                    $bom->update($bomData);
                } else {
                    MatlBom::create($bomData);
                }
            }

            // Handle Deleted BOMs for Edit operation
            if (!$isNewMaterial) {
                foreach ($this->deletedItems as $deletedItemId) {
                    MatlBom::find($deletedItemId)->delete();
                }
            }

            // Handle Warehouses - only for new materials
            if ($isNewMaterial) {
                $warehouse = ConfigConst::GetWarehouse();
                foreach ($warehouse as $warehouse) {
                    $inventoryBal = IvtBal::firstOrCreate([
                        'matl_id' => $this->object->id,
                        'wh_id' => $warehouse->id,
                        'wh_code' => $warehouse->str2
                    ]);
                    IvtBalUnit::firstOrCreate([
                        'ivt_id' => $inventoryBal->id,
                        'matl_id' => $this->object->id,
                        'wh_id' => $warehouse->id,
                        'matl_uom_id' =>$this->object_uoms->id,
                        'uom' =>$this->object_uoms->name,
                    ]);
                }
            }

            // Dispatch success notification
            $message = $isNewMaterial ? 'generic.success.create' : 'generic.success.update';
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get($message, ['object' => "Material"])
            ]);

            // Reset and repopulate dropdowns

            DB::commit();
            $this->emit('materialSaved', $this->object->id);
            if ($isNewMaterial) {
                $this->resetMaterialForm();
            }
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.update', ['object' => "Material", 'message' => $e->getMessage()])
            ]);
        }
    }

    public function getMaterialDescriptionsFromBOMs()
    {
        $materialDescriptions = "";

        // Check if there are any BOMs
        if ($this->matl_boms && count($this->matl_boms) > 0) {
            foreach ($this->matl_boms as $bomData) {

                $baseMaterials = ConfigConst::find($bomData['base_matl_id']);
                $jwlSidesCnt = $bomData['jwl_sides_cnt'] ?? 0;
                $jwlSidesCarat = $bomData['jwl_sides_carat'] ?? 0;

                $description = "$jwlSidesCnt $baseMaterials->str1:$jwlSidesCarat";
                $materialDescriptions .= $description . "  ";
            }

            return $materialDescriptions;
        }

        // Return an empty string or a default value if there are no BOMs
        return $materialDescriptions;
    }




    protected function resetMaterialForm()
    {
        $this->reset('materials');
        $this->reset('matl_uoms');
        $this->reset('matl_boms');

        $this->object = new Material();
        $this->object_uoms = new MatlUom();
        $this->object_boms = [];
        $this->matl_boms_array = [];
        $this->deletedItems = [];
        $this->newItems = [];
        $this->bom_row = 0;
        $this->populateDropdowns();
    }

    public function saveAttachment()
    {
        if ($this->photo) {
            $attachmentsPath = storage_path('attachments/' . $this->object->id);
            if (!file_exists($attachmentsPath)) {
                mkdir($attachmentsPath, 0777, true);
            }

            $filename = $this->photo->getClientOriginalName();
            $filePath = 'attachments/' . $this->object->id . '/' . $filename;
            $fullPath = $attachmentsPath . '/' . $filename;

            $counter = 1;
            while (file_exists($fullPath)) {
                $filename = pathinfo($this->photo->getClientOriginalName(), PATHINFO_FILENAME)
                            . '_' . $counter . '.'
                            . $this->photo->getClientOriginalExtension();
                $filePath = 'attachments/' . $this->object->id . '/' . $filename;
                $fullPath = $attachmentsPath . '/' . $filename;
                $counter++;
            }

            $this->photo->storeAs('attachments/' . $this->object->id, $filename, 'public');

            $attachmentData = [
                'name' => $filename,
                'path' => $filePath,
                'seq' => 1,
                'content_type' => $this->photo->getClientMimeType(),
                'extension' => $this->photo->getClientOriginalExtension(),
                'attached_objectid' => $this->object->id,
                'attached_objecttype' => class_basename($this->object)
            ];

            $existingAttachment = Attachment::where('attached_objectid', $this->object->id)
                                            ->where('attached_objecttype', class_basename($this->object))
                                            ->first();

            if ($existingAttachment) {
                $existingAttachment->update($attachmentData);
            } else {
                Attachment::create($attachmentData);
                $this->photo = null;
            }
        }
    }


    public function validateBoms()
    {
        // $rules = [
        //     'inputs.tr_date' => 'required',
        //     'input_details.*.matl_id' => 'required',
        //     'input_details.*.qty' => 'required|integer|min:0|max:9999999999',
        //     'input_details.*.price' => 'required|integer|min:0|max:9999999999',
        // ];
        // $attributes = [
        //     'inputs'                => 'Input',
        //     'inputs.*'              => 'Input',
        //     'inputs.tr_date'      => 'Tanggal Transaksi',
        //     'inputs.partner_id'      => 'Supplier',
        //     'input_details.*'              => 'Inputan Barang',
        //     'input_details.*.matl_id' => 'Item',
        //     'input_details.*.qty' => 'Item Qty',
        //     'input_details.*.price' => 'Item Price',
        // ];

        try {
            // $this->validate($rules, $attributes);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "Material Bom", 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    public function addBoms()
    {
        $bomsDetail = new MatlBom();
        array_push($this->matl_boms_array, $bomsDetail);
        array_push($this->object_boms, $bomsDetail);
        $this->refreshCategories($this->bom_row);
        $this->refreshBaseMaterials($this->bom_row);
        $newDetail = end($this->matl_boms_array);
        $this->newItems[] = $newDetail;
        $this->emit('itemAdded');
        $this->bom_row++;
    }

    public function generateSpecs($value)
    {

    }

    public function deleteBoms($index)
    {
        if (isset($this->matl_boms_array[$index]['id'])) {
            $this->deletedItems[] = $this->matl_boms_array[$index]['id'];
        }
        unset($this->matl_boms_array[$index]);
        $this->matl_boms_array = array_values($this->matl_boms_array);

    }

    public function runExe()
    {
        $exePath = 'C:\RFIDScanner\RFIDScanner.exe';
        $exePath = escapeshellarg($exePath); // Use escapeshellarg for safety

        // Define the arguments
        $maxScannedTagLimit = 1;
        $timeoutSeconds = 1;

        // Append arguments to the command
        $command = $exePath . ' ' . escapeshellarg($maxScannedTagLimit) . ' ' . escapeshellarg($timeoutSeconds);

        exec($command, $output, $returnValue);
        $this->matl_uoms['barcode'] = $output[0];
    }

}
