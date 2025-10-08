<?php

namespace App\Livewire\TrdRetail1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdRetail1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $object_detail;
    public $inputs = [];
    public $input_details = [];
    public $return_details = [];

    public $customers = [];
    public $partners = [];
    public $partnerSearchText = '';
    public $selectedPartners = [];
    public $isPrint = false;

    public $warehouses;
    public $deletedItems = [];
    public $newItems = [];
    public $trType = 'SO';

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $barcode = '';
    protected $masterService;
    public $isPanelEnabled = 'true';
    public $total_amount = 0;

    public $warehouseOptions = [];
    public $uomOptions = [];
    public $materialUomOptions = []; // Add this to store UOM options for each material
    public $payments;

    public $materials;
    public $wh_code = '';
    public $rules = [
        'inputs.tr_date' => 'required',
        'inputs.partner_id' => 'required',
        'inputs.payment_term_id' => 'required',
        'input_details.*.qty' => 'required|numeric|min:1',
        'input_details.*.matl_id' => 'required',
        'wh_code' => 'required',
        'input_details.*.matl_uom' => 'required',
    ];
    protected $messages = [
        'input_details.*.qty.min' => 'Isi Qty',
    ];
    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
        'materialsSelected' => 'handleMaterialsSelected'
    ];

    // Cache untuk material data yang sudah di-load
    private $materialCache = [];
    private $uomCache = [];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'inputs.tr_date' => $this->trans('tr_date'),
            'inputs.partner_id' => $this->trans('supplier'),
            'inputs.wh_code' => $this->trans('warehouse'),
        ];

        $this->masterService = new MasterService();
        $this->payments = $this->masterService->getPaymentTerm();
        $this->warehouses = $this->masterService->getWarehouse();

        $this->warehouseOptions = $this->masterService->getWarehouseData();
        $this->wh_code = $this->warehouseOptions[0]['value'] ?? null;
        $this->uomOptions = $this->masterService->getMatlUOMData();
        if ($this->isEditOrView()) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['partner_name'] = $this->object->Partner->code . ' - ' . $this->object->Partner->name;
            $this->loadDetails();
        }
        if (!empty($this->input_details)) {
            $this->isPanelEnabled = 'false';
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_RUPIAH_ID;
        $this->inputs['curr_code'] = 'IDR';
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods
    public function onValidateAndSave()
    {
        // Jika mode edit dan order sudah completed, tampilkan peringatan dan hentikan proses.
        if ($this->actionValue === 'Edit') {
            if ($this->object->isOrderCompleted()) {
                throw new Exception('Nota ini tidak bisa di edit, karena status sudah Completed');
            }
        }
        if (!isNullOrEmptyNumber($this->inputs['partner_id']) && $this->inputs['partner_id'] > 0) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner ? $partner->code : '';
        } else {
            $this->addError('partner_id', 'Customer tidak ditemukan');
            throw new Exception('Harap isi Customer terlebih dahulu');
        }

        // Initialize payment term data
        if (!isNullOrEmptyNumber($this->inputs['payment_term_id']) && $this->inputs['payment_term_id'] > 0) {
            $this->masterService = new MasterService();
            $paymentTerm = $this->masterService->getPaymentTermById($this->inputs['payment_term_id']);
            $this->inputs['payment_term'] = $paymentTerm ?? '';
        } else {
            $this->inputs['payment_term_id'] = 0;
            $this->inputs['payment_term'] = '';
        }

        // Ensure all required fields are properly initialized
        $this->inputs['reff_code'] = $this->inputs['reff_code'] ?? '';
        $this->inputs['sales_id'] = $this->inputs['sales_id'] ?? 0;
        $this->inputs['sales_code'] = $this->inputs['sales_code'] ?? '';
        $this->inputs['deliv_by'] = $this->inputs['deliv_by'] ?? '';
        $this->inputs['curr_rate'] = $this->inputs['curr_rate'] ?? 0;
        $this->inputs['print_settings'] = $this->inputs['print_settings'] ?? '';
        $this->inputs['print_remarks'] = $this->inputs['print_remarks'] ?? '';

        // Convert any array values to strings or appropriate types
        foreach ($this->inputs as $key => $value) {
            if (is_array($value)) {
                $this->inputs[$key] = json_encode($value);
            } elseif (is_object($value)) {
                $this->inputs[$key] = (string) $value;
            }
        }
        // Persiapkan array detail untuk disimpan.
        // Contoh: Update urutan (tr_seq), tambahkan info warehouse dan material.
        $itemsToSave = [];
        foreach ($this->input_details as $index => $detail) {
            $detail['tr_seq'] = $index + 1; // Pastikan urutan detail tersimpan dengan benar.
            $detail['tr_id'] = $this->object->tr_id; // Gunakan tr_id yang sudah di-generate.
            $detail['trhdr_id'] = $this->object->id; // Header ID pada OrderHdr.
            $detail['tr_type'] = $this->trType;
            $detail['wh_code'] = $this->wh_code; // Misalnya warehouse code disediakan dari properti komponen.

            // Cari konfigurasi warehouse jika diperlukan.
            $configConst = ConfigConst::where('const_group', 'MWAREHOUSE_LOCL1')
                ->where('str1', $detail['wh_code'] ?? '')
                ->first();
            $detail['wh_id'] = $configConst ? $configConst->id : null;

            // Ambil data material untuk mendapatkan material code.
            $material = Material::withTrashed()->find($detail['matl_id'] ?? null);
            $detail['matl_code'] = $material ? $material->code : '';

            // Ensure numeric fields are properly typed
            $detail['qty'] = (float) ($detail['qty'] ?? 0);
            $detail['price'] = (float) ($detail['price'] ?? 0);
            $detail['amt'] = (float) ($detail['amt'] ?? 0);
            $detail['matl_id'] = (int) ($detail['matl_id'] ?? 0);

            // Remove any array or object fields that might cause issues
            unset($detail['image_url']); // This might be causing issues if it's an object
            unset($detail['matl_descr']); // Remove description if it's not needed in save

            // Jika diperlukan, tambahkan atau ubah field lain di detail.
            $itemsToSave[] = $detail;
        }

        // Simpan header dan detail secara terpadu menggunakan method saveOrder.
        // Parameter: tipe transaksi, data header, data detail, dan flag untuk membuat header delivery/billing/payment.
        $this->object->saveOrder($this->trType, $this->inputs, $itemsToSave, true);

        // Redirect bila aksi adalah Create, atau lakukan tindakan lanjutan sesuai kebutuhan.
        if ($this->actionValue === 'Create') {
            return redirect()->route($this->appCode . '.Transaction.SalesOrder.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id),
            ]);
        }
        if ($this->isPrint) {
            return redirect()->route($this->appCode . '.Transaction.SalesOrder.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id),
            ]);
        }
    }
    public function scanBarcode()
    {
        $cleanBarcode = trim($this->barcode);
        $uom = MatlUom::where('barcode', $cleanBarcode)->first();

        if (! $uom) {
            $this->dispatch('error', 'Kode batang tidak ditemukan, mohon scan ulang!');
        } else {
            // Cari index yang match
            $index = collect($this->input_details)->search(function ($d) use ($uom) {
                return ($d['matl_id'] ?? null) === $uom->matl_id && ($d['matl_uom'] ?? null) === $uom->matl_uom;
            });

            if ($index !== false) {
                // Kalau sudah ada, tambah qty-nya
                $this->input_details[$index]['qty'] += 1;
                $this->updateItemAmount($index);
                $this->dispatch('success', 'Qty berhasil ditambah untuk item ini.');
            } else {
                // Kalau belum ada, tambahkan item baru
                $newIndex = count($this->input_details);
                $this->addItem();

                $this->onMaterialChanged($newIndex, $uom->matl_id);
                $this->onUomChanged($newIndex, $uom->matl_uom);

                $this->input_details[$newIndex]['qty'] = 1;
                $this->updateItemAmount($newIndex);

                $this->dispatch('success', 'Item berhasil ditambahkan melalui scan barcode.');
            }
        }

        // Clear input scanner dan kembalikan fokus
        $this->dispatch('barcode-processed');
        $this->barcode = '';
    }

    public function SaveAndPrint()
    {
        $this->isPrint = true;
        $this->Save();
    }

    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
                return;
            }

            //$this->updateVersionNumber();
            if (isset($this->object->status_code)) {
                $this->object->status_code = Status::NONACTIVE;
            }
            $this->object->save();
            $this->object->delete();
            $messageKey = 'generic.string.delete';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }

    #endregion

    #region Component Events
    public function openPartnerDialogBox()
    {
        $this->partnerSearchText = '';
        $this->customers = [];
        $this->selectedPartners = [];
        $this->dispatch('openPartnerDialogBox');
    }
    public function searchPartners()
    {
        if (!empty($this->partnerSearchText)) {
            $searchTerm = strtoupper($this->partnerSearchText);
            $this->customers = Partner::where('grp', Partner::SUPPLIER)
                ->where(function ($query) use ($searchTerm) {
                    $query->whereRaw('UPPER(code) LIKE ?', ["%{$searchTerm}%"])->orWhereRaw('UPPER(name) LIKE ?', ["%{$searchTerm}%"]);
                })
                ->get();
        } else {
            $this->dispatch('error', 'Mohon isi kode atau nama supplier');
        }
    }

    public function selectPartner($partnerId)
    {
        $key = array_search($partnerId, $this->selectedPartners);

        if ($key !== false) {
            unset($this->selectedPartners[$key]);
            $this->selectedPartners = array_values($this->selectedPartners);
        } else {
            $this->selectedPartners[] = $partnerId;
        }
    }

    // public function confirmSelection()
    // {
    //     if (empty($this->selectedPartners)) {
    //         $this->dispatch('error', "Silakan pilih satu supplier terlebih dahulu.");
    //         return;
    //     }
    //     if (count($this->selectedPartners) > 1) {
    //         $this->dispatch('error', "Hanya boleh memilih satu supplier.");
    //         return;
    //     }
    //     $partner = Partner::find($this->selectedPartners[0]);

    //     if ($partner) {
    //         $this->inputs['partner_id'] = $partner->id;
    //         $this->inputs['partner_name'] = $partner->code . " - " . $partner->name;
    //         $this->dispatch('success', "Supplier berhasil dipilih.");
    //         $this->dispatch('closePartnerDialogBox');
    //     }
    // }
    public function addItem()
    {
        $key = count($this->input_details);
        $this->input_details[] = [
            'matl_id' => null,
            'matl_uom' => 'PCS',
            'qty' => null,
            'price' => 0.0,
            'amt' => 0.0
        ];

        // Initialize empty UOM options for the new item
        $this->materialUomOptions[$key] = [];
    }
    public function onMaterialChanged($key, $matl_id)
    {
        if (!$matl_id) {
            return;
        }

        // Check for duplicates more efficiently
        $duplicate = false;
        foreach ($this->input_details as $index => $detail) {
            if ($index != $key && isset($detail['matl_id']) && $detail['matl_id'] == $matl_id) {
                $duplicate = true;
                break;
            }
        }

        if ($duplicate) {
            $this->dispatch('error', 'Material sudah ada dalam daftar.');
            return;
        }

        // Use select to get only needed fields for better performance
        $material = Material::select('id', 'code', 'name')->find($matl_id);
        if (!$material) {
            $this->dispatch('error', 'Material_not_found');
            return;
        }

        $this->input_details[$key]['matl_id'] = $material->id;
        $this->input_details[$key]['matl_code'] = $material->code;
        $this->input_details[$key]['matl_descr'] = $material->name;

        // Load UOMs for this specific material (ordered by matl_uom)
        $materialUoms = MatlUom::where('matl_id', $matl_id)
            ->select('matl_uom') // Only select needed column
            ->orderBy('matl_uom', 'asc')
            ->get();

        $uomOptions = [];
        $firstUom = null;
        foreach ($materialUoms as $matlUom) {
            $uomOptions[] = [
                'value' => $matlUom->matl_uom,
                'label' => $matlUom->matl_uom
            ];

            // Set first UOM found
            if ($firstUom === null) {
                $firstUom = $matlUom->matl_uom;
            }
        }

        // Store UOM options for this material
        $this->materialUomOptions[$key] = $uomOptions;

        // Set default UOM to first found UOM from material's UOM list
        if ($firstUom !== null) {
            $this->input_details[$key]['matl_uom'] = $firstUom;
        } else {
            // Only fallback to PCS if absolutely no UOMs exist for this material
            $this->input_details[$key]['matl_uom'] = 'PCS';
            // Create default UOM entry if none exists
            $this->materialUomOptions[$key] = [['value' => 'PCS', 'label' => 'PCS']];
        }

        // Get image URL more efficiently
        $attachment = $material->Attachment()->first();
        $this->input_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';

        $this->updateMaterialUomData($key);
    }

    public function onUomChanged($key, $uomId)
    {
        $this->input_details[$key]['matl_uom'] = $uomId;
        $this->updateMaterialUomData($key);
    }

    private function updateMaterialUomData($key)
    {
        $materialId = $this->input_details[$key]['matl_id'] ?? null;
        $uom = $this->input_details[$key]['matl_uom'] ?? 'PCS';

        if (!$materialId) {
            return;
        }

        // Use single query with fallback logic
        $matlUom = MatlUom::where('matl_id', $materialId)
            ->where('matl_uom', $uom)
            ->first();

        if ($matlUom) {
            $this->input_details[$key]['price'] = $matlUom->selling_price;
        } else {
            // Fallback to 0 if no MatlUom found since selling_price is in MatlUom table
            $this->input_details[$key]['price'] = 0;
        }

        $this->updateItemAmount($key);
    }

    public function updateItemAmount($key)
    {
        // Early validation to avoid unnecessary calculations
        if (!isset($this->input_details[$key])) {
            return;
        }

        $detail = &$this->input_details[$key]; // Use reference for better performance
        $qty = floatval($detail['qty'] ?? 0);
        $price = floatval($detail['price'] ?? 0);

        // Calculate amount only if both values are valid
        if ($qty > 0 && $price >= 0) {
            $amount = $qty * $price;
            $detail['amt'] = round($amount, 2); // Round to prevent floating point issues
        } else {
            $detail['amt'] = 0;
        }

        $detail['amt_idr'] = rupiah($detail['amt']);

        // Update totals with optimized calculation
        $this->updateTotalAmount();
    }

    private function updateTotalAmount()
    {
        $total = 0;
        foreach ($this->input_details as $detail) {
            $total += floatval($detail['amt'] ?? 0);
        }
        $this->total_amount = round($total, 2);
    }

    public function deleteItem($index)
    {
        try {
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('warning', 'Item telah dihapus dari daftar. Tekan Simpan untuk menyimpan perubahan.');
            $this->updateTotalAmount(); // Use optimized method
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)->orderBy('tr_seq')->get();
            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['wh_code'] = $this->warehouseOptions[0]['value'] ?? null;

                // Load UOMs for this material (ordered by matl_uom)
                $materialUoms = MatlUom::where('matl_id', $detail->matl_id)
                    ->orderBy('matl_uom', 'asc')
                    ->get();
                $uomOptions = [];
                foreach ($materialUoms as $matlUom) {
                    $uomOptions[] = [
                        'value' => $matlUom->matl_uom,
                        'label' => $matlUom->matl_uom
                    ];
                }
                $this->materialUomOptions[$key] = $uomOptions;

                $material = Material::withTrashed()->find($detail->matl_id);
                if ($material) {
                    $attachment = optional($material->Attachment)->first();
                    $this->input_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                } else {
                    $this->input_details[$key]['image_url'] = '';
                }
                $this->updateItemAmount($key);
            }
        }

        // Calculate total once after loading all details
        $this->updateTotalAmount();
    }

    public function openItemDialogBox()
    {
        $this->dispatch('openItemDialogBox');
    }

    public function handleMaterialsSelected($selectedMaterials)
    {
        if (empty($selectedMaterials)) {
            $this->dispatch('error', 'Tidak ada material yang dipilih.');
            return;
        }

        $addedCount = 0;
        foreach ($selectedMaterials as $materialData) {
            // Handle both old format (just ID) and new format (array with matl_id and matl_uom)
            if (is_array($materialData)) {
                $matl_id = $materialData['matl_id'];
                $matl_uom = $materialData['matl_uom'] ?? 'PCS';
            } else {
                $matl_id = $materialData;
                $matl_uom = 'PCS';
            }

            $exists = collect($this->input_details)->contains('matl_id', $matl_id);

            if ($exists) {
                // Skip if already exists, but don't show error for each one
                continue;
            }

            // Add to list if not duplicate
            $key = count($this->input_details);
            $this->input_details[] = [
                'matl_id' => $matl_id,
                'matl_uom' => $matl_uom,
                'qty' => null,
                'price' => 0.0,
                'amt' => 0.0
            ];
            $this->onMaterialChanged($key, $matl_id);
            $addedCount++;
        }

        if ($addedCount > 0) {
            $this->dispatch('success', "$addedCount material(s) berhasil ditambahkan.");
        } else {
            $this->dispatch('warning', 'Semua material yang dipilih sudah ada dalam daftar.');
        }
    }

    #region Cache and Optimization Methods

    /**
     * Get material data with caching
     */
    private function getCachedMaterial($materialId)
    {
        if (!isset($this->materialCache[$materialId])) {
            $this->materialCache[$materialId] = Material::select('id', 'code', 'name')
                ->find($materialId);
        }
        return $this->materialCache[$materialId];
    }

    /**
     * Get UOM data for material with caching
     */
    private function getCachedMaterialUoms($materialId)
    {
        if (!isset($this->uomCache[$materialId])) {
            $this->uomCache[$materialId] = MatlUom::where('matl_id', $materialId)
                ->select('matl_uom', 'selling_price')
                ->orderBy('matl_uom', 'asc')
                ->get();
        }
        return $this->uomCache[$materialId];
    }

    /**
     * Clear caches when needed
     */
    private function clearCaches()
    {
        $this->materialCache = [];
        $this->uomCache = [];
    }

    #endregion

}
