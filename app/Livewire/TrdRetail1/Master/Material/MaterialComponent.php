<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB};
use App\Models\TrdRetail1\Master\{Material, MatlUom};
use App\Models\SysConfig1\ConfigSnum;
use App\Models\Base\Attachment;
use App\Services\TrdRetail1\Master\MasterService;
use App\Enums\Status;
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

    // UomListComponent properties
    public $object_detail;
    public $input_details = [];

    protected $masterService;

    public $rules = [
        'materials.code' => 'required|string|max:255',
        'materials.category' => 'required',
        'materials.seq' => 'required',
        // 'materials.name' => 'required|string|max:255',
        'materials.remark' => 'nullable|string|max:500',
        'materials.brand' => 'nullable|string|max:255',
        'materials.class_code' => 'nullable|string|max:255',
        // 'materials.color_code' => 'required|string|max:50',
        // 'materials.color_name' => 'required|string|max:100',
        'matl_uoms.selling_price' => 'nullable|numeric|min:0',
        'materials.buying_price' => 'nullable|numeric|min:0',
        // 'materials.stock' => 'required|integer|min:0',
        'materials.tag' => 'nullable|string|max:255',
        'matl_uoms.barcode' => 'nullable',
        // UomListComponent rules
        'input_details.*.matl_uom' => 'required|string|max:50',
        'input_details.*.reff_uom' => 'required|string|max:50',
        'input_details.*.reff_factor' => 'required|numeric|min:1',
        'input_details.*.base_factor' => 'required|numeric|min:1',
        'input_details.*.barcode' => 'nullable|string|max:50',
        'input_details.*.selling_price' => 'nullable|numeric|min:0',
    ];

    protected $listeners = [
        'changeStatus' => 'changeStatus',
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
            // UomListComponent validations
            'input_details.*.matl_uom' => $this->trans('Base UOM'),
            'input_details.*.reff_uom' => $this->trans('Reff UOM'),
            'input_details.*.reff_factor' => $this->trans('Reff Factor'),
            'input_details.*.base_factor' => $this->trans('Base Factor'),
            'input_details.*.barcode' => $this->trans('barcode'),
            'input_details.*.selling_price' => $this->trans('selling_price'),
        ];

        $this->masterService = new MasterService();
        $this->materialCategories = $this->masterService->getMatlCategoryData();
        $this->materialUOM = $this->masterService->getMatlUOMData();
        if ($this->isEditOrView()) {
            $this->loadMaterial($this->objectIdValue);
            $this->loadUomDetails();
        }
    }

    public function onReset()
    {
        $this->product_code = '';
        $this->reset('materials');
        $this->reset('matl_uoms');
        $this->reset('input_details');
        $this->input_details = [[
            'matl_uom'     => 'PCS',
            'reff_uom'     => 'PCS',
            'reff_factor'  => 1,
            'base_factor'  => 1,
            'barcode'      => '',
            'selling_price'=> 0,
        ]];
        $this->object = new Material();
        $this->object_uoms = new MatlUom();
        $this->materials['category'] = '';
        $this->matl_uoms['uom'] = '';
        $this->capturedImages = [];
    }

    protected function loadMaterial($objectId)
    {
        $this->object = Material::withTrashed()->find($objectId);
        if ($this->object) {
            $this->materials = populateArrayFromModel($this->object);
            $attachments = $this->object->Attachment;
            $specs = $this->object->specs ?? [];
            $this->materials['color_code'] = $specs['color_code'] ?? '';
            $this->materials['color_name'] = $specs['color_name'] ?? '';
            $this->materials['size'] = $specs['size'] ?? '';
            $this->materials['stock'] = $this->object->Stock;
            foreach ($attachments as $attachment) {
                $this->capturedImages[] = [
                    'url' => $attachment->getUrl(),
                    'filename' => $attachment->name,
                ];
            }
        }
    }

    // UomListComponent loadDetails method
    protected function loadUomDetails()
    {
        if (!empty($this->objectIdValue)) {
            $uoms = MatlUom::withTrashed()->where('matl_id', $this->objectIdValue)->get();
            $this->input_details = $uoms
                ->map(function ($uom) {
                    return [
                        'id' => $uom->id,
                        'matl_uom' => $uom->matl_uom,
                        'reff_uom' => $uom->reff_uom,
                        'reff_factor' => $uom->reff_factor ?? 1,
                        'base_factor' => $uom->base_factor ?? 1,
                        'barcode' => $uom->barcode,
                        'selling_price' => $uom->selling_price,
                        'buying_price' => $uom->buying_price,
                        'qty_oh' => $uom->qty_oh,
                        'deleted_at' => $uom->deleted_at,
                    ];
                })
                ->toArray();
        }
    }

    public function render()
    {
        return view(getViewPath(__NAMESPACE__, class_basename($this)));
    }
    #endregion

    #region CRUD Methods
    public function toggleUomStatus($index)
    {
        // ambil data detail di index
        if (!isset($this->input_details[$index])) {
            $this->dispatch('error', 'UOM item tidak ditemukan.');
            return;
        }

        $detail = $this->input_details[$index];
        $uom = MatlUom::withTrashed()->find($detail['id']);
        if (!$uom) {
            $this->dispatch('error', 'UOM record tidak ada.');
            return;
        }

        if ($uom->trashed()) {
            $parent = Material::withTrashed()->find($uom->matl_id);
            if (!$parent || $parent->trashed()) {
                $this->dispatch('error', 'Material induk masih non-aktif. Silakan aktifkan material terlebih dahulu.');
                return;
            }

            // restore UOM setelah material oke
            $uom->restore();
            $uom->status_code = Status::ACTIVE;
            $uom->save();
            $this->dispatch('success', 'UOM berhasil di-aktifkan.');
        }
         else {
            // soft‑delete
            $uom->status_code = Status::NONACTIVE;
            $uom->save();
            $uom->delete();
            $this->dispatch('success', 'UOM berhasil di‑non‑aktifkan.');
        }

        // reload detail agar view ter‑update
        $this->loadUomDetails();
    }

    public function onValidateAndSave()
    {
        $this->materials['color_code'] = strtoupper(str_replace(' ', '', $this->materials['color_code']));
        // 3. Siapkan data specs, dsb. (terserah logika Anda)
        $this->materials['specs'] = [
            'color_code' => $this->materials['color_code'] ?? '',
            'color_name' => $this->materials['color_name'] ?? '',
            'size' => $this->materials['size'] ?? '',
        ];

        // 4. Buat tag & generateName (sesuai logika internal Anda)
        $this->materials['tag'] = Material::generateTag(
            $this->materials['name'] ?? '',
            $this->materials['code'] ?? '',
            $this->object->MatlUom, // atau null, tergantung implementasi
            $this->materials['brand'] ?? '',
            $this->materials['class_code'] ?? '',
            $this->materials['specs'],
        );
        $this->generateName();
        $query = Material::where('name', $this->materials['name'])
        ->where('category', $this->materials['category']);
        if ($this->object->id) {
            $query->where('id', '!=', $this->object->id);
        }

        if ($query->exists()) {
            $this->dispatch('error', 'Nama material dengan kategori ini sudah ada.');
            return;
        }

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
        foreach ($this->input_details as $key => $detail) {
            $matlUom = MatlUom::withTrashed()->updateOrCreate(
                ['matl_id' => $this->object->id, 'matl_uom' => $detail['matl_uom']],
                [
                    'reff_uom'     => $detail['reff_uom'],
                    'reff_factor'  => $detail['reff_factor'] ?? 1,
                    'base_factor'  => $detail['base_factor'] ?? 1,
                    'barcode'      => $detail['barcode'],
                    'selling_price'=> $detail['selling_price'],
                    'buying_price' => $detail['buying_price'] ?? 0,
                ]
            );
            $this->input_details[$key]['id'] = $matlUom->id;
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
        $code = '';
        $configSnum = null;
        if (!isNullOrEmptyString($this->materials['category'])) {
            $configSnum = ConfigSnum::where('code', '=', 'MMATL_' . $this->materials['category'] . '_LASTID')->first();
            $code = $this->materials['category'];
        } else {
            $this->dispatch('error', 'Mohon pilih kategori untuk mendapatkan material code.');
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
            $this->dispatch('error', 'Tidak ada kode ditemukan untuk kategori produk ini.');
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
        $colorName = $this->materials['color_name'] ?? '';
        $generated = Material::generateName($category, $brand, $classCode, $colorCode, $colorName);
        if ($generated !== '') {
            $this->materials['name'] = $generated;
        }
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
        $this->loadUomDetails();
    }

    // UomListComponent methods
    public function addItem()
    {
        $this->input_details[] = [
            'matl_uom' => '',
            'reff_uom' => '',
            'reff_factor' => 1, // Default 1
            'base_factor' => 1, // Default 1
            'barcode' => '',
            'selling_price' => 0,
        ];
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);
            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function printBarcode($index)
    {
        if (isset($this->input_details[$index])) {
            $itemId = (string) $this->input_details[$index]['id'];
            $itemBarcode = MatlUom::find($itemId);

            if ($itemBarcode) {
                $itemBarcodeString = (string) $itemBarcode->barcode;

                if ($itemBarcodeString !== (string) $this->input_details[$index]['barcode']) {
                    $this->dispatch('error', 'Mohon save item terlebih dahulu');
                } else {
                    return redirect()->route($this->appCode . '.Master.Material.PrintPdf', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($itemId),
                    ]);
                }
            }
        }
    }
    #endregion
}
