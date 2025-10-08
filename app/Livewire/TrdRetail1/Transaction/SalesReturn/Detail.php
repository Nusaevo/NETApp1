<?php

namespace App\Livewire\TrdRetail1\Transaction\SalesReturn;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl, ReturnHdr, ReturnDtl};
use App\Models\TrdRetail1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $object_detail;
    public $object; // ReturnHdr object (renamed from return_object)
    public $exchange_object; // OrderHdr object for exchange
    public $inputs = [];
    public $input_details = []; // Items yang diretur (return items) - ReturnHdr
    public $exchange_details = []; // Items yang ditukar (exchange items) - OrderHdr

    public $customers = [];
    public $partners = [];
    public $partnerSearchText = '';
    public $selectedPartners = [];
    public $isPrint = false;

    public $warehouses;
    public $deletedItems = [];
    public $deletedExchangeItems = []; // Track exchange items marked for deletion
    public $newItems = [];
    public $returnTrType = 'SR'; // Sales Return
    public $exchangeTrType = 'SOR'; // Sales Order Return (Exchange)

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $currencyRate = 0;
    public $barcode = '';
    public $exchangeBarcode = '';
    protected $masterService;
    public $isPanelEnabled = 'true';
    public $total_amount = 0;
    public $total_return_amount = 0;

    public $warehouseOptions = [];
    public $uomOptions = [];
    public $materialUomOptions = []; // Add this to store UOM options for each return material
    public $exchangeMaterialUomOptions = []; // Add this to store UOM options for each exchange material
    public $payments;

    public $materials;
    public $wh_code = '';
    public $exchange_wh_code = '';

    public $rules = [
        'inputs.tr_date' => 'required',
        'inputs.partner_id' => 'required',
        'inputs.payment_term_id' => 'required',
        'input_details.*.qty' => 'required|numeric|min:1',
        'input_details.*.matl_id' => 'required',
        'exchange_details.*.qty' => 'required|numeric|min:1',
        'exchange_details.*.matl_id' => 'required',
        'wh_code' => 'required',
        'input_details.*.matl_uom' => 'required',
        'exchange_details.*.matl_uom' => 'required',
    ];

    protected $messages = [
        'input_details.*.qty.min' => 'Isi Qty Return',
        'exchange_details.*.qty.min' => 'Isi Qty Exchange',
    ];

    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
        'materialsSelected' => 'handleMaterialsSelected',
        'exchangeMaterialsSelected' => 'handleExchangeMaterialsSelected'
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*.qty' => 'Qty',
            'exchange_details.*.qty' => 'Qty',
            'inputs.tr_date' => $this->trans('tr_date'),
            'inputs.partner_id' => $this->trans('customer'),
            'wh_code' => $this->trans('warehouse'),
        ];

        $this->masterService = new MasterService();
        $this->payments = $this->masterService->getPaymentTerm();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->warehouseOptions = $this->masterService->getWarehouseData();
        $this->wh_code = $this->warehouseOptions[0]['value'] ?? null;
        $this->exchange_wh_code = $this->warehouseOptions[0]['value'] ?? null;
        $this->uomOptions = $this->masterService->getMatlUOMData();

        if ($this->isEditOrView()) {
            // Load existing return record with all necessary relationships
            $this->object = ReturnHdr::withTrashed()
                ->with([
                    'Partner',
                    'ReturnDtl.Material.Attachment',
                    'ExchangeOrder.OrderDtl.Material.Attachment'
                ])
                ->find($this->objectIdValue);
            if ($this->object) {
                $this->inputs = populateArrayFromModel($this->object);
                $this->inputs['status_code_text'] = $this->object->status_Code_text;
                $this->inputs['partner_name'] = $this->object->Partner->code . ' - ' . $this->object->Partner->name;
                $this->loadDetails();
            }
        }
        if (!empty($this->input_details) || !empty($this->exchange_details)) {
            $this->isPanelEnabled = 'false';
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->reset('input_details');
        $this->reset('exchange_details');
        $this->object = new ReturnHdr();
        $this->exchange_object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = $this->returnTrType;
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
        // Validate basic inputs
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
       $this->processReturnItems();
       $this->processExchangeItems();

        // Redirect handling
        if ($this->actionValue === 'Create') {
            return redirect()->route($this->appCode . '.Transaction.SalesReturn.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id ?? $this->exchange_object->id),
            ]);
        }
        if ($this->isPrint) {
            return redirect()->route($this->appCode . '.Transaction.SalesReturn.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id ?? $this->exchange_object->id),
            ]);
        }
    }

    private function processReturnItems()
    {
        // Initialize ReturnHdr if not exists
        if (!$this->object) {
            $this->object = new ReturnHdr();
        }

        // Generate return ID from RETURN_ORDER_LASTID serial
        if (!$this->object->tr_id) {
            $this->inputs['tr_id'] = $this->generateReturnId();
        }
        $this->inputs['tr_type'] = $this->returnTrType;

        // Prepare return items for ReturnDtl
        $returnItems = [];
        foreach ($this->input_details as $index => $detail) {
            // Skip null or invalid details
            if (!$detail || !is_array($detail)) {
                continue;
            }

            $detail['tr_seq'] = $index + 1;
            $detail['tr_id'] = $this->inputs['tr_id'];
            $detail['trhdr_id'] = $this->object->id;
            $detail['tr_type'] = $this->returnTrType;
            $detail['wh_code'] = $this->wh_code;

            // Get warehouse config
            $configConst = ConfigConst::where('const_group', 'MWAREHOUSE_LOCL1')
                ->where('str1', $detail['wh_code'] ?? '')
                ->first();
            $detail['wh_id'] = $configConst ? $configConst->id : null;

            // Get material code
            $material = Material::withTrashed()->find($detail['matl_id'] ?? null);
            $detail['matl_code'] = $material ? $material->code : '';

            // Ensure numeric fields are properly typed
            $detail['qty'] = (float) ($detail['qty'] ?? 0); // Positive qty for returns
            $detail['price'] = (float) ($detail['price'] ?? 0); // Return items also have prices
            $detail['amt'] = (float) ($detail['amt'] ?? 0); // Return items also have amounts
            $detail['matl_id'] = (int) ($detail['matl_id'] ?? 0);

            // Remove UI fields
            unset($detail['image_url']);
            unset($detail['matl_descr']);

            $returnItems[] = $detail;
        }

        // Handle deleted items - force delete ReturnDtl records that were marked for deletion
        if (!empty($this->deletedItems)) {
            foreach ($this->deletedItems as $deletedId) {
                $returnDtlToDelete = ReturnDtl::find($deletedId);
                if ($returnDtlToDelete) {
                    $returnDtlToDelete->forceDelete(); // This will trigger ReturnDtl::deleted event to handle stock adjustments
                }
            }
            // Clear the deleted items array after processing
            $this->deletedItems = [];
        }

        // Save ReturnHdr and ReturnDtl (this will increase stock)
        $this->object->saveReturn($this->returnTrType, $this->inputs, $returnItems);
    }

    private function processExchangeItems()
    {
        // Initialize OrderHdr if not exists
        if (!$this->exchange_object) {
            $this->exchange_object = new OrderHdr();
        }

        // Prepare exchange inputs (copy from main inputs but change tr_type)
        $exchangeInputs = $this->inputs;
        $exchangeInputs['tr_type'] = $this->exchangeTrType;

        // Use the same tr_id from Sales Return for exchange items
        // This ensures both return and exchange are linked by the same transaction ID
        if (isset($this->inputs['tr_id']) && !empty($this->inputs['tr_id'])) {
            $exchangeInputs['tr_id'] = $this->inputs['tr_id'];
            $this->exchange_object->tr_id = $this->inputs['tr_id'];
        }

        // Prepare exchange items for OrderDtl
        $exchangeItems = [];
        foreach ($this->exchange_details as $index => $detail) {
            // Skip null or invalid details
            if (!$detail || !is_array($detail)) {
                continue;
            }

            $detail['tr_seq'] = $index + 1;
            $detail['tr_id'] = $this->inputs['tr_id']; // Use Sales Return tr_id
            $detail['trhdr_id'] = $this->exchange_object->id;
            $detail['tr_type'] = $this->exchangeTrType;
            $detail['wh_code'] = $this->exchange_wh_code;

            // Get warehouse config
            $configConst = ConfigConst::where('const_group', 'MWAREHOUSE_LOCL1')
                ->where('str1', $detail['wh_code'] ?? '')
                ->first();
            $detail['wh_id'] = $configConst ? $configConst->id : null;

            // Get material code
            $material = Material::withTrashed()->find($detail['matl_id'] ?? null);
            $detail['matl_code'] = $material ? $material->code : '';

            // Ensure numeric fields are properly typed
            $detail['qty'] = (float) ($detail['qty'] ?? 0); // Positive qty for exchange
            $detail['price'] = (float) ($detail['price'] ?? 0);
            $detail['amt'] = (float) ($detail['amt'] ?? 0);
            $detail['matl_id'] = (int) ($detail['matl_id'] ?? 0);

            // Remove UI fields
            unset($detail['image_url']);
            unset($detail['matl_descr']);

            $exchangeItems[] = $detail;
        }

        // Handle deleted exchange items - force delete OrderDtl records that were marked for deletion
        if (!empty($this->deletedExchangeItems)) {
            foreach ($this->deletedExchangeItems as $deletedId) {
                $orderDtlToDelete = OrderDtl::find($deletedId);
                if ($orderDtlToDelete) {
                    $orderDtlToDelete->forceDelete(); // This will trigger OrderDtl::deleted event to handle stock adjustments
                }
            }
            // Clear the deleted exchange items array after processing
            $this->deletedExchangeItems = [];
        }

        // Save OrderHdr and OrderDtl (this will create delivery and decrease stock)
        $this->exchange_object->saveOrder($this->exchangeTrType, $exchangeInputs, $exchangeItems, true);
    }

    private function generateReturnId()
    {
        // Get next ID from RETURN_ORDER_LASTID serial
        $configConst = ConfigConst::where('const_group', 'RETURN_ORDER_LASTID')->first();

        if ($configConst) {
            $lastId = (int) $configConst->str1;
            $newId = $lastId + 1;

            // Update the serial
            $configConst->str1 = (string) $newId;
            $configConst->save();
            return $newId;
        }

        // If no config found, create a new one starting from 1
        $newConfig = new ConfigConst();
        $newConfig->const_group = 'RETURN_ORDER_LASTID';
        $newConfig->str1 = '1';
        $newConfig->save();
        return 1;
    }

    public function SaveAndPrint()
    {
        $this->isPrint = true;
        $this->Save();
    }

    public function delete()
    {
        try {
            // Delete ReturnHdr if exists
            if ($this->object) {
                if (isset($this->object->status_code)) {
                    $this->object->status_code = Status::NONACTIVE;
                }
                $this->object->save();
                $this->object->delete();
            }

            // Delete OrderHdr (exchange) if exists
            if ($this->exchange_object) {
                if ($this->exchange_object->isOrderCompleted()) {
                    $this->dispatch('warning', 'Exchange order tidak bisa delete, karena status sudah Completed');
                    return;
                }

                if (!$this->exchange_object->isOrderEnableToDelete()) {
                    $this->dispatch('warning', 'Exchange order tidak bisa delete, karena memiliki material yang sudah dijual.');
                    return;
                }

                if (isset($this->exchange_object->status_code)) {
                    $this->exchange_object->status_code = Status::NONACTIVE;
                }
                $this->exchange_object->save();
                $this->exchange_object->delete();
            }

            $messageKey = 'generic.string.delete';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.disable', ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }
    #endregion

    #region Component Events
    public function scanBarcode()
    {
        $cleanBarcode = trim($this->barcode);
        $uom = MatlUom::where('barcode', $cleanBarcode)->first();

        if (!$uom) {
            $this->dispatch('error', 'Kode batang tidak ditemukan, mohon scan ulang!');
        } else {
            // Cari index yang match di return items
            $index = collect($this->input_details)->search(function ($d) use ($uom) {
                return ($d['matl_id'] ?? null) === $uom->matl_id && ($d['matl_uom'] ?? null) === $uom->matl_uom;
            });

            if ($index !== false) {
                // Kalau sudah ada, tambah qty-nya
                $this->input_details[$index]['qty'] += 1;
                $this->dispatch('success', 'Qty berhasil ditambah untuk item return ini.');
            } else {
                // Kalau belum ada, tambahkan item baru
                $newIndex = count($this->input_details);
                $this->addItem();

                $this->onMaterialChanged($newIndex, $uom->matl_id);
                $this->onUomChanged($newIndex, $uom->matl_uom);

                $this->input_details[$newIndex]['qty'] = 1;

                $this->dispatch('success', 'Item berhasil ditambahkan melalui scan barcode.');
            }
        }

        // Clear input scanner dan kembalikan fokus
        $this->dispatch('barcode-processed');
        $this->barcode = '';
    }

    public function scanExchangeBarcode()
    {
        $cleanBarcode = trim($this->exchangeBarcode);
        $uom = MatlUom::where('barcode', $cleanBarcode)->first();

        if (!$uom) {
            $this->dispatch('error', 'Kode batang tidak ditemukan, mohon scan ulang!');
        } else {
            // Cari index yang match di exchange items
            $index = collect($this->exchange_details)->search(function ($d) use ($uom) {
                return ($d['matl_id'] ?? null) === $uom->matl_id && ($d['matl_uom'] ?? null) === $uom->matl_uom;
            });

            if ($index !== false) {
                // Kalau sudah ada, tambah qty-nya
                $this->exchange_details[$index]['qty'] += 1;
                $this->updateExchangeItemAmount($index);
                $this->dispatch('success', 'Qty berhasil ditambah untuk item exchange ini.');
            } else {
                // Kalau belum ada, tambahkan item baru
                $newIndex = count($this->exchange_details);
                $this->addExchangeItem();

                $this->onExchangeMaterialChanged($newIndex, $uom->matl_id);
                $this->onExchangeUomChanged($newIndex, $uom->matl_uom);

                $this->exchange_details[$newIndex]['qty'] = 1;
                $this->updateExchangeItemAmount($newIndex);

                $this->dispatch('success', 'Item berhasil ditambahkan melalui scan barcode exchange.');
            }
        }

        // Clear input scanner dan kembalikan fokus
        $this->dispatch('exchange-barcode-processed');
        $this->exchangeBarcode = '';
    }

    // Return Items Management
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

    public function deleteItem($index)
    {
        try {
            // Check if this is an existing item (has ID) that needs to be marked for deletion
            if (isset($this->input_details[$index]['id']) && !empty($this->input_details[$index]['id'])) {
                // This is an existing record, add to deletedItems for database deletion
                $this->deletedItems[] = $this->input_details[$index]['id'];
            }

            // Remove from UI array
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            // Recalculate totals
            $this->recalculateReturnTotals();

            $this->dispatch('warning', 'Item telah dihapus dari daftar. Tekan Simpan untuk menyimpan perubahan.');
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $duplicate = collect($this->input_details)->contains(function ($detail, $index) use ($key, $matl_id) {
                return $index != $key && isset($detail['matl_id']) && $detail['matl_id'] == $matl_id;
            });

            if ($duplicate) {
                $this->dispatch('error', 'Material sudah ada dalam daftar.');
                return;
            }

            $material = Material::find($matl_id);
            if ($material) {
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['matl_code'] = $material->code;
                $this->input_details[$key]['matl_descr'] = $material->name;

                // Load UOMs for this specific material (ordered by matl_uom)
                $materialUoms = MatlUom::where('matl_id', $matl_id)
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

                $attachment = optional($material->Attachment)->first();
                $this->input_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                $this->updateMaterialUomData($key);
            } else {
                $this->dispatch('error', 'Material_not_found');
            }
        }
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

        if ($materialId) {
            $matlUom = MatlUom::where('matl_id', $materialId)->where('matl_uom', $uom)->first();

            if ($matlUom) {
                $this->input_details[$key]['price'] = $matlUom->selling_price;
            } else {
                // fallback to material default price
                $material = Material::find($materialId);
                $this->input_details[$key]['price'] = $material->selling_price ?? 0;
            }

            $this->updateReturnItemAmount($key);
        }
    }

    public function updateReturnItemAmount($key)
    {
        // Ensure the key exists in input_details
        if (!isset($this->input_details[$key])) {
            return;
        }

        $qty = floatval($this->input_details[$key]['qty'] ?? 0);
        $price = floatval($this->input_details[$key]['price'] ?? 0);

        if ($qty > 0 && $price > 0) {
            $amount = round($qty * $price, 2);
            $this->input_details[$key]['amt'] = $amount;
        } else {
            $this->input_details[$key]['amt'] = 0.0;
        }
        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);

        // Update return totals
        $this->recalculateReturnTotals();
    }

    public function recalculateReturnTotals()
    {
        $this->total_return_amount = array_sum(
            array_map(function ($detail) {
                if (!$detail) return 0.0;
                $qty = floatval($detail['qty'] ?? 0);
                $price = floatval($detail['price'] ?? 0);
                $amount = round($qty * $price, 2);
                return $amount;
            }, $this->input_details),
        );

        $this->total_return_amount = round($this->total_return_amount, 2);
    }

    // Exchange Items Management (Tukar Barang)
    public function addExchangeItem()
    {
        $key = count($this->exchange_details);
        $this->exchange_details[] = [
            'matl_id' => null,
            'matl_uom' => 'PCS',
            'qty' => null,
            'price' => 0.0,
            'amt' => 0.0
        ];

        // Initialize empty UOM options for the new exchange item
        $this->exchangeMaterialUomOptions[$key] = [];
    }

    public function deleteExchangeItem($index)
    {
        try {
            // Check if this is an existing exchange item (has ID) that needs to be marked for deletion
            if (isset($this->exchange_details[$index]['id']) && !empty($this->exchange_details[$index]['id'])) {
                // This is an existing OrderDtl record, add to deletedExchangeItems for database deletion
                $this->deletedExchangeItems[] = $this->exchange_details[$index]['id'];
            }

            // Remove from UI array
            unset($this->exchange_details[$index]);
            $this->exchange_details = array_values($this->exchange_details);

            // Recalculate totals
            $this->recalculateTotals();

           $this->dispatch('warning', 'Item telah dihapus dari daftar. Tekan Simpan untuk menyimpan perubahan.');
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function onExchangeMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $duplicate = collect($this->exchange_details)->contains(function ($detail, $index) use ($key, $matl_id) {
                return $index != $key && isset($detail['matl_id']) && $detail['matl_id'] == $matl_id;
            });

            if ($duplicate) {
                $this->dispatch('error', 'Material sudah ada dalam daftar exchange.');
                return;
            }

            $material = Material::find($matl_id);
            if ($material) {
                $this->exchange_details[$key]['matl_id'] = $material->id;
                $this->exchange_details[$key]['matl_code'] = $material->code;
                $this->exchange_details[$key]['matl_descr'] = $material->name;

                // Load UOMs for this specific material (ordered by matl_uom)
                $materialUoms = MatlUom::where('matl_id', $matl_id)
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

                // Store UOM options for this exchange material
                $this->exchangeMaterialUomOptions[$key] = $uomOptions;

                // Set default UOM to first found UOM from material's UOM list
                if ($firstUom !== null) {
                    $this->exchange_details[$key]['matl_uom'] = $firstUom;
                } else {
                    // Only fallback to PCS if absolutely no UOMs exist for this material
                    $this->exchange_details[$key]['matl_uom'] = 'PCS';
                    // Create default UOM entry if none exists
                    $this->exchangeMaterialUomOptions[$key] = [['value' => 'PCS', 'label' => 'PCS']];
                }

                $attachment = optional($material->Attachment)->first();
                $this->exchange_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                $this->updateExchangeMaterialUomData($key);
            } else {
                $this->dispatch('error', 'Material_not_found');
            }
        }
    }

    public function onExchangeUomChanged($key, $uomId)
    {
        $this->exchange_details[$key]['matl_uom'] = $uomId;
        $this->updateExchangeMaterialUomData($key);
    }

    private function updateExchangeMaterialUomData($key)
    {
        $materialId = $this->exchange_details[$key]['matl_id'] ?? null;
        $uom = $this->exchange_details[$key]['matl_uom'] ?? 'PCS';

        if ($materialId) {
            $matlUom = MatlUom::where('matl_id', $materialId)->where('matl_uom', $uom)->first();

            if ($matlUom) {
                $this->exchange_details[$key]['price'] = $matlUom->selling_price;
            } else {
                // fallback to material default price
                $material = Material::find($materialId);
                $this->exchange_details[$key]['price'] = $material->selling_price ?? 0;
            }

            $this->updateExchangeItemAmount($key);
        }
    }

    public function updateExchangeItemAmount($key)
    {
        // Ensure the key exists in exchange_details
        if (!isset($this->exchange_details[$key])) {
            return;
        }

        $qty = floatval($this->exchange_details[$key]['qty'] ?? 0);
        $price = floatval($this->exchange_details[$key]['price'] ?? 0);

        if ($qty > 0 && $price > 0) {
            $amount = round($qty * $price, 2);
            $this->exchange_details[$key]['amt'] = $amount;
        } else {
            $this->exchange_details[$key]['amt'] = 0.0;
        }
        $this->exchange_details[$key]['amt_idr'] = rupiah($this->exchange_details[$key]['amt']);

        // Update totals immediately
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->total_amount = array_sum(
            array_map(function ($detail) {
                if (!$detail) return 0.0;
                $qty = floatval($detail['qty'] ?? 0);
                $price = floatval($detail['price'] ?? 0);
                $amount = round($qty * $price, 2);
                return $amount;
            }, $this->exchange_details),
        );

        $this->total_amount = round($this->total_amount, 2);
    }

    protected function loadDetails()
    {
        // Load return details from ReturnHdr with relationships
        if (!empty($this->object)) {
            // Efficiently load return details with material relationships
            $returnDetails = $this->object->ReturnDtl()
                ->with(['Material.Attachment'])
                ->orderBy('tr_seq')
                ->get();

            foreach ($returnDetails as $key => $detail) {
                if ($detail) { // Add null check
                    $this->input_details[$key] = populateArrayFromModel($detail);
                    $this->input_details[$key]['wh_code'] = $this->warehouseOptions[0]['value'] ?? null;

                    // Load UOMs for this return material (ordered by matl_uom)
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

                    // Use already loaded relationship data
                    $material = $detail->Material;
                    if ($material) {
                        $attachment = $material->Attachment ? $material->Attachment->first() : null;
                        $this->input_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                    } else {
                        $this->input_details[$key]['image_url'] = '';
                    }
                    $this->updateReturnItemAmount($key); // Update amounts when loading
                }
            }
        }

        // Load exchange details using the relationship
        if (!empty($this->object) && $this->object->ExchangeOrder) {
            $this->exchange_object = $this->object->ExchangeOrder;

            // Efficiently load exchange details with material relationships
            $exchangeDetails = $this->exchange_object->OrderDtl()
                ->with(['Material.Attachment'])
                ->orderBy('tr_seq')
                ->get();

            foreach ($exchangeDetails as $key => $detail) {
                if ($detail) { // Add null check
                    $this->exchange_details[$key] = populateArrayFromModel($detail);
                    $this->exchange_details[$key]['wh_code'] = $detail->wh_code ?? $this->warehouseOptions[0]['value'] ?? null;
                    if (empty($this->exchange_wh_code)) {
                        $this->exchange_wh_code = $this->exchange_details[$key]['wh_code'];
                    }

                    // Load UOMs for this exchange material (ordered by matl_uom)
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
                    $this->exchangeMaterialUomOptions[$key] = $uomOptions;

                    // Use already loaded relationship data
                    $material = $detail->Material;
                    if ($material) {
                        $attachment = $material->Attachment ? $material->Attachment->first() : null;
                        $this->exchange_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                    } else {
                        $this->exchange_details[$key]['image_url'] = '';
                    }
                    $this->updateExchangeItemAmount($key);
                }
            }
        }
    }

    // Material Search Functions replaced by reusable component
    // All searchMaterials, selectMaterial, confirmSelection methods now handled by MaterialSelection component

    public function openItemDialogBox()
    {
        $this->dispatch('openItemDialogBox');
    }

    public function openExchangeMaterialDialogBox()
    {
        $this->dispatch('openExchangeMaterialDialogBox');
    }

    public function handleMaterialsSelected($selectedMaterials)
    {
        if (empty($selectedMaterials)) {
            $this->dispatch('error', 'Tidak ada material yang dipilih.');
            return;
        }

        $addedCount = 0;
        foreach ($selectedMaterials as $selectedItem) {
            // Handle both old format (just ID) and new format (array with matl_id and matl_uom)
            if (is_array($selectedItem)) {
                $matl_id = $selectedItem['matl_id'];
                $matl_uom = $selectedItem['matl_uom'] ?? 'PCS';
            } else {
                $matl_id = $selectedItem;
                $matl_uom = 'PCS';
            }

            $exists = collect($this->input_details)->contains('matl_id', $matl_id);

            if ($exists) {
                continue;
            }

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
            $this->dispatch('success', "$addedCount material(s) berhasil ditambahkan untuk return.");
        } else {
            $this->dispatch('warning', 'Semua material yang dipilih sudah ada dalam daftar return.');
        }
    }

    public function handleExchangeMaterialsSelected($selectedMaterials)
    {
        if (empty($selectedMaterials)) {
            $this->dispatch('error', 'Tidak ada material yang dipilih.');
            return;
        }

        $addedCount = 0;
        foreach ($selectedMaterials as $selectedItem) {
            // Handle both old format (just ID) and new format (array with matl_id and matl_uom)
            if (is_array($selectedItem)) {
                $matl_id = $selectedItem['matl_id'];
                $matl_uom = $selectedItem['matl_uom'] ?? 'PCS';
            } else {
                $matl_id = $selectedItem;
                $matl_uom = 'PCS';
            }

            $exists = collect($this->exchange_details)->contains('matl_id', $matl_id);

            if ($exists) {
                continue;
            }

            $key = count($this->exchange_details);
            $this->exchange_details[] = [
                'matl_id' => $matl_id,
                'matl_uom' => $matl_uom,
                'qty' => null,
                'price' => 0.0,
                'amt' => 0.0
            ];
            $this->onExchangeMaterialChanged($key, $matl_id);
            $addedCount++;
        }

        if ($addedCount > 0) {
            $this->dispatch('success', "$addedCount material(s) berhasil ditambahkan untuk exchange.");
        } else {
            $this->dispatch('warning', 'Semua material yang dipilih sudah ada dalam daftar exchange.');
        }
    }

    #endregion
}
