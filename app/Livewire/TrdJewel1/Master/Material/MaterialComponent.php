<?php

namespace App\Livewire\TrdJewel1\Master\Material;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Master\MatlUom;
use App\Models\TrdJewel1\Master\MatlBom;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\Base\Attachment;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;
use App\Enums\Status;
use App\Services\TrdJewel1\Master\MasterService;

class MaterialComponent extends BaseComponent
{
    use WithFileUploads;
    public $object_uoms;
    public $object_boms;
    public $materials = [];
    public $matl_uoms = [];
    public $matl_boms = [];
    public $product_code = "";

    public $photo;

    public $materialCategories1;
    public $materialCategories2;
    public $materialUOMs;
    public $baseMaterials;

    public $deletedItems = [];
    public $bom_row = 0;

    public $capturedImages = [];
    public $deleteImages = [];


    public $materialJewelPurity = [];
    public $sideMaterialShapes = [];
    public $sideMaterialGiaColors = [];
    public $sideMaterialGemColors = [];
    public $sideMaterialCut = [];
    public $sideMaterialClarity = [];
    public $sideMaterialJewelPurity = [];
    public $partners = [];
    public $sideMaterialGemStone = [];
    public $sideMaterialJewelOrigins = [];
    public $searchMode = false;
    public $panelEnabled = "true";
    public $orderedMaterial = false;

    protected $masterService;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $searchMode = false)
    {
        $this->searchMode = $searchMode;
        $this->bypassPermissions = $searchMode;
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public $rules = [
        'materials.code' => 'required',
        'materials.jwl_buying_price_usd' => [
            'required_if:orderedMaterial,false'
        ],
        'materials.jwl_selling_price_usd' => [
            'required_if:orderedMaterial,false'
        ],
        'materials.jwl_buying_price_idr' => [
            'required_if:orderedMaterial,true'
        ],
        'materials.jwl_selling_price_idr' => [
            'required_if:orderedMaterial,true'
        ],
        'materials.jwl_category1' => 'required|string|min:0|max:255',
        // 'materials.jwl_category2' => 'required|string|min:0|max:255',
        'materials.jwl_carat' => 'required|string|min:0|max:255',
        'materials.jwl_wgt_gold' => 'required',
        'materials.name' => 'required|string|min:0|max:255',
        // 'materials.descr' => 'required|string|min:0|max:255',
        // 'matl_uoms.barcode' => 'required',
        'matl_boms.*.base_matl_id' => 'required',
        'matl_boms.*.jwl_sides_cnt' => 'required',
        'matl_boms.*.jwl_sides_carat' => 'required',
        'matl_boms.*.jwl_sides_price' => 'nullable',
        'matl_boms.*.purity' => 'nullable',
        'matl_boms.*.shapes' => 'nullable',
        'matl_boms.*.clarity' => 'nullable',
        'matl_boms.*.color' => 'nullable',
        'matl_boms.*.cut' => 'nullable',
        'matl_boms.*.gia_number' => 'nullable',
        'matl_boms.*.gemstone' => 'nullable',
        'matl_boms.*.gemcolor' => 'nullable',
        'matl_boms.*.production_year' => 'nullable',
        'matl_boms.*.ref_mark' => 'nullable'
    ];

    protected function onPreRender()
    {
        $this->panelEnabled = $this->actionValue == 'Create' ? 'true' : 'false';
        $this->baseRoute = 'TrdJewel1.Master.Material.Detail';
        // $this->langBasePath = 'trd-jewel1/master/material/detail';
        $this->customValidationAttributes  = [
            'materials'                => $this->trans('input'),
            'materials.*'              => $this->trans('input'),
            'materials.code'      => $this->trans('code'),
            'materials.jwl_category1'      => $this->trans('category1'),
            'materials.jwl_category2'      => $this->trans('category2'),
            'materials.jwl_wgt_gold'      => $this->trans('weight'),
            'materials.jwl_carat' => $this->trans('carat'),
            'materials.code'      => $this->trans('uom'),
            'materials.name'      => $this->trans('description'),
            'materials.descr'      => $this->trans('bom_description'),
            'materials.remark'      => $this->trans('remark'),
            'matl_uoms.barcode'      => $this->trans('barcode'),
            'materials.jwl_cost' => $this->trans('jwl_cost'),
            'materials.gold_price'      => $this->trans('gold_price'),
            'materials.jwl_buying_price_usd'      =>  $this->trans('buying_price_usd'),
            'materials.jwl_selling_price_usd'      => $this->trans('selling_price_usd'),
            'materials.jwl_buying_price_idr'      =>  $this->trans('buying_price_idr'),
            'materials.jwl_selling_price_idr'      => $this->trans('selling_price_idr'),
            'matl_boms.*.base_matl_id' => $this->trans('material'),
            'matl_boms.*.jwl_sides_cnt' => $this->trans('quantity'),
            'matl_boms.*.jwl_sides_carat' => $this->trans('carat'),
            'matl_boms.*.jwl_sides_price' => $this->trans('price'),
            'matl_boms.*.matl_origin_id' => $this->trans('origin'),
        ];
        $this->masterService = new MasterService();
        $this->baseMaterials = $this->masterService->getMatlBaseMaterialData($this->appCode);
        $this->materialUOMs = $this->masterService->getUOMData($this->appCode);
        $this->materialCategories1 = $this->masterService->getMatlCategory1Data($this->appCode);
        $this->materialCategories2 = $this->masterService->getMatlCategory2Data($this->appCode);
        $this->materialJewelPurity = $this->masterService->getMatlJewelPurityData($this->appCode);
        $this->sideMaterialShapes = $this->masterService->getMatlSideMaterialShapeData($this->appCode);
        $this->sideMaterialClarity = $this->masterService->getMatlSideMaterialClarityData($this->appCode);
        $this->sideMaterialCut = $this->masterService->getMatlSideMaterialCutData($this->appCode);
        $this->sideMaterialGemColors = $this->masterService->getMatlSideMaterialGemColorData($this->appCode);
        $this->sideMaterialGiaColors = $this->masterService->getMatlSideMaterialGiaColorData($this->appCode);
        $this->sideMaterialGemStone = $this->masterService->getMatlSideMaterialGemstoneData($this->appCode);
        $this->sideMaterialJewelPurity = $this->masterService->getMatlSideMaterialPurityData($this->appCode);
        $this->sideMaterialJewelOrigins = $this->masterService->getMatlSideMaterialOriginData($this->appCode);
        $this->partners = $this->masterService->getCustomers($this->appCode);


        if ($this->isEditOrView()) {
            $this->loadMaterial($this->objectIdValue);
            if ($this->object->isItemExistonOrder($this->objectIdValue)) {
                $this->actionValue = "View";
            }
        }
        $this->orderedMaterial = !isNullOrEmptyNumber($this->materials['partner_id']);
    }

    public function onCategory1Changed()
    {
        $this->materials['name'] = Material::generateMaterialDescriptions($this->materials);
        if(!$this->orderedMaterial){
            $this->materials['code'] = '';
        }
    }

    public function onPartnerChanged()
    {
        $this->orderedMaterial = !isNullOrEmptyNumber($this->materials['partner_id']);
        if(!$this->orderedMaterial){
            $this->materials['code'] = '';
        }
        else{
            if (!isNullOrEmptyString($this->materials['code']) && strpos($this->materials['code'], 'SO') !== 0) {
                $this->materials['code'] = '';
            }
        }
    }

    public function onReset()
    {
        $this->product_code = "";
        $this->reset('materials');
        $this->materials['partner_id'] = 0;
        $this->materials['jwl_category1'] = "";
        $this->materials['jwl_category2'] = "";
        $this->materials['jwl_carat'] = "";
        $this->materials['code'] = '';
        $this->matl_uoms['matl_uom'] = 'PCS';
        $this->materials['markup'] = 0;
        $this->reset('matl_uoms');
        $this->reset('matl_boms');
        $this->object = new Material();
        $this->object_uoms = new MatlUom();
        $this->object_boms = [];
        $this->deletedItems = [];
        $this->capturedImages = [];
        $this->bom_row = 0;
    }

    protected function loadMaterial($objectId)
    {
        $this->object = Material::withTrashed()->find($objectId);
        if ($this->object) {
            $this->object_uoms = $this->object->MatlUom[0];
            $this->object_boms = $this->object->MatlBom;
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms);
            foreach ($this->object_boms as $key => $detail) {
                $formattedDetail = populateArrayFromModel($detail);
                $this->matl_boms[$key] =  $formattedDetail;
                $this->matl_boms[$key]['id'] = $detail->id;

                $baseMaterial = ConfigConst::where('id', $detail->base_matl_id)->first();
                $this->matl_boms[$key]['base_matl_id'] = strval($baseMaterial->id) . "-" . strval($baseMaterial->note1);

                $this->matl_boms[$key]['base_matl_id_value'] =  $baseMaterial->id;
                $this->matl_boms[$key]['base_matl_id_note'] =  $baseMaterial->note1;

                $decodedData = json_decode($detail->jwl_sides_spec, true);
                switch ($this->matl_boms[$key]['base_matl_id_note']) {
                    case Material::JEWELRY:
                        $this->matl_boms[$key]['purity'] = $decodedData['purity'] ?? null;
                        break;
                    case Material::DIAMOND:
                        $this->matl_boms[$key]['shapes'] = $decodedData['shapes'] ?? null;
                        $this->matl_boms[$key]['clarity'] = $decodedData['clarity'] ?? null;
                        $this->matl_boms[$key]['color'] = $decodedData['color'] ?? null;
                        $this->matl_boms[$key]['cut'] = $decodedData['cut'] ?? null;
                        $this->matl_boms[$key]['gia_number'] = $decodedData['gia_number'] ?? 0;
                        break;
                    case Material::GEMSTONE:
                        $this->matl_boms[$key]['gemstone'] = $decodedData['gemstone'] ?? null;
                        $this->matl_boms[$key]['gemcolor'] = $decodedData['gemcolor'] ?? null;
                        break;
                    case Material::GOLD:
                        $this->matl_boms[$key]['production_year'] = $decodedData['production_year'] ?? 0;
                        $this->matl_boms[$key]['ref_mark'] = $decodedData['ref_mark'] ?? null;
                        break;
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
        $this->orderedMaterial = !isNullOrEmptyNumber($this->materials['partner_id']);
    }

    public function searchProduct()
    {
        if (isset($this->product_code)) {
            $this->product_code = strtoupper($this->product_code);

            $material = Material::where('code', $this->product_code)->first();

            if ($material) {
                if ($material->isItemExistonAnotherPO($material->id)) {
                    $this->notify('error', "Penerimaan barang sudah dibuat untuk item ini");
                } else {
                    $this->loadMaterial($material->id);
                }
            } else {
                $this->notify('error', __($this->langBasePath . '.message.product_notfound'));
            }
        }
    }


    public function submitImages($imageByteArrays)
    {
        foreach ($imageByteArrays as $byteArray) {
            $dataUrl = 'data:image/jpeg;base64,' . base64_encode(implode('', array_map('chr', $byteArray)));
            $filename = uniqid() . '.jpg';
            $this->capturedImages[] = ['url' => $dataUrl, 'filename' => $filename];
        }
    }

    public function captureImages($imageData)
    {
        $filename = uniqid() . '.jpg';
        $this->capturedImages[] = ['url' => $imageData, 'filename' => $filename];
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
                        $errorMessages[] = __($this->langBasePath . '.message.attachment_failed', ['filename' => $image['filename']]);
                    }
                } catch (Exception $e) {
                    $errorMessages[] = __($this->langBasePath . '.message.attachment_failed', ['filename' => $image['filename']]);
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
        'captureImages'  => 'captureImages',
        'runExe'  => 'runExe',
        'submitImages'  => 'submitImages',
        'changeStatus'  => 'changeStatus',
        'tagScanned' => 'tagScanned',
        'resetMaterial' => 'onReset',
        'onPartnerChanged' => 'onPartnerChanged'
    ];

    public function changeStatus()
    {
        $this->changeMaterial();
    }

    protected function changeMaterial()
    {
        try {
            $this->updateVersionNumber();
            if ($this->object->deleted_at) {
                if (isset($this->object->status_code)) {
                    $this->object->status_code =  Status::ACTIVE;
                }
                $this->object->deleted_at = null;
                $messageKey = 'generic.string.enable';
            } else {
                if ($this->object->getAvailableMaterials()) {
                    $this->notify('error', "Barang masih ada di inventory, tidak bisa di nonaktifkan!");
                    return;
                }
                if (isset($this->object->status_code)) {
                    $this->object->status_code =  Status::NONACTIVE;
                }
                $this->object->save();
                $this->object->delete();
                $messageKey = 'generic.string.disable';
            }

            $this->object->save();
            $this->notify('success', __($messageKey));
        } catch (Exception $e) {
            $this->notify('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatch('refresh');
    }

    public function getMatlCode()
    {
        $code = "";
        $configSnum = null;
        if (!isNullOrEmptyNumber($this->materials['partner_id'])) {
            $configSnum = ConfigSnum::where('app_code', '=', $this->appCode)
                ->where('code', '=', 'MMATL_SO_LASTID')
                ->first();
            $code = "SO";
        } else {
            if (!isNullOrEmptyString($this->materials['jwl_category1'])) {
                $configSnum = ConfigSnum::where('app_code', '=', $this->appCode)
                    ->where('code', '=', 'MMATL_' . $this->materials['jwl_category1'] . '_LASTID')
                    ->first();
                 $code = $this->materials['jwl_category1'];
            } else {
                $this->notify('error', "Mohon pilih customer/kategori1 untuk mendapatkan material code.");
                return;
            }
        }

        if ($configSnum != null) {
            $stepCnt = $configSnum->step_cnt;
            $proposedTrId = $configSnum->last_cnt + $stepCnt;
            if ($proposedTrId > $configSnum->wrap_high) {
                $proposedTrId = $configSnum->wrap_low;
            }
            $proposedTrId = max($proposedTrId, $configSnum->wrap_low);

            // Set kode material dan update configSnum
            $this->materials['code'] = $code . $proposedTrId;
            $configSnum->last_cnt = $proposedTrId;
            $configSnum->save();
        } else {
            $this->notify('error', "Tidak ada kode ditemukan untuk kategori produk ini.");
        }
    }


    public function onValidateAndSave()
    {
        if ($this->orderedMaterial) {
            // Validate IDR prices
            if ($this->materials['jwl_selling_price_idr'] <= 0) {
                $this->addError('materials.jwl_selling_price_idr', ' IDR harga penjualan harus lebih besar dari 0.');
                throw new \Exception('IDR harga penjualan harus lebih besar dari 0.');
            }
            if ($this->materials['jwl_buying_price_idr'] <= 0) {
                $this->addError('materials.jwl_buying_price_idr', ' IDR harga pembelian harus lebih besar dari 0.');
                throw new \Exception('IDR harga pembelian harus lebih besar dari 0.');
            }
        } else {
            // Validate USD prices
            if ($this->materials['jwl_buying_price_usd'] <= 0) {
                $this->addError('materials.jwl_buying_price_usd', ' USD harga pembelian harus lebih besar dari 0.');
                throw new \Exception('USD harga pembelian harus lebih besar dari 0.');
            }

            if ($this->materials['jwl_selling_price_usd'] <= 0) {
                $this->addError('materials.jwl_selling_price_usd', ' USD harga penjualan harus lebih besar dari 0.');
                throw new \Exception('USD harga penjualan harus lebih besar dari 0.');
            }
        }

        $this->generateMaterialDescriptionsFromBOMs();
        $this->object->fillAndSanitize($this->materials);

        // Cek kode material di database
        if($this->object->isNew())
        {
            $code = $this->materials['code'];
            if (isNullOrEmptyNumber($this->materials['partner_id'])) {
                if (strpos($code, $this->materials['jwl_category1']) !== 0) {
                    throw new \Exception("Kode material harus sesuai dengan kategori -> " . $this->materials['jwl_category1']);
                }
            } else {
                if (strpos($code, 'SO') !== 0) {
                    throw new \Exception("Kode material harus dimulai dengan 'SO' untuk barang pesanan.");
                }
            }
            $codeLetter = preg_replace('/\d/', '', $code);
            $configCode = 'MMATL_' . $codeLetter . '_LASTID';
            $existingMaterial = Material::where('code', '=', $code)->first();
            if ($existingMaterial) {
                throw new \Exception("Kode material telah digunakan.");
            }
            $configSnum = ConfigSnum::where('app_code', '=', $this->appCode)
                ->where('code', '=', $configCode)
                ->first();

            if ($configSnum != null) {
                $currentNumber = (int)preg_replace('/\D/', '', $code);
                // if ($configSnum->last_cnt == $currentNumber) {
                //     throw new \Exception("Kode material telah digunakan.");
                // }

                if ($configSnum->last_cnt < $currentNumber) {
                    $configSnum->last_cnt = $currentNumber;
                    $configSnum->save();
                }
            }
        }

        $this->object->save();

        // Save Attachment
        $this->saveAttachment();
        $this->matl_uoms['matl_id'] = $this->object->id;
        $this->matl_uoms['matl_code'] = $this->object->code;
        $this->object_uoms->fillAndSanitize($this->matl_uoms);

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
            $bomData['base_matl_id'] = $bomData['base_matl_id_value'];
            $baseMaterialId = $bomData['base_matl_id_note'];
            $dataToSave = [];
            if (in_array($baseMaterialId, [Material::JEWELRY])) {
                $dataToSave['purity'] = $bomData['purity'] ?? null;
            } elseif (in_array($baseMaterialId, [Material::DIAMOND])) {
                $dataToSave = [
                    'shapes' => $bomData['shapes'] ?? null,
                    'clarity' => $bomData['clarity'] ?? null,
                    'color' => $bomData['color'] ?? null,
                    'cut' => $bomData['cut'] ?? null,
                    'gia_number' => $bomData['gia_number'] ?? 0,
                ];
            } elseif (in_array($baseMaterialId, [Material::GEMSTONE])) {
                $dataToSave = [
                    'gemstone' => $bomData['gemstone'] ?? null,
                    'gemcolor' => $bomData['gemcolor'] ?? null,
                ];
            } elseif (in_array($baseMaterialId, [Material::GOLD])) {
                $dataToSave = [
                    'production_year' => $bomData['production_year'] ?? 0,
                    'ref_mark' => $bomData['ref_mark'] ?? null,
                ];
            }
            $bomData['jwl_sides_spec'] = json_encode($dataToSave);
            $this->object_boms[$index]->fillAndSanitize($bomData);
            $this->object_boms[$index]->save();
        }

        if (!$this->object->isNew()) {
            foreach ($this->deletedItems as $deletedItemId) {
                MatlBom::find($deletedItemId)->forceDelete();
            }
        }
        $this->dispatch('materialSaved', $this->object->id);
    }

    public function generateMaterialDescriptions()
    {
        $this->materials['name'] = Material::generateMaterialDescriptions($this->materials);
    }

    public function baseMaterialChange($key, $value)
    {
        $base_matl_id_parts = explode('-', $value);
        $this->matl_boms[$key]['base_matl_id_value'] = $base_matl_id_parts[0];
        $this->matl_boms[$key]['base_matl_id_note'] =  $base_matl_id_parts[1];
        $this->generateMaterialDescriptionsFromBOMs();
    }

    public function generateMaterialDescriptionsFromBOMs()
    {
        $this->materials['descr'] = Material::generateMaterialDescriptionsFromBOMs($this->matl_boms);
    }

    public function addBoms()
    {
        $bomsDetail['jwl_sides_price'] = 0;
        $bomsDetail['jwl_sides_cnt'] = 1;
        $bomsDetail['base_matl_id_value'] = "";
        $bomsDetail['base_matl_id_note'] = "";
        $this->matl_boms[] = $bomsDetail;

        $this->matl_boms[$this->bom_row]['base_matl_id'] = "";
        $this->matl_boms[$this->bom_row]['shapes'] = "";
        $this->matl_boms[$this->bom_row]['clarity'] = "";
        $this->matl_boms[$this->bom_row]['cut'] = "";
        $this->matl_boms[$this->bom_row]['gemcolor'] = "";
        $this->matl_boms[$this->bom_row]['color'] = "";
        $this->matl_boms[$this->bom_row]['gemstone'] = "";
        $this->matl_boms[$this->bom_row]['purity'] = "";
        $this->matl_boms[$this->bom_row]['origin'] = 0;
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
        if (empty($this->materials['jwl_buying_price_usd'])) {
            return;
        }
        $this->materials['jwl_selling_price_usd'] = Material::calculateSellingPrice($this->materials['jwl_buying_price_usd'], $this->materials['markup']);
    }

    public function sellingPriceChanged()
    {
        if($this->orderedMaterial){
            $this->materials['jwl_buying_price_idr'] = $this->materials['jwl_selling_price_idr'];
            return;
        }
        if (empty($this->materials['jwl_buying_price_usd'])) {
            return;
        }
        $this->materials['markup'] = Material::calculateMarkup($this->materials['jwl_buying_price_usd'], $this->materials['jwl_selling_price_usd']);
    }

    public function tagScanned($tags)
    {
        if (isset($tags)) {
            $tagCount = count($tags);
            if ($tagCount > 1) {
                // Show error message with the number of tags
                $this->notify('error', "Terdapat {$tagCount} tag, mohon scan kembali!");
            } else {
                // Process a single tag
                if (!$this->isUniqueBarcode($tags)) {
                    $this->notify('error', 'RFID telah digunakan sebelumnya');
                } else {
                    $this->matl_uoms['barcode'] = $tags[0];
                    $this->notify('success', 'RFID berhasil discan');
                }
            }
        }
    }

    protected function isUniqueBarcode($barcode)
    {
        $query = MatlUom::where('barcode', $barcode);
        if (isset($this->object_id)) {
            $query->where('matl_id', '!=', $this->object_id);
        }

        return !$query->exists();
    }

    public function printBarcode()
    {
        if (isset($this->matl_uoms['barcode'])) {
            if (isset($this->materials['descr'])) {
                $additionalParam = urlencode($this->object->id);
                return redirect()->route('TrdJewel1.Master.Material.PrintPdf', ["action" => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey(""), 'additionalParam' => $additionalParam]);
            } else {
                $this->notify('error', __($this->langBasePath . '.message.save_material_input'));
            }
        } else {
            $this->notify('error', __($this->langBasePath . '.message.barcode_validation'));
        }
    }
}
