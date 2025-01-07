<?php

namespace App\Livewire\TrdTire1\Master\Material;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\Material;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\TrdTire1\Master\MatlUom;
use Exception;
use Livewire\WithFileUploads;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;

class MaterialComponent extends BaseComponent
{
    use WithFileUploads;
    #region Constant Variables

    public $materialType = [];
    public $materialJenis = [];
    public $materialMerk = [];
    public $object_uoms;
    public $matl_uoms = [];

    public $materials = [];
    public $product_code = "";


    protected $masterService;
    public $rules = [
        'materials.brand' => 'required|string|max:255',
        'materials.code' => 'required|string|max:255',
        'materials.type_code' => 'required|string|max:255',
        'materials.category' => 'required|string|max:255',
        'materials.name' => 'required|string|max:255',
        'materials.reserved' => 'required|string|max:255',
        'materials.tag' => 'required|string|max:255',
    ];


    protected $listeners = [
        'runExe'  => 'runExe',
        'submitImages'  => 'submitImages',
        'changeStatus'  => 'changeStatus',
        'tagScanned' => 'tagScanned',
        'resetMaterial' => 'onReset',
        'onPartnerChanged' => 'onPartnerChanged'
    ];
    #endregion

    #region Populate Data methods
    public function MatlUom()
    {
        return $this->hasMany(MatlUom::class, 'matl_id');
    }

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $searchMode = false)
    {
        $this->resetAfterCreate = !$searchMode;
        $this->bypassPermissions = $searchMode;
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
        $decodedData = $this->object->specs;
        $this->materials['size'] = $decodedData['size'] ?? null;
        $this->materials['pattern'] = $decodedData['pattern'] ?? null;

        if ($this->isEditOrView()) {
            $this->loadMaterial($this->objectIdValue);
        }
    }
    public function onReset()
    {
        $this->product_code = "";
        $this->reset('materials');
        $this->materials['brand'] = "";
        $this->materials['type_code'] = "";
        $this->matl_uoms['matl_uom'] = 'PCS';
        $this->materials['code'] = '';
        $this->materials['class_code'] = '';
        $this->materials['markup'] = 0;
        $this->reset('matl_uoms');
        $this->object = new Material();
        $this->object_uoms = new MatlUom();
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
            $decodedData = json_decode($this->object->specs, true);
            $this->materials['size'] = $decodedData['size'] ?? null;
            $this->materials['pattern'] = $decodedData['pattern'] ?? null;
            $this->materials['point'] = $decodedData['point'] ?? null;
        } else {
            // Tangani jika material tidak ditemukan
            $this->dispatch('error', 'Material tidak ditemukan.');
        }
    }


    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods
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


    public function addPurchaseOrder()
    {
        if (!isset($this->object->id)) {
            $this->dispatch('error', "Harap save barang terlebih dahulu!");
            return;
        }
        $this->dispatch('materialSaved', $this->object->id);
    }

    private function validatePrices()
    {
        $this->validateIDRPrices();
        $this->validateUSDPrices();
    }

    private function validateIDRPrices()
    {
        if ($this->materials['selling_price'] <= 0) {
            $this->addError('materials.selling_price', ' IDR harga penjualan harus lebih besar dari 0.');
            throw new \Exception('IDR harga penjualan harus lebih besar dari 0.');
        }
        if ($this->materials['buying_price'] <= 0) {
            $this->addError('materials.buying_price', ' IDR harga pembelian harus lebih besar dari 0.');
            throw new \Exception('IDR harga pembelian harus lebih besar dari 0.');
        }
    }

    private function validateUSDPrices()
    {
        if ($this->materials['buying_price'] <= 0) {
            $this->addError('materials.buying_price', ' USD harga pembelian harus lebih besar dari 0.');
            throw new \Exception('USD harga pembelian harus lebih besar dari 0.');
        }

        if ($this->materials['selling_price'] <= 0) {
            $this->addError('materials.selling_price', ' USD harga penjualan harus lebih besar dari 0.');
            throw new \Exception('USD harga penjualan harus lebih besar dari 0.');
        }
    }


    private function prepareBOMData($bomData, $index)
    {
        $bomData['matl_id'] = $this->object->id;
        $bomData['matl_code'] = $this->object->id;
        $bomData['jwl_sides_price'] = $bomData['jwl_sides_price'] === null || $bomData['jwl_sides_price'] === "" ? 0 : $bomData['jwl_sides_price'];
        $bomData['seq'] = $index + 1;
        $bomData['base_matl_id'] = $bomData['base_matl_id_value'];
        $bomData['jwl_sides_spec'] = $this->generateJWLSidesSpec($bomData);

        return $bomData;
    }

    private function generateJWLSidesSpec($bomData)
    {
        $dataToSave = [];
        $baseMaterialId = $bomData['base_matl_id_note'];

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

        return $dataToSave;
    }

    // private function deleteRemovedItems()
    // {
    //     foreach ($this->deletedItems as $deletedItemId) {
    //         MatlBom::find($deletedItemId)->forceDelete();
    //     }
    // }

    #endregion

    #region Component Events

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

            // Set kode material dan update configSnum
            $this->materials['code'] = $code . $proposedTrId;
            $configSnum->last_cnt = $proposedTrId;
            $configSnum->save();
        } else {
            $this->dispatch('error', "Tidak ada kode ditemukan untuk kategori produk ini.");
        }
    }


    public function generateMaterialDescriptions()
    {
        $this->materials['name'] = Material::generateMaterialDescriptions($this->materials);
    }

    public function markupPriceChanged()
    {
        if (empty($this->materials['buying_price'])) {
            return;
        }
        $this->materials['selling_price'] = Material::calculateSellingPrice($this->materials['buying_price'], $this->materials['markup']);
    }

    public function sellingPriceChanged()
    {
        if (empty($this->materials['buying_price'])) {
            return;
        }
        $this->materials['markup'] = Material::calculateMarkup($this->materials['buying_price'], $this->materials['jwl_selling_price_usd']);
    }
    public function tagScanned($tags)
    {
        if (!isset($this->object->id)) {
            $this->dispatch('error', "Harap save barang terlebih dahulu!");
            return;
        }

        if (isset($tags)) {
            $tagCount = count($tags);
            if ($tagCount > 1) {
                // Show error message with the number of tags
                $this->dispatch('error', "Terdapat {$tagCount} tag, mohon scan kembali!");
            } else {
                // Process a single tag
                if (!$this->isUniqueBarcode($tags)) {
                    $this->dispatch('error', 'RFID telah digunakan sebelumnya');
                } else {
                    $this->matl_uoms['barcode'] = $tags[0];
                    $this->saveUOMs();
                    $this->dispatch('success', 'RFID berhasil discan');
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
        if (!isset($this->object->id)) {
            $this->dispatch('error', "Harap save barang terlebih dahulu!");
            return;
        }

        $url = route($this->appCode.'.Master.Material.PrintPdf', [
            "action" => encryptWithSessionKey('Edit'),
            "objectId" => encryptWithSessionKey($this->object->id)
        ]);
        $this->dispatch('open-print-tab', [
            'url' => $url
        ]);
    }
    #endregion

}
