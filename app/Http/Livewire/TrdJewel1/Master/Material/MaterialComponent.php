<?php

namespace App\Http\Livewire\TrdJewel1\Master\Material;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Master\MatlUom;
use App\Models\TrdJewel1\Master\MatlBom;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\Base\Attachment;
use Lang;
use Exception;
use DB;
use Livewire\WithFileUploads;

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


    public $sideMaterialShapes = [];
    public $sideMaterialGiaColors = [];
    public $sideMaterialGemColors = [];
    public $sideMaterialCut = [];
    public $sideMaterialClarity = [];
    public $sideMaterialJewelPurity = [];
    public $sideMaterialGemStone = [];

    protected function onPreRender()
    {
        $this->langBasePath = 'trd-jewel1/master/material/detail';
        $this->customValidationAttributes  = [
            'materials'                => $this->trans('input'),
            'materials.*'              => $this->trans('input'),
            'materials.code'      => $this->trans('code'),
            'materials.jwl_category'      => $this->trans('category'),
            'materials.code'      => $this->trans('uom'),
            'materials.descr'      => $this->trans('description'),
            'matl_uoms.barcode'      => $this->trans('barcode'),
            'materials.jwl_buying_price'      =>  $this->trans('buying_price'),
            'materials.jwl_selling_price'      => $this->trans('selling_price'),
            'matl_boms.*.base_matl_id' => $this->trans('material'),
            'matl_boms.*.jwl_sides_cnt' => $this->trans('quantity'),
            'matl_boms.*.jwl_sides_carat' => $this->trans('carat'),
            'matl_boms.*.jwl_sides_price' => $this->trans('price'),
        ];
        $this->customRules  = [
            'materials.jwl_buying_price' => 'required',
            'materials.jwl_selling_price' => 'required',
            'materials.jwl_category' => 'required|string|min:0|max:255',
            'matl_uoms.barcode' => 'required|string|min:0|max:255',
            'matl_boms.*.base_matl_id' => 'required',
            'matl_boms.*.jwl_sides_cnt' => 'required',
            'matl_boms.*.jwl_sides_carat' => 'required',
            // 'matl_boms.*.jwl_sides_price' => 'required',
            // 'materials.code' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:50',
            //     Rule::unique('sys-config1.config_appls', 'code')->ignore($this->object ? $this->object->id : null),
            // ],
        ];
    }

    protected function onLoadForEdit()
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
                $this->refreshSideMaterialGiaColor($this->bom_row);
                $this->refreshSideMaterialGemColor($this->bom_row);
                $this->refreshSideMaterialClarity($this->bom_row);
                $this->refreshSideMaterialCut($this->bom_row);
                $this->refreshSideMaterialGemstone($this->bom_row);
                $this->refreshSideMaterialShapes($this->bom_row);
                $this->refreshSideMaterialJewelPurity($this->bom_row);
                $formattedDetail = populateArrayFromModel($detail);
                $this->matl_boms[$key] =  $formattedDetail;
                $this->matl_boms[$key]['id'] = $detail->id;
                $decodedData = json_decode($detail->jwl_sides_spec, true);
                switch ($detail->base_matl_id) {
                    case Material::GOLD:
                    case Material::ROSE_GOLD:
                    case Material::WHITE_GOLD:
                        $this->matl_boms[$key]['purity'] = $decodedData['purity'] ?? null;
                        break;
                    case Material::DIAMOND:
                        $this->matl_boms[$key]['shapes'] = $decodedData['shapes'] ?? null;
                        $this->matl_boms[$key]['clarity'] = $decodedData['clarity'] ?? null;
                        $this->matl_boms[$key]['color'] = $decodedData['color'] ?? null;
                        $this->matl_boms[$key]['cut'] = $decodedData['cut'] ?? null;
                        $this->matl_boms[$key]['gia_number'] = $decodedData['gia_number'] ?? null;
                        break;
                    case Material::STONE:
                        $this->matl_boms[$key]['gemstone'] = $decodedData['gemstone'] ?? null;
                        $this->matl_boms[$key]['color'] = $decodedData['color'] ?? null;
                        break;
                    case Material::ANTAM:
                        $this->matl_boms[$key]['production_year'] = $decodedData['production_year'] ?? null;
                        $this->matl_boms[$key]['ref_mark'] = $decodedData['ref_mark'] ?? null;
                        break;
                }

                switch ($formattedDetail['base_matl_id']) {

                }
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
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1,
            ];
        })->toArray();
        $this->matl_uoms['matl_uom'] = 'PCS';
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

        $this->materialCategories = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
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

        $this->baseMaterials = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->id
            ];
        })->toArray();
        $this->matl_boms[$key]['base_matl_id'] = null;
    }

    public function refreshSideMaterialShapes($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GEMSHAPES')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->sideMaterialShapes = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['shapes'] = null;
    }

    public function refreshSideMaterialClarity($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2','note1')
        ->where('const_group', 'MMATL_JEWEL_GIACLARITY')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->sideMaterialClarity = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['clarity'] = null;
    }

    public function refreshSideMaterialCut($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GIACUT')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->sideMaterialCut = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['cut'] = null;
    }

    public function refreshSideMaterialGemColor($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GEMCOLORS')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->sideMaterialGemColors = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['color'] = null;
    }

    public function refreshSideMaterialGiaColor($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GIACOLORS')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->sideMaterialGiaColors = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['color'] = null;
    }

    public function refreshSideMaterialGemstone($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GEMSTONES')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->sideMaterialGemStone = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['gemstone'] = null;
    }
    public function refreshSideMaterialJewelPurity($key)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GOLDPURITY')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        $this->sideMaterialJewelPurity = $data->map(function ($data) {
            return [
                'label' => $data->str1." - ".$data->str2,
                'value' => $data->str1
            ];
        })->toArray();
        $this->matl_boms[$key]['purity'] = null;
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
                        $errorMessages[] = Lang::get($this->langBasePath.'.message.attachment_failed', ['filename' => $image['filename']]);
                    }
                } catch (Exception $e) {
                    $errorMessages[] = Lang::get($this->langBasePath.'.message.attachment_failed', ['filename' => $image['filename']]);
                }
            }
        }

        Attachment::reSortSequences($this->object->id, class_basename($this->object));
        if (!empty($errorMessages)) {
            $errorMessage = implode(', ', $errorMessages);
            throw new Exception($errorMessage);
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
        $this->generateMaterialDescriptionsFromBOMs();
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

            if ($bomData['jwl_sides_price'] === null || $bomData['jwl_sides_price'] === "") {
                $bomData['jwl_sides_price'] = 0;
            }
            $bomData['seq'] = $index + 1;
            $baseMaterialId = $bomData['base_matl_id'];
            $dataToSave = [];
            if (in_array($baseMaterialId, [Material::GOLD, Material::ROSE_GOLD, Material::WHITE_GOLD])) {
                $dataToSave['purity'] = $bomData['purity'] ?? null;
            } elseif (in_array($baseMaterialId, [Material::DIAMOND])) {
                $dataToSave = [
                    'shapes' => $bomData['shapes'] ?? null,
                    'clarity' => $bomData['clarity'] ?? null,
                    'color' => $bomData['color'] ?? null,
                    'cut' => $bomData['cut'] ?? null,
                    'gia_number' => $bomData['gia_number'] ?? null,
                ];
            } elseif (in_array($baseMaterialId, [Material::STONE])) {
                $dataToSave = [
                    'gemstone' => $bomData['gemstone'] ?? null,
                    'color' => $bomData['color'] ?? null,
                ];
            } elseif (in_array($baseMaterialId, [Material::ANTAM])) {
                $dataToSave = [
                    'production_year' => $bomData['production_year'] ?? null,
                    'ref_mark' => $bomData['ref_mark'] ?? null,
                ];
            }
            $bomData['jwl_sides_spec']= json_encode($dataToSave);
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

    public function generateMaterialDescriptionsFromBOMs()
    {
        $materialDescriptions = '';

        if ($this->matl_boms && count($this->matl_boms) > 0) {
            $bomIds = array_column($this->matl_boms, 'base_matl_id');
            $bomData = ConfigConst::whereIn('id', $bomIds)->get()->keyBy('id');

            foreach ($this->matl_boms as $bom) {
                $baseMaterial = $bomData[$bom['base_matl_id']] ?? null;

                if ($baseMaterial) {
                    $jwlSidesCnt = $bom['jwl_sides_cnt'] ?? 0;
                    $jwlSidesCarat = $bom['jwl_sides_carat'] ?? 0;
                    $materialDescriptions .= "$jwlSidesCnt $baseMaterial->str1:$jwlSidesCarat ";
                }
            }
        }

        $this->materials['descr'] = $materialDescriptions;
    }

    public function addBoms()
    {
        $bomsDetail = new MatlBom();
        $bomsDetail['jwl_sides_price'] = 0;
        $bomsDetail['jwl_sides_cnt'] = 1;
        array_push($this->matl_boms, $bomsDetail);
        // array_push($this->object_boms, $bomsDetail);
        $this->refreshBaseMaterials($this->bom_row);
        $this->refreshSideMaterialGiaColor($this->bom_row);
        $this->refreshSideMaterialGemColor($this->bom_row);
        $this->refreshSideMaterialClarity($this->bom_row);
        $this->refreshSideMaterialCut($this->bom_row);
        $this->refreshSideMaterialGemstone($this->bom_row);
        $this->refreshSideMaterialShapes($this->bom_row);
        $this->refreshSideMaterialJewelPurity($this->bom_row);
        $newDetail = end($this->matl_boms);
        $this->newItems[] = $newDetail;
        $this->emit('itemAdded');
        $this->bom_row++;
    }

    public function deleteBoms($index)
    {
        if (isset($this->matl_boms[$index]['id'])) {
            $this->deletedItems[] = $this->matl_boms[$index]['id'];
        }
        unset($this->matl_boms[$index]);
        $this->matl_boms = array_values($this->matl_boms);
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

    public function printBarcode()
    {
        if (isset( $this->matl_uoms['barcode'])) {
            if(isset($this->materials['descr']))
            {
                $additionalParam = urlencode($this->matl_uoms['barcode'] . ';' . $this->materials['descr']);
                return redirect()->route('TrdJewel1.Master.Material.PrintPdf', ["action" => encryptWithSessionKey('Edit'),'objectId' => encryptWithSessionKey(""),'additionalParam'=> $additionalParam ]);
            }else{
                $this->notify('error',Lang::get($this->langBasePath.'.message.side_material_input'));
            }

        }else{
            $this->notify('error',Lang::get($this->langBasePath.'.message.barcode_validation'));
        }
    }
}
