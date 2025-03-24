<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB};
use App\Models\TrdRetail1\Master\{Material, MatlUom};
use App\Models\SysConfig1\ConfigSnum;
use App\Models\Base\Attachment;
use App\Services\TrdRetail1\Master\MasterService;
use Livewire\WithFileUploads;
use Exception;


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
    public $materialUOM;

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
        // 'materials.name' => 'required|string|max:255',
        'materials.remark' => 'nullable|string|max:500',
        'materials.brand' => 'required|string|max:255',
        'materials.class_code' => 'required|string|max:255',
        // 'materials.color_code' => 'required|string|max:50',
        // 'materials.color_name' => 'required|string|max:100',
        'matl_uoms.selling_price' => 'nullable|numeric|min:0',
        'materials.buying_price' => 'nullable|numeric|min:0',
        // 'materials.stock' => 'required|integer|min:0',
        'materials.tag' => 'nullable|string|max:255',
        'matl_uoms.barcode' => 'nullable',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
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
            'materials.class_code' => $this->trans('type'),
            'materials.color_code' => $this->trans('color_code'),
            'materials.color_name' => $this->trans('color_name'),
            'matl_uoms.selling_price' => $this->trans('selling_price'),
            'materials.buying_price' => $this->trans('buying_price'),
            'materials.cogs' => $this->trans('cogs'),
            'materials.stock' => $this->trans('stock'),
            'materials.matl_uom' => $this->trans('uom'),
            'materials.tag' => $this->trans('tag'),
            'matl_uoms.barcode' => $this->trans('barcode'),
        ];

        $this->masterService = new MasterService();
        $this->materialCategories = $this->masterService->getMatlCategoryData();
        $this->materialUOM = $this->masterService->getMatlUOMData();
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
        $this->object = Material::withTrashed()->find($objectId);

        if ($this->object) {
            $this->object_uoms = $this->object->DefaultUom;
            $this->materials = populateArrayFromModel($this->object);
            $this->matl_uoms = populateArrayFromModel($this->object_uoms);
            $attachments = $this->object->Attachment;
            $specs = $this->object->specs ?? [];
            $this->materials['color_code'] = $specs['color_code'] ?? '';
            $this->materials['color_name'] = $specs['color_name'] ?? '';
            $this->materials['stock'] = $this->object->Stock;
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
        // 1. Pastikan input 'uom' tidak kosong
        $selectedUOM = $this->materials['uom'] ?? null;
        if (!$selectedUOM) {
            throw new \Exception('UOM tidak boleh kosong.');
        }

        // 2. Jika data Material lama (bukan baru), pastikan UOM sudah terdaftar
        if (!$this->object->isNew()) {
            $exists = MatlUom::where('matl_id', $this->object->id)
                ->where('matl_uom', $selectedUOM)
                ->exists();

            if (!$exists) {
                throw new \Exception("UOM '$selectedUOM' tidak ditemukan dalam daftar UOM yang valid untuk material ini.");
            }
        }

        // 3. Siapkan data specs, dsb. (terserah logika Anda)
        $this->materials['specs'] = [
            'color_code' => $this->materials['color_code'] ?? '',
            'color_name' => $this->materials['color_name'] ?? '',
        ];

        // 4. Buat tag & generateName (sesuai logika internal Anda)
        $this->materials['tag'] = Material::generateTag(
            $this->materials['code']       ?? '',
            $this->object->MatlUom,  // atau null, tergantung implementasi
            $this->materials['brand']      ?? '',
            $this->materials['class_code'] ?? '',
            $this->materials['specs']
        );
        $this->generateName(); // method apa pun yang Anda punya

        // 5. Isi model Material dengan data input
        $this->object->fill($this->materials);

        // 6. Jika baru, pastikan kode material valid (misal unique)
        if ($this->object->isNew()) {
            $this->validateMaterialCode();
        }

        // 7. Simpan Material agar mendapatkan ID (jika baru), atau update (jika lama)
        $this->object->save();

        // 8. Jika Material masih baru (artinya baru saja disimpan) -> Buat MatlUom
        //    (Jika TIDAK baru, artinya UOM sudah dicek, tidak perlu bikin lagi)
        if ($this->object->wasRecentlyCreated) {
            // Buat record MatlUom baru
            $matlUom = new MatlUom();
            $matlUom->matl_id       = $this->object->id;
            $matlUom->matl_uom      = $selectedUOM;
            $matlUom->barcode       = "";
            $matlUom->reff_uom      = $selectedUOM;
            $matlUom->reff_factor   = 1;
            $matlUom->base_factor   = 1;
            $matlUom->selling_price = 0;
            $matlUom->qty_oh        = 0;
            $matlUom->save();
        }

        // 9. Simpan attachment (jika ada)
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
    public function generateName()
    {
        $masterService = new MasterService();
        $category = $this->materials['category'] ?? '';
        $brand = $this->materials['brand'] ?? '';
        $classCode = $this->materials['class_code'] ?? '';
        $colorCode = $this->materials['color_code'] ?? '';
        $this->materials['name'] = Material::generateName($category, $brand, $classCode, $colorCode);
    }

    public function onCategoryChanged()
    {
        $this->materials['code'] = '';
        $this->generateName();
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

    public function changeStatus()
    {
        $this->change();
    }
    #endregion
}
