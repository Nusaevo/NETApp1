<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Master\MatlUom;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\Base\Attachment;
use Exception;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Services\TrdRetail1\Master\MasterService;

class MaterialComponent extends BaseComponent
{
    use WithFileUploads;

    #region Variables
    public $object_uoms;
    public $materials = [];
    public $matl_uoms = [];
    public $product_code = '';

    public $photo;

    public $materialCategories;
    public $materialUOMs;

    public $capturedImages = [];
    public $deleteImages = [];

    public $isComponent = false;
    public $panelEnabled = 'true';
    public $btnAction = 'true';

    protected $masterService;

    public $rules = [
        'materials.code' => 'required|string|max:255',
        'materials.category' => 'required',
        'materials.seq' => 'required',
        'materials.name' => 'required|string|max:255',
        'materials.remark' => 'nullable|string|max:500',
        'materials.brand' => 'required|string|max:255',
        'materials.type_code' => 'required|string|max:255',
        // 'materials.color_code' => 'required|string|max:50',
        // 'materials.color_name' => 'required|string|max:100',
        'materials.selling_price' => 'nullable|numeric|min:0',
        'materials.buying_price' => 'nullable|numeric|min:0',
        'materials.cogs' => 'required|numeric|min:0',
        // 'materials.stock' => 'required|integer|min:0',
        'materials.tag' => 'nullable|string|max:255',
        'matl_uoms.matl_uom' => 'required|string|max:50',
        'matl_uoms.barcode' => 'nullable|string|max:255',
    ];

    protected $listeners = [
        'captureImages' => 'captureImages',
        'submitImages' => 'submitImages',
        'submitAttachmentsFromStorage' => 'submitAttachmentsFromStorage',
        'resetMaterial' => 'onReset',
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
        $this->panelEnabled = $this->actionValue === 'Create' ? 'true' : 'false';
        $this->customActionValue = 'Edit';
        $this->baseRoute = 'TrdRetail1.Master.Material.Detail';

        $this->customValidationAttributes = [
            'materials.code' => $this->trans('code'),
            'materials.seq' => $this->trans('seq'),
            'materials.category' => $this->trans('category'),
            'materials.name' => $this->trans('name'),
            'materials.remark' => $this->trans('remark'),
            'materials.brand' => $this->trans('brand'),
            'materials.type_code' => $this->trans('type'),
            'materials.color_code' => $this->trans('color_code'),
            'materials.color_name' => $this->trans('color_name'),
            'materials.selling_price' => $this->trans('selling_price'),
            'materials.buying_price' => $this->trans('buying_price'),
            'materials.cogs' => $this->trans('cogs'),
            'materials.stock' => $this->trans('stock'),
            'materials.matl_uom' => $this->trans('uom'),
            'materials.tag' => $this->trans('tag'),
            'matl_uoms.barcode' => $this->trans('barcode'),
        ];

        $this->masterService = new MasterService();
        $this->materialCategories = $this->masterService->getMatlCategoryData();
        $this->materialUOMs = $this->masterService->getMatlUOMData();

        if ($this->isEditOrView()) {
            $this->loadMaterial($this->objectIdValue);
        }
    }
    public function onReset()
    {
        $this->product_code = '';
        $this->reset('materials');
        $this->reset('matl_uoms');
        $this->object = new Material();
        $this->object_uoms = new MatlUom();
        $this->materials['category'] = "";
        $this->matl_uoms['uom'] = "";
        $this->capturedImages = [];
    }

    protected function loadMaterial($objectId)
    {
        $this->object = Material::find($objectId);
        if ($this->object) {
            $this->object_uoms = $this->object->MatlUom[0];
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms);
            $attachments = $this->object->Attachment;
            $specs = $this->object->specs ?? [];
            $this->materials['color_code'] = $specs['color_code'] ?? '';
            $this->materials['color_name'] = $specs['color_name'] ?? '';
            $this->materials['stock'] = $this->object->Stock;
            $this->materials['tag'] = $this->object->Tag;

            foreach ($attachments as $attachment) {
                $this->capturedImages[] = [
                    'url' => $attachment->getUrl(),
                    'filename' => $attachment->name,
                ];
            }
        }
    }

    public function render()
    {
        return view(getViewPath(__NAMESPACE__, class_basename($this)));
    }
    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        $this->materials['specs'] = [
            'color_code' => $this->materials['color_code'] ?? '',
            'color_name' => $this->materials['color_name'] ?? '',
        ];
        $this->masterService = new MasterService();
        $this->materials['name'] = $this->masterService->getMatlCategoryString($this->materials['category']). ' '
        . $this->materials['brand'] . ' '
        . $this->materials['type_code'];

        $this->object->fillAndSanitize($this->materials);
        if ($this->object->isNew()) {
            $this->validateMaterialCode();
        }

        $this->object->save();
        $this->saveUOMs();
        $this->saveAttachment();
    }

    private function validateMaterialCode()
    {
        $code = $this->materials['code'];
        $existingMaterial = Material::where('code', '=', $code)->first();
        if ($existingMaterial) {
            throw new \Exception($this->trans('code') . ' ' . $this->trans('already_used'));
        }
    }

    public function getMatlCode()
    {
        $code = "";
        $configSnum = null;
        if (!isNullOrEmptyString($this->materials['category'])) {
            $configSnum = ConfigSnum::where('code', '=', 'MMATL_' . $this->materials['category'] . '_LASTID')
                ->first();
            $code = $this->materials['category'];
        } else {
            $this->dispatch('error', "Mohon pilih kategori untuk mendapatkan material code.");
            return;
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
            $this->dispatch('error', "Tidak ada kode ditemukan untuk kategori produk ini.");
        }
    }
    private function saveUOMs()
    {
        $this->matl_uoms['matl_id'] = $this->object->id;
        $this->matl_uoms['matl_code'] = $this->object->code;
        $this->object_uoms->fillAndSanitize($this->matl_uoms);
        $this->object_uoms->save();
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
    #endregion

    #region Component Events
    public function onCategoryChanged()
    {
        $this->materials['code'] = '';
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
    #endregion
}
