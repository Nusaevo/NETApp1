<?php

namespace App\Livewire\TrdTire1\Master\Material;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB};
use Livewire\WithFileUploads;
use App\Models\TrdTire1\Master\{Material, MatlUom, Partner};
use App\Models\SysConfig1\ConfigSnum;
use App\Models\Base\Attachment;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Exception;


class MaterialComponent extends BaseComponent
{
    use WithFileUploads;
    #region Constant Variables

    public $materialType = [];
    public $materialJenis = [];
    public $materialMerk = [];
    public $materialUOM = [];
    public $object_uoms;
    public $matl_uoms = [];
    public $materials = [];
    public $product_code = "";
    public $capturedImages = [];
    public $deleteImages = [];
    public $deletedItems = [];



    protected $masterService;
    public $rules = [
        'materials.brand' => 'required|string',
        'materials.code' => 'required|string',
        'materials.type_code' => 'required|string',
        'materials.category' => 'required|string',
        'materials.selling_price' => 'required|numeric',
    ];


    protected $listeners = [
        'runExe'  => 'runExe',
        'captureImages'  => 'captureImages',
        'submitImages'  => 'submitImages',
        'submitAttachmentsFromStorage'  => 'submitAttachmentsFromStorage',
        'changeStatus'  => 'changeStatus',
        'tagScanned' => 'tagScanned',
        'resetMaterial' => 'onReset',
        'onPartnerChanged' => 'onPartnerChanged',
        'onNameChanged' => 'onNameChanged'  // Listen to onNameChanged
    ];
    #endregion

    #region Populate Data methods

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $isComponent = false)
    {
        $this->isComponent = $isComponent;
        $this->resetAfterCreate = !$isComponent;
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    protected function onPreRender()
    {
        $this->customActionValue = 'Edit';
        $this->baseRoute = $this->appCode . '.Master.Material.Detail';
        $this->customValidationAttributes  = [
            'materials'                => $this->trans('input'),
            'materials.*'              => $this->trans('input'),
            'materials.code'      => $this->trans('jenis'),
            'materials.type_code'      => $this->trans('jenis'),
            'materials.name'      => $this->trans('name'),
            'materials.brand'      => $this->trans('brand'),
            'materials.reserved'      => $this->trans('reserved'),
            'materials.tag'      => $this->trans('tag'),
            'materials.stok'      => $this->trans('stock'),
            'materials.category'      => $this->trans('category'),
            'materials.remark'      => $this->trans('remark'),
            'materials.jwl_cost' => $this->trans('jwl_cost'),
            'materials.gold_price'      => $this->trans('gold_price'),
            'materials.jwl_buying_price_usd'      =>  $this->trans('buying_price_usd'),
            'materials.jwl_selling_price_usd'      => $this->trans('selling_price_usd'),
            'materials.jwl_buying_price_idr'      =>  $this->trans('buying_price_idr'),
            'materials.selling_price'      => $this->trans('selling_price'),
        ];
        $this->masterService = new MasterService();
        $this->materialType = $this->masterService->getMatlTypeData();
        $this->materialJenis = $this->masterService->getMatlJenisData();
        $this->materialMerk = $this->masterService->getMatlMerkData();
        $this->materialUOM = $this->masterService->getMatlUOMData();
        $decodedData = $this->object->specs;
        $this->materials['size'] = $decodedData['size'] ?? null;
        $this->materials['pattern'] = $decodedData['pattern'] ?? null;

        if ($this->isEditOrView()) {
            $this->loadMaterial($this->objectIdValue);
        }
    }
    public function onReset()
    {
        $this->reset('materials');
        $this->reset('matl_uoms');
        $this->product_code = "";
        $this->materials['brand'] = "";
        $this->materials['type_code'] = "";
        $this->matl_uoms['matl_uom'] = 'PCS';
        $this->materials['code'] = '';
        $this->materials['class_code'] = '';
        $this->materials['markup'] = 0;
        $this->object = new Material();
        $this->object_uoms = new MatlUom();
        $this->deletedItems = [];
        $this->capturedImages = [];
    }
    protected function loadMaterial($objectId)
    {
        $this->object = Material::find($objectId);

        if ($this->object) {
            $this->materials = $this->object->toArray(); // Atau sesuaikan dengan cara Anda mengisi data
            $this->object_uoms = $this->object->MatlUom[0] ?? new MatlUom();
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms);

            // Decode the JSON from 'specs' and assign to $this->materials
            $decodedData = $this->object->specs;
            $this->materials['size'] = $decodedData['size'] ?? null;
            $this->materials['pattern'] = $decodedData['pattern'] ?? null;
            $this->materials['point'] = $decodedData['point'] ?? null;
        } else {
            // Tangani jika material tidak ditemukan
            $this->dispatch('error', 'Material tidak ditemukan.');
        }
        $attachments = $this->object->Attachment;
            foreach ($attachments as $attachment) {
                $url = $attachment->getUrl();
                $this->capturedImages[] = ['url' => $url, 'filename' => $attachment->name];
            }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods
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

        foreach ($this->capturedImages as $image) {
            if (isset($image['storage_id'])) {
                try {
                    Attachment::deleteAttachmentById($image['storage_id']);
                } catch (Exception $e) {
                }
            }
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
                    $this->dispatch('error', "Barang masih ada di inventory, tidak bisa di nonaktifkan!");
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
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatch('refresh');
    }

    public function onValidateAndSave()
    {
        $this->generateName();
        if ($this->object->isNew()) {
            $this->validateMaterialCode();
        }

        // Debugging
        \Log::info('Materials Data:', $this->materials);

        $dataToSave['size'] = $this->materials['size'] ?? null;
        $dataToSave['pattern'] = $this->materials['pattern'] ?? null;
        $dataToSave['point'] = $this->materials['point'] ?? null;


        $this->materials['specs'] = $dataToSave;

        $this->object->fillAndSanitize($this->materials);
        $this->object->save();
        $this->saveUOMs();
        $this->saveAttachment();

    }

    private function validateMaterialCode()
    {
        $code = $this->materials['code'];
        $this->checkExistingMaterial($code);
        $this->updateConfigSnum($code);
    }


    private function checkExistingMaterial($code)
    {
        $existingMaterial = Material::where('code', '=', $code)->first();
        if ($existingMaterial) {
            throw new \Exception("Kode material telah digunakan.");
        }
    }

    private function updateConfigSnum($code)
    {
        $codeLetter = preg_replace('/\d/', '', $code);
        $configCode = 'MMATL_' . $codeLetter . '_LASTID';
        $configSnum = ConfigSnum::where('code', '=', $configCode)
            ->first();

        if ($configSnum != null) {
            $currentNumber = (int)preg_replace('/\D/', '', $code);
            if ($configSnum->last_cnt < $currentNumber) {
                $configSnum->last_cnt = $currentNumber;
                $configSnum->save();
            }
        }
    }
    private function saveUOMs()
    {
        $this->matl_uoms['matl_id'] = $this->object->id;
        $this->matl_uoms['matl_code'] = $this->object->code;
        $this->object_uoms->fillAndSanitize($this->matl_uoms);
        $this->object_uoms->save();
    }

    #endregion

    #region Component Events

    public function generateName()
    {
        $this->materials['name'] = Partner::generateName($this->materials['brand'], $this->materials['size'], $this->materials['pattern']);
    }

    public function onBrandChanged()
    {
        $this->materials['code'] = '';
    }

    public function onPartnerChanged()
    {
        if (!isNullOrEmptyNumber($this->materials['partner_id'])) {
            $this->materials['code'] = '';
        } else {
            if (!isNullOrEmptyString($this->materials['code']) && strpos($this->materials['code'], 'SO') !== 0) {
                $this->materials['code'] = '';
            }
        }
    }
    public function searchProduct()
    {
        if (isset($this->product_code)) {
            $this->product_code = strtoupper($this->product_code);

            $material = Material::where('code', $this->product_code)->first();

            if ($material) {
                if ($material->isItemExistonAnotherPO($material->id)) {
                    $this->dispatch('error', "Penerimaan barang sudah dibuat untuk item ini");
                } else {
                    $this->loadMaterial($material->id);
                }
            } else {
                $this->dispatch('error', __($this->langBasePath . '.message.product_notfound'));
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

    public function submitAttachmentsFromStorage($attachmentIds)
    {
        foreach ($attachmentIds as $attachmentId) {
            $attachment = Attachment::find($attachmentId);
            if ($attachment) {
                $url = $attachment->getUrl();
                $imageData = file_get_contents($url);
                $imageBase64 = base64_encode($imageData);
                $dataUrl = 'data:image/jpeg;base64,' . $imageBase64;

                $filename = uniqid() . '.jpg';

                $this->capturedImages[] = ['url' => $dataUrl, 'filename' => $filename, 'storage_id' => $attachment->id];
                $this->dispatch('success', 'Images submitted successfully.');
                $this->dispatch('closeStorageDialog');

            } else {
                $this->dispatch('error', 'Attachment with ID ' . $attachmentId . ' not found.');
            }
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

    public function getMatlCode()
    {
        $code = "";
        $configSnum = null;
        if (!isNullOrEmptyString($this->materials['brand'])) {
            $configSnum = ConfigSnum::where('code', '=', 'MMATL_' . $this->materials['brand'] . '_LASTID')
                ->first();
            $code = $this->materials['brand'];
        } else {
            $this->dispatch('error', "Mohon pilih merk untuk mendapatkan material code.");
            return;
        }

        if ($configSnum != null) {
            $stepCnt = $configSnum->step_cnt;
            $proposedTrId = $configSnum->last_cnt + $stepCnt;
            if ($proposedTrId > $configSnum->wrap_high) {
                $proposedTrId = $configSnum->wrap_low;
            }
            $proposedTrId = max($proposedTrId, $configSnum->wrap_low);

            // Format the proposed ID to 4 digits with leading zeros
            $formattedTrId = str_pad($proposedTrId, 4, '0', STR_PAD_LEFT);

            // Set kode material dan update configSnum
            $this->materials['code'] = $code . $formattedTrId;
            $configSnum->last_cnt = $proposedTrId;
            $configSnum->save();
        } else {
            $this->dispatch('error', "Tidak ada kode ditemukan untuk kategori produk ini.");
        }
    }


    #endregion

}
