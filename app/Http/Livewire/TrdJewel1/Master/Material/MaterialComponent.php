<?php

namespace App\Http\Livewire\TrdJewel1\Master\Material;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Master\MatlUom;
use App\Models\TrdJewel1\Master\MatlBom;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\Inventories\IvtBal;
use App\Models\Inventories\IvtBalUnit;
use App\Models\Base\Attachment;
use Illuminate\Support\Facades\Session;
use Lang;
use Exception;
use DB;
use Livewire\WithFileUploads;
use App\Enums\Status;
use Illuminate\Validation\Rule;

class MaterialComponent extends BaseComponent
{
    use WithFileUploads;
    public $object_uoms;
    public $object_boms;
    public $materials = [];
    public $matl_uoms = [];
    public $matl_boms = [];

    public $unit_row = 0;
    public $photo;

    public $materialCategories;
    public $materialUOMs;
    public $baseMaterials;
    public $selectedBomKey;

    public $deletedItems = [];
    public $newItems = [];
    public $bom_row = 0;

    public $capturedImages = [];
    public $deleteImages = [];

    protected function onLoad()
    {
        $this->object = Material::withTrashed()->find($this->objectIdValue);
        if($this->object)
        {
            $this->object_uoms = $this->object->MatlUom[0];
            $this->object_boms = $this->object->MatlBom;
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms);
            foreach ($this->object_boms as $key => $detail) {
                $this->refreshBaseMaterials($this->bom_row);
                $formattedDetail = populateArrayFromModel($detail);
                $this->matl_boms[$key] =  $formattedDetail;
                $this->matl_boms[$key]['id'] = $detail->id;
                $this->bom_row++;
            }
            $attachments = $this->object->Attachment;
            foreach ($attachments as $attachment) {
                $url = $attachment->getUrl();
                $this->capturedImages[] = ['url' => $url, 'filename' => $attachment->name];
            }
            $this->sellingPriceChanged();
        }
    }

    public function refreshUOMs()
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_UOM')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
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
        $data = DB::connection('sys-config1')
            ->table('config_consts')
            ->select('id','str1','str2')
            ->where('const_group', 'MMATL_CATEGL1')
            ->where('app_code', $this->appCode)
            ->where('deleted_at', NULL)
            ->orderBy('seq')
            ->get();

        $this->materialCategories = $data->map(function ($item) {
            return [
                'label' => $item->str2,
                'value' => $item->str1
            ];
        })->toArray();

        $this->materials['jwl_category'] = null;
    }

    public function refreshBaseMaterials($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_COMPONENTS')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->baseMaterials = $data->map(function ($item) {
            return [
                'label' => $item->str2,
                'value' => $item->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['base_matl_id'] = null;
    }

    protected function onPopulateDropdowns()
    {
        $this->refreshUOMs();
        $this->refreshCategories();
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

            $this->notify('error', $errorMessage);
        }
    }

    public function render()
    {
        return view('livewire.trd-jewel1.master.material.material-component');
    }


    protected $listeners = [
        'imagesCaptured'  => 'imagesCaptured',
        'runExe'  => 'runExe'
    ];

    protected function rules()
    {
        $rules = [
            'materials.jwl_buying_price' => 'required|numeric|min:0|max:9999999999',
            'materials.jwl_selling_price' => 'required|numeric|min:0|max:9999999999',
            'materials.jwl_category' => 'required|string|min:0|max:255',
            'matl_uoms.name' => 'required|string|min:0|max:255',
            'matl_uoms.barcode' => 'required|string|min:0|max:255',
            'matl_boms.*.base_matl_id' => 'required',
            'matl_boms.*.jwl_sides_cnt' => 'required|numeric|min:0|max:9999999999',
            'matl_boms.*.jwl_sides_carat' => 'required|numeric|min:0|max:9999999999',
            'matl_boms.*.jwl_sides_price' => 'required|numeric|min:0|max:9999999999',
            // 'materials.code' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:50',
            //     Rule::unique('sys-config1.config_appls', 'code')->ignore($this->object ? $this->object->id : null),
            // ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'materials'                => 'Input Material',
        'materials.*'              => 'Input Material',
        'materials.code'      => 'Material Code',
        'materials.jwl_category'      => 'Material Category',
        'matl_uoms.name'      => 'Material UOM',
        'materials.descr'      => 'Description Material',
        'matl_uoms.barcode'      => 'Barcode Material',
        'materials.jwl_buying_price'      => 'Buying Price Material',
        'materials.jwl_selling_price'      => 'Selling Price Material',
        'matl_boms.*.base_matl_id' => 'Material',
        'matl_boms.*.jwl_sides_cnt' => 'Quantity',
        'matl_boms.*.jwl_sides_carat' => 'Carat',
        'matl_boms.*.jwl_sides_price' => 'Price',
    ];

    protected function onReset()
    {
        $this->reset('materials');
        $this->reset('matl_uoms');
        $this->reset('matl_boms');
        $this->object = new Material();
        $this->object_uoms = new MatlUom();
        $this->object_boms = [];
        $this->deletedItems = [];
        $this->newItems = [];
        $this->bom_row = 0;
        $this->capturedImages = [];
    }

    public function onValidateAndSave()
    {
        $this->validateBoms();
        $this->materials['descr'] = $this->getMaterialDescriptionsFromBOMs();
        $this->object->fill($this->materials);
        if($this->object->code == null)
        {
            $configSnum = ConfigSnum::where('app_code', '=', $this->appCode)
            ->where('code', '=', 'MMATL_'.$this->materials['jwl_category']."_LASTID")
            ->first();
            if ($configSnum != null) {
                $stepCnt = $configSnum->step_cnt;
                $proposedTrId = $configSnum->last_cnt + $stepCnt;
                if ($proposedTrId > $configSnum->wrap_high) {
                    $proposedTrId = $configSnum->wrap_low;
                }
                $proposedTrId = max($proposedTrId, $configSnum->wrap_low);
                $configSnum->last_cnt = $proposedTrId;
                $this->object->code = $this->materials['jwl_category'].$proposedTrId;
                $configSnum->save();
            }
        }
        $this->object->save();

        // Save Attachment
        $this->saveAttachment();
        $this->matl_uoms['code'] = $this->object->code;
        $this->matl_uoms['matl_id'] = $this->object->id;
        $this->matl_uoms['matl_code'] = $this->object->code;
        $this->object_uoms->fill($this->matl_uoms);

        $this->object_uoms->save();

        // Handle BOMs
        foreach ($this->matl_boms as $index => $bomData) {
            if (!isset($this->object_boms[$index])) {
                $this->object_boms[$index] = new MatlBom();
            }
            $bomData['matl_id'] = $this->object->id;
            $bomData['matl_code'] = $this->object->id;
            $bomData['seq'] = $index + 1;
            $this->object_boms[$index]->fill($bomData);
            $this->object_boms[$index]->save();
        }

        if (!$this->object->isNew()) {
            foreach ($this->deletedItems as $deletedItemId) {
                MatlBom::find($deletedItemId)->forceDelete();
            }
        }
        $this->emit('materialSaved', $this->object->id);
    }

    public function getMaterialDescriptionsFromBOMs()
    {
        $materialDescriptions = "";
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
        return $materialDescriptions;
    }

    public function validateBoms()
    {
        try {
        } catch (Exception $e) {
            throw new Exception(Lang::get('generic.error.save', ['message' => $e->getMessage()]));
        }
    }

    public function addBoms()
    {
        $bomsDetail = new MatlBom();
        array_push($this->matl_boms, $bomsDetail);
        // array_push($this->object_boms, $bomsDetail);
        $this->refreshBaseMaterials($this->bom_row);
        $newDetail = end($this->matl_boms);
        $this->newItems[] = $newDetail;
        $this->emit('itemAdded');
        $this->bom_row++;
    }

    public function generateSpecs($value)
    {
    }

    public function deleteBoms($index)
    {
        if (isset($this->matl_boms[$index]['id'])) {
            $this->deletedItems[] = $this->matl_boms[$index]['id'];
        }
        unset($this->matl_boms[$index]);
        $this->matl_boms = array_values($this->matl_boms);
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


    public function markupPriceChanged()
    {
        if (!isset($this->materials['jwl_buying_price']) || !isset($this->materials['markup'])) {
            return null;
        }

        $buyingPrice = $this->materials['jwl_buying_price'];

        $markupAmount = $buyingPrice * ($this->materials['markup'] / 100);
        $this->materials['jwl_selling_price'] = $buyingPrice + $markupAmount;
    }


    public function sellingPriceChanged()
    {
        if (!isset($this->materials['jwl_buying_price'])) {
            return;
        }

        $buyingPrice = $this->materials['jwl_buying_price'];

        if ($buyingPrice <= 0) {
            return;
        }

        $newMarkupPercentage = (($this->materials['jwl_selling_price'] - $buyingPrice) / $buyingPrice) * 100;
        $this->materials['markup'] = $newMarkupPercentage;
    }
}
