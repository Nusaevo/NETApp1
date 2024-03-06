<?php

namespace App\Http\Livewire\Masters\Materials;

use Livewire\Component;
use App\Models\Masters\Material;
use App\Models\Masters\MatlUom;
use App\Models\Masters\MatlBom;
use App\Models\Settings\ConfigConst;
use App\Models\Inventories\IvtBal;
use App\Models\Inventories\IvtBalUnit;
use App\Models\Bases\Attachment;
use Illuminate\Support\Facades\Session;
use Lang;
use Exception;
use DB;
use Livewire\WithFileUploads;

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

    public $capturedImages = [];
    public $deleteImages = [];
    public function mount($materialActionValue, $materialIDValue = null)
    {
        $this->appCode =  Session::get('app_code', '');
        $this->actionValue = $materialActionValue;
        $this->objectIdValue = $materialIDValue;
        $this->populateDropdowns();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $this->objectIdValue) {
            $this->object = Material::withTrashed()->find($this->objectIdValue);
            // $this->object_detail = ItemUnit::ItemId($this->object->id)->get();
            //$this->attachments = $this->object->attachments;
            $this->object_uoms = $this->object->MatlUom[0];
            $this->object_boms = $this->object->MatlBom;
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms);
            foreach ($this->object_boms as $key => $detail) {
                $this->refreshBaseMaterials($this->bom_row);
                $formattedDetail = populateArrayFromModel($detail);
                array_push($this->matl_boms_array, $formattedDetail);
                $this->matl_boms[$key] =  $formattedDetail;
                $this->bom_row++;
            }
            $attachments = $this->object->Attachment;
            foreach ($attachments as $attachment) {
                $url = $attachment->getUrl();
                $this->capturedImages[] = ['url' => $url, 'filename' => $attachment->name];
            }
        } else {
            $this->resetMaterialForm();
        }
    }

    public function refreshUOMs()
    {
        $data = ConfigConst::where('app_code', $this->appCode)
        ->where('const_group', 'MATL_UOM')
        ->orderBy('seq')
        ->get();

        $this->materialUOMs = $data->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->id,
            ];
        })->toArray();

        $this->matl_uoms['name'] = null;
    }

    public function refreshCategories()
    {
        $data = ConfigConst::where('app_code', $this->appCode)
        ->where('const_group', 'MATL_JWL_CATEGORY')
        ->orderBy('seq')
        ->get();
        $this->materialCategories = $data->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->id,
            ];
        })->toArray();
        $this->materials['jwl_category'] = null;
    }

    public function refreshBaseMaterials($key)
    {
        $data = ConfigConst::where('app_code', $this->appCode)
        ->where('const_group', 'MATL_JWL_BASE_MATL')
        ->orderBy('seq')
        ->get();

        $this->baseMaterials = $data->map(function ($data) {
            return [
                'label' => $data->str1,
                'value' => $data->id,
            ];
        })->toArray();
        $this->matl_boms[$key]['base_matl_id'] = null;
    }

    public function imagesCaptured($imageDataUrl)
    {
        $filename = uniqid() . '.jpg';
        $this->capturedImages[] = ['url' => $imageDataUrl, 'filename' => $filename];
    }
    
    public function deleteImage($index)
    {
        if (isset($this->capturedImages[$index])) {
            $this->deleteImages[] = $this->capturedImages[$index]['filename'];
            unset($this->capturedImages[$index]);
        }
    }
    
    public function saveAttachment()
    {
        $errorMessages = [];
        // Delete attachments based on deleteImages array
        if (!empty($this->deleteImages)) {
            foreach ($this->deleteImages as $filename) {
                Attachment::deleteAttachmentByFilename($this->object->id, class_basename($this->object), $filename);
            }
            $this->deleteImages = [];
        }
        // Save new attachments
        if (!empty($this->capturedImages)) {
            foreach ($this->capturedImages as $image) {
                try {
                    $filePath = Attachment::saveAttachmentByFileName($image['url'], $this->object->id, class_basename($this->object), $image['filename']);
                    if ($filePath !== false) {
                    } else {
                        $errorMessages[] = "Failed to save attachment with filename {$image['filename']}";
                    }
                } catch (Exception $e) {
                    $errorMessages[] = "An error occurred while saving attachment with filename {$image['filename']}: " . $e->getMessage();
                }
            }
        }
    
        Attachment::reSortSequences($this->object->id, class_basename($this->object));
        if (!empty($errorMessages)) {
            $errorMessage = "Failed to save attachments: " . implode(', ', $errorMessages);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => $errorMessage,
            ]);
        }
    }

    public function render()
    {
        return view('livewire.masters.materials.material-form');
    }


    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'imagesCaptured'  => 'imagesCaptured',
        'runExe'  => 'runExe'
    ];

    protected function rules()
    {
        $rules = [
            'materials.jwl_buying_price' => 'required|integer|min:0|max:9999999999',
            'materials.jwl_selling_price' =>'required|integer|min:0|max:9999999999',
            'matl_uoms.barcode' =>'required|integer|min:0|max:9999999999',
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
        $this->refreshCategories();
    }

    protected function resetMaterialForm()
    {
        if ($this->actionValue == 'Create') {
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
            $this->capturedImages = [];
            $this->populateDropdowns();
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number ?? null;
        }
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
            // Populate Material Data
            $this->materials['descr'] = $this->getMaterialDescriptionsFromBOMs();

            if ($this->object) {
                $this->object->updateObject($this->VersioNumber);
                $this->object->fill($this->materials);
                $this->object->save();
            }

            // Save Attachment
            $this->saveAttachment();
            // Populate and Save/Update Material UOMs
            $materialUOMData = $this->populateObjectArray($this->object_uoms, $this->matl_uoms);
            $materialUOMData['matl_id'] = $this->object->id;
            $materialUOMData['matl_code'] = $this->object->code;

            $this->object_uoms = $this->object->isNew()
            ? MatlUom::create($materialUOMData)
            : tap($this->object_uoms)->update($materialUOMData);

         // Handle BOMs
            foreach ($this->matl_boms as $index => $bomData) {
                if (!isset($this->object_boms[$index])) {
                    $this->object_boms[$index] = new MatlBom();
                }

                if ($this->object_boms[$index]->isNew()) {
                    $this->object_boms[$index] = new MatlBom();
                }

                $bomData = $this->populateObjectArray($this->object_boms[$index], $bomData);
                $bomData['matl_id'] = $this->object->id;
                $bomData['matl_code'] = $this->object->id;
                $bomData['seq'] = $index + 1;

                if (isset($this->object_boms[$index]->id)) {
                    $bom = MatlBom::find($this->object_boms[$index]->id);
                    if ($bom) {
                        $bom->update($bomData);
                    }
                } else {
                    MatlBom::create($bomData);
                }
            }
            
            if (!$this->object->isNew()) {
                foreach ($this->deletedItems as $deletedItemId) {
                    MatlBom::find($deletedItemId)->delete();
                }
            }


            // Handle Warehouses - only for new materials
            if ($this->object->isNew()) {
                $warehouseIds = ConfigConst::GetWarehouse()->pluck('id')->toArray();
                $inventoryBalData = [];
                foreach ($warehouseIds as $warehouseId) {
                    $inventoryBalData[] = [
                        'matl_id' => $this->object->id,
                        'wh_id' => $warehouseId,
                        'wh_code' => $warehouseId,
                    ];
                }
                IvtBal::insert($inventoryBalData);

                // Create inventory balance units
                $inventoryBalUnitsData = [];
                foreach ($warehouseIds as $warehouseId) {
                    $inventoryBalUnitsData[] = [
                        'ivt_id' => $this->object->id,
                        'matl_id' => $this->object->id,
                        'wh_id' => $warehouseId,
                        'matl_uom_id' => $this->object_uoms->id,
                        'uom' => $this->object_uoms->name,
                    ];
                }
                IvtBalUnit::insert($inventoryBalUnitsData);
            }

            
            // Dispatch success notification
            $message = $this->object->isNew() ? 'generic.success.create' : 'generic.success.update';
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get($message, ['object' => "Material"])
            ]);

            // Reset and repopulate dropdowns

            DB::commit();
            $this->emit('materialSaved', $this->object->id);
            $this->resetMaterialForm();
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
        // array_push($this->object_boms, $bomsDetail);
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
        // $exePath = 'C:\RFIDScanner\RFIDScanner.exe';
        // $exePath = escapeshellarg($exePath); // Use escapeshellarg for safety

        // // Define the arguments
        // $maxScannedTagLimit = 1;
        // $timeoutSeconds = 1;

        // // Append arguments to the command
        // $command = $exePath . ' ' . escapeshellarg($maxScannedTagLimit) . ' ' . escapeshellarg($timeoutSeconds);

        // exec($command, $output, $returnValue);
        $this->matl_uoms['barcode'] = "12345";
    }

}
