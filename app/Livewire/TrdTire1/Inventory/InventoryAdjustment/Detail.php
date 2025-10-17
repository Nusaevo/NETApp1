<?php

namespace App\Livewire\TrdTire1\Inventory\InventoryAdjustment;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Inventories\IvttrHdr;
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Inventories\IvttrDtl;
use App\Services\TrdTire1\InventoryService;
use Illuminate\Support\Facades\{Session, DB};
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $paymentTerms = [];
    public $partnerSearchText = '';
    public $selectedPartners = [];
    public $warehouses;
    public $warehousesType;
    public $partners;
    public $transaction_id;
    public $payments;
    public $trType;
    public $deletedItems = [];
    public $newItems = [];

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    protected $masterService;
    public $isPanelEnabled = "false";
    public $isEdit = "false";

    // Material List Component Properties
    public $materials;
    public $materialQuery = "";
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    public $wh_code;
    public $isEditWhCode2 = "false";
    public $matl_id;
    public $qty;
    public $batchOptions = [];

    public $rules  = [
        'inputs.tr_date' => 'required',
        'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'updateAmount' => 'updateAmount',
        'updateDiscount' => 'updateDiscount',
        'updateDPP' => 'updateDPP',
        'updatePPN' => 'updatePPN',
        'updateTotalTax' => 'updateTotalTax',
        'toggleWarehouseDropdown' => 'toggleWarehouseDropdown',
        'onMaterialChanged' => 'onMaterialChanged',
    ];
    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
        ];

        $this->isEdit = $this->isEditOrView() ? 'true' : 'false';
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->warehousesType = $this->masterService->getWarehouseType();

        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = IvttrHdr::with('IvttrDtl')->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['tr_type'] = $this->object->tr_type;
            $this->inputs['tr_date'] = $this->object->tr_date ? $this->object->tr_date->format('Y-m-d') : null;
            $this->inputs['tr_code'] = $this->object->tr_code;
            $this->inputs['wh_code'] = $this->object->IvttrDtl->first()->wh_code ?? null;
            $this->inputs['tr_descr'] = $this->object->IvttrDtl->first()->tr_descr ?? null;

            // Tambahkan baris berikut untuk TW
            if ($this->object->tr_type === 'TW') {
                // Ambil wh_code2 dari detail dengan tr_seq > 0 (tujuan)
                $detailTujuan = $this->object->IvttrDtl->where('tr_seq', '>', 0)->first();
                $this->inputs['wh_code2'] = $detailTujuan->wh_code ?? null;
            }

            // Load material details
            $this->loadDetails();
        }

        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
            if (isset($this->inputs['tr_type']) && $this->inputs['tr_type'] === 'TW') {
                $this->isEditWhCode2 = 'true';
            } else {
                $this->isEditWhCode2 = 'false';
            }
        }

        // Set warehouse dropdown state based on tr_type

        // Load materials based on warehouse
        if (!empty($this->inputs['wh_code'])) {
            $this->loadMaterialsByWarehouse($this->inputs['wh_code']);
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new IvttrHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['wh_code'] = "";
    }

    public function render()
    {
        $filteredBatchCode = [];
        foreach ($this->input_details as $key => $detail) {
            $matl_id = $detail['matl_id'] ?? null;
            $filteredBatchCode[$key] = $matl_id ? ($this->batchOptions[$matl_id] ?? []) : [];
        }
        // dd($this->batchOptions, $this->input_details);
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'filteredMaterials' => $this->materials,
            'filteredBatchCode' => $filteredBatchCode,
        ]);
    }
    #endregion

    #region Material List Methods

    public function addItem()
    {
        if (empty($this->inputs['wh_code'])) {
            $this->dispatch('error', 'Mohon pilih gudang terlebih dahulu.');
            return;
        }
        $this->input_details[] = [
            'matl_id'     => null,
            'batch_code'  => null,
            'qty_oh'      => null,
            'qty'         => null, // user input
            'qty_end'     => null, // user input
            'is_editable' => true,
        ];
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            $detail = $this->input_details[$index];

            if (!empty($detail['id'])) {
                $pos = IvttrDtl::find($detail['id']);

                if ($pos) {
                    // Hapus kedua record: tr_seq positif (misalnya 1) dan negatif (misalnya -1)
                    IvttrDtl::where('trhdr_id', $pos->trhdr_id)
                        ->whereIn('tr_seq', [$pos->tr_seq, -$pos->tr_seq])
                        ->delete();
                }
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $destinationDetails = IvttrDtl::where('trhdr_id', $this->object->id)
                ->where('tr_seq', '>', 0)
                ->orderBy('tr_seq')
                ->get();

            // Gunakan inputs.wh_code sebagai warehouse asal
            $wh_code_asal = $this->inputs['wh_code'] ?? null;
            $wh_code_tujuan = $this->inputs['wh_code2'] ?? null;

            // Ambil semua material unik dari detail
            $matl_ids = $destinationDetails->pluck('matl_id')->unique()->toArray();
            $this->batchOptions = [];
            if ($wh_code_asal) {
                foreach ($matl_ids as $matl_id) {
                    $batches = IvtBal::where('wh_code', $wh_code_asal)
                        ->where('matl_id', $matl_id)
                        ->get();
                    foreach ($batches as $bal) {
                        // Untuk mode edit, hitung stock sebelum transaksi
                        $tr_type = $this->inputs['tr_type'] ?? null;
                        $qty_oh_before = $bal->qty_oh; // Default: stock saat ini

                        // Cari detail yang sesuai dengan batch ini
                        $detailForBatch = $destinationDetails->where('matl_id', $matl_id)
                            ->where('batch_code', $bal->batch_code)
                            ->first();

                        if ($detailForBatch) {
                            $qty = $detailForBatch->qty;
                            if ($tr_type === 'IA') {
                                // Untuk IA: qty_oh_sebelum = qty_oh_sekarang - qty_adjustment
                                $qty_oh_before = $bal->qty_oh - $qty;
                            } else {
                                // Untuk TW: qty_oh_sebelum = qty_oh_sekarang + qty_transfer
                                $qty_oh_before = $bal->qty_oh + $qty;
                            }
                        }

                        $this->batchOptions[$matl_id][] = [
                            'value' => $bal->batch_code,
                            'label' => $bal->batch_code,
                            'qty_oh' => $qty_oh_before,
                        ];
                    }
                }
            }

            $this->input_details = [];
            foreach ($destinationDetails as $key => $detail) {
                // Hitung qty_oh sebelum transaksi (stock asli sebelum adjustment)
                $qty_oh = null;
                if ($wh_code_asal) {
                    $ivtBal = IvtBal::where('wh_code', $wh_code_asal)
                        ->where('matl_id', $detail->matl_id)
                        ->where('batch_code', $detail->batch_code)
                        ->first();
                    if ($ivtBal) {
                        // Untuk mode edit, hitung stock sebelum transaksi
                        $qty = $detail->qty;
                        $tr_type = $this->inputs['tr_type'] ?? null;
                        if ($tr_type === 'IA') {
                            // Untuk IA: qty_oh_sebelum = qty_oh_sekarang - qty_adjustment
                            $qty_oh = $ivtBal->qty_oh - $qty;
                        } else {
                            // Untuk TW: qty_oh_sebelum = qty_oh_sekarang + qty_transfer
                            $qty_oh = $ivtBal->qty_oh + $qty;
                        }
                    }
                }
                if ($qty_oh === null && isset($detail->qty_oh)) {
                    $qty_oh = $detail->qty_oh;
                }

                $qty = $detail->qty;
                $tr_type = $this->inputs['tr_type'] ?? null;
                if ($tr_type === 'IA') {
                    $qty_end = is_numeric($qty_oh) && is_numeric($qty) ? $qty_oh + $qty : null;
                } else {
                    $qty_end = is_numeric($qty_oh) && is_numeric($qty) ? $qty_oh - $qty : null;
                }

                $this->input_details[] = [
                    'matl_id'    => $detail->matl_id,
                    'batch_code' => $detail->batch_code,
                    'qty_oh'     => $qty_oh,
                    'qty'        => $qty,
                    'qty_end'    => $qty_end,
                    'is_editable'=> false,
                ];
            }
            // Tidak perlu ambil wh_code asal dari detail, cukup dari inputs.wh_code
        }
    }

    public function onWarehouseChanged($whCode)
    {
        $this->inputs['wh_code'] = $whCode;
        $this->loadMaterialsByWarehouse($whCode);

        $tr_type = $this->inputs['tr_type'] ?? null;

        if ($tr_type === 'IA') {
            // Untuk IA, ambil batch dari IvtBal dan tambahkan default 240101 untuk material yang tidak ada
            $ivtBals = IvtBal::where('wh_code', $whCode)->get();
            $this->batchOptions = [];

            // Ambil semua material yang ada di ivt_bal
            $materialsInIvtBal = $ivtBals->pluck('matl_id')->unique();

            foreach ($ivtBals as $bal) {
                $this->batchOptions[$bal->matl_id][] = [
                    'value' => $bal->batch_code,
                    'label' => $bal->batch_code,
                    'qty_oh' => $bal->qty_oh,
                ];
            }

            // Untuk material yang tidak ada di ivt_bal, tambahkan default batch 240101
            $allMaterials = Material::where('status_code', 'A')
                ->where('deleted_at', null)
                ->whereNotIn('id', $materialsInIvtBal)
                ->get();

            foreach ($allMaterials as $material) {
                $this->batchOptions[$material->id][] = [
                    'value' => '240101',
                    'label' => '240101',
                    'qty_oh' => 0,
                ];
            }
        } else {
            // Untuk TW, ambil semua kombinasi matl_id dan batch_code dari IvtBal untuk whCode
            $ivtBals = IvtBal::where('wh_code', $whCode)->get();
            $this->batchOptions = [];
            foreach ($ivtBals as $bal) {
                $this->batchOptions[$bal->matl_id][] = [
                    'value' => $bal->batch_code,
                    'label' => $bal->batch_code,
                    'qty_oh' => $bal->qty_oh,
                ];
            }
        }

        // Jangan langsung isi input_details
        $this->input_details = [];
    }

    protected function loadMaterialsByWarehouse($whCode)
    {
        $tr_type = $this->inputs['tr_type'] ?? null;

        if ($tr_type === 'IA') {
            // Untuk IA, tampilkan semua material aktif, termasuk yang tidak ada di ivt_bal
            $this->materialQuery = "
                SELECT m.id, m.code, m.name, coalesce(b.qty_oh,0) qty_oh, coalesce(b.qty_fgi,0) qty_fgi
                FROM materials m
                LEFT OUTER JOIN (
                    select matl_id, SUM(qty_oh)::int as qty_oh,SUM(qty_fgi)::int as qty_fgi
                    from ivt_bals
                    where wh_code = '$whCode'
                    group by matl_id
                    ) b on b.matl_id = m.id
                WHERE m.status_code = 'A'
                AND m.deleted_at IS NULL
            ";
        } else {
            // Untuk TW, hanya tampilkan material yang ada di ivt_bal
            $this->materialQuery = "
                SELECT m.id, m.code, m.name, coalesce(b.qty_oh,0) qty_oh, coalesce(b.qty_fgi,0) qty_fgi
                FROM materials m
                LEFT OUTER JOIN (
                    select matl_id, SUM(qty_oh)::int as qty_oh,SUM(qty_fgi)::int as qty_fgi
                    from ivt_bals
                    where wh_code = '$whCode'
                    group by matl_id
                    ) b on b.matl_id = m.id
                WHERE m.status_code = 'A'
                AND m.deleted_at IS NULL
                AND b.matl_id IS NOT NULL
            ";
        }

        // Untuk mode edit, juga isi $this->materials dengan data yang sudah dipilih
        if ($this->isEditOrView() && !empty($this->input_details)) {
            $selectedMatlIds = collect($this->input_details)->pluck('matl_id')->filter()->unique()->toArray();
            if (!empty($selectedMatlIds)) {
                $selectedMaterials = Material::whereIn('id', $selectedMatlIds)->get()
                    ->map(fn($m) => [
                        'value' => $m->id,
                        'label' => $m->code . " - " . $m->name,
                    ]);
                $this->materials = $selectedMaterials;
            }
        }
    }

    public function toggleWarehouseDropdown($enabled)
    {
        $this->isEditWhCode2 = $enabled ? 'true' : 'false';
    }

    public function updatedInputDetails($value, $key)
    {
        // $key format: index.field
        [$index, $field] = explode('.', $key);
        if ($field === 'matl_id' && isset($this->input_details[$index]['matl_id'])) {
            $matl_id = $this->input_details[$index]['matl_id'];
            // Reset batch_code dan qty_oh ketika material berubah
            $this->input_details[$index]['batch_code'] = null;
            $this->input_details[$index]['qty_oh'] = null;
        }
        if ($field === 'batch_code' && isset($this->input_details[$index]['matl_id'], $this->input_details[$index]['batch_code'])) {
            $matl_id = $this->input_details[$index]['matl_id'];
            $batch_code = $this->input_details[$index]['batch_code'];
            // Cari qty_oh dari batchOptions
            if (isset($this->batchOptions[$matl_id])) {
                foreach ($this->batchOptions[$matl_id] as $batch) {
                    if ($batch['value'] == $batch_code) {
                        $this->input_details[$index]['qty_oh'] = $batch['qty_oh'];
                        break;
                    }
                }
            }
        }
    }

    public function onMaterialChanged($index, $matl_id = null)
    {
        if ($matl_id) {
            $this->input_details[$index]['matl_id'] = $matl_id;
        }

        if (!isset($this->input_details[$index]['matl_id'])) {
            $this->input_details[$index]['batch_code'] = null;
            $this->input_details[$index]['qty_oh'] = null;
            return;
        }

        $matl_id = $this->input_details[$index]['matl_id'];
        $tr_type = $this->inputs['tr_type'] ?? null;

        if ($tr_type === 'IA') {
            // Untuk IA, cek apakah ada batch di ivt_bal
            $wh_code = $this->inputs['wh_code'] ?? null;
            $hasBatch = false;

            if ($wh_code && isset($this->batchOptions[$matl_id])) {
                // Ada batch di ivt_bal, reset untuk dipilih user
                $this->input_details[$index]['batch_code'] = null;
                $this->input_details[$index]['qty_oh'] = null;
                $hasBatch = true;
            }

            if (!$hasBatch) {
                // Tidak ada batch di ivt_bal, gunakan default 240101
                $this->input_details[$index]['batch_code'] = '240101';
                $this->input_details[$index]['qty_oh'] = 0; // Default stock 0 untuk material baru
            }
        } else {
            // Untuk TW, reset batch_code dan qty_oh, akan diisi oleh onBatchCodeChanged
            $this->input_details[$index]['batch_code'] = null;
            $this->input_details[$index]['qty_oh'] = null;
        }
    }

    public function onBatchCodeChanged($index)
    {
        if (!isset($this->input_details[$index]['matl_id'])) {
            $this->input_details[$index]['qty_oh'] = null;
            return;
        }

        $matl_id = $this->input_details[$index]['matl_id'];
        $batch_code = $this->input_details[$index]['batch_code'] ?? null;
        $tr_type = $this->inputs['tr_type'] ?? null;

        if (!isset($this->input_details[$index]['batch_code'])) {
            $this->input_details[$index]['qty_oh'] = null;
            return;
        }

        if ($tr_type === 'IA') {
            // Untuk IA, cek apakah ada batch di batchOptions
            if (isset($this->batchOptions[$matl_id])) {
                foreach ($this->batchOptions[$matl_id] as $batch) {
                    if ($batch['value'] == $batch_code) {
                        $this->input_details[$index]['qty_oh'] = $batch['qty_oh'];
                        return;
                    }
                }
            }
            // Jika tidak ada di batchOptions, set qty_oh = 0 (material baru)
            $this->input_details[$index]['qty_oh'] = 0;
        } else {
            // Untuk TW, gunakan logic lama
            if (isset($this->batchOptions[$matl_id])) {
                foreach ($this->batchOptions[$matl_id] as $batch) {
                    if ($batch['value'] == $batch_code) {
                        $this->input_details[$index]['qty_oh'] = $batch['qty_oh'];
                        return;
                    }
                }
            }
            $this->input_details[$index]['qty_oh'] = null;
        }
    }

    public function onQtyChanged($index)
    {
        $qty_oh = $this->input_details[$index]['qty_oh'] ?? null;
        $qty = $this->input_details[$index]['qty'] ?? null;
        $tr_type = $this->inputs['tr_type'] ?? null;

        if ($tr_type === 'IA') {
            // Untuk IA: qty_end = qty_oh + qty (adjustment ditambahkan ke stock)
            if (is_numeric($qty_oh) && is_numeric($qty)) {
                $this->input_details[$index]['qty_end'] = $qty_oh + $qty;
            } else {
                $this->input_details[$index]['qty_end'] = null;
            }
        } else {
            // Untuk TW: qty_end = qty_oh - qty (stock dikurangi untuk transfer)
            if (is_numeric($qty_oh) && is_numeric($qty)) {
                $this->input_details[$index]['qty_end'] = $qty_oh - $qty;
            } else {
                $this->input_details[$index]['qty_end'] = null;
            }
        }
    }

    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        // dd($this->inputs, $this->input_details);
        if ($this->actionValue == 'Edit') {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            // Hapus detail lama terlebih dahulu untuk mode edit
            $this->deleteExistingDetails();
        }

        $warehouseType = ConfigConst::where('str1', $this->inputs['tr_type'])->first();
        if ($warehouseType) {
            $this->inputs['tr_type'] = $warehouseType->str1;
        }

        $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'IVT_LASTID');

        // Ambil ulang object agar tr_code terisi
        $this->object->refresh();
        $this->inputs['id'] = $this->object->id;
        $this->inputs['tr_code'] = $this->object->tr_code;

        // Setelah header tersimpan, periksa tr_type
        if ($this->inputs['tr_type'] === 'TW') {
            // Convert warehouse codes to IDs
            $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();
            $warehouse2 = ConfigConst::where('str1', $this->inputs['wh_code2'])->first();

            $this->inputs['wh_id'] = $warehouse ? $warehouse->id : null;
            $this->inputs['wh_id2'] = $warehouse2 ? $warehouse2->id : null;
            // $this->inputs['id'] dan tr_code sudah di-set di atas

            $detailData = $this->prepareBatchCode();
            // dd($detailData);


            app(InventoryService::class)->saveInventoryTrx($this->inputs, $detailData);
        } else {
            // Untuk IA, isi wh_id dari wh_code
            $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();
            $this->inputs['wh_id'] = $warehouse ? $warehouse->id : null;
            // $this->inputs['id'] dan tr_code sudah di-set di atas

            $detailData = $this->prepareBatchCode();
            app(InventoryService::class)->saveInventoryTrx($this->inputs, $detailData);

        }
        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Inventory.InventoryAdjustment.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
    }


    public function delete()
    {
        try {
            // if ($this->object->isOrderCompleted()) {
            //     $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
            //     return;
            // }

            // if (!$this->object->isOrderEnableToDelete()) {
            //     $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
            //     return;
            // }

            if (isset($this->object->status_code)) {
                $this->object->status_code =  Status::NONACTIVE;
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

    public function deleteTransaction()
    {
        try {
            app(InventoryService::class)->delInventory($this->object->id);
            $this->dispatch('success', __('generic.string.delete'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.disable', ['message' => $e->getMessage()]));
        }
        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }

    public function onTypeChanged($value)
    {
        $this->inputs['tr_type'] = $value;
        $enabled = $value === 'TW';
        $this->dispatch('toggleWarehouseDropdown', $enabled);
    }

    public function isEditOrView() {
        return $this->actionValue === 'Edit' || $this->actionValue === 'View';
    }

    protected function deleteExistingDetails()
    {
        if (!empty($this->object->id)) {
            // Hapus log inventory terlebih dahulu
            app(InventoryService::class)->delIvtLog($this->object->id);

            // Hapus detail inventory
            IvttrDtl::where('trhdr_id', $this->object->id)->delete();
        }
    }

    protected function prepareBatchCode()
    {
        $result = [];
        $tr_type = $this->inputs['tr_type'] ?? null;
        $whIdFrom = $this->inputs['wh_id'] ?? null;
        $whCodeFrom = $this->inputs['wh_code'] ?? null;
        $whIdTo = $this->inputs['wh_id2'] ?? null;
        $whCodeTo = $this->inputs['wh_code2'] ?? null;

        if ($tr_type === 'IA') {
            if (!$whIdFrom || empty($this->input_details)) {
                return $result;
            }
        } else {
            if (!$whIdFrom || !$whIdTo || empty($this->input_details)) {
                return $result;
            }
        }

        // Ambil semua matl_id unik dari input_details
        $matlIds = collect($this->input_details)->pluck('matl_id')->unique()->filter()->toArray();
        // Ambil semua material sekaligus, index by id
        $materials = Material::whereIn('id', $matlIds)->get()->keyBy('id');

        $seq = 1;
        foreach ($this->input_details as $item) {
            $matlId = $item['matl_id'];
            $qty_user = $item['qty'];
            $qty_oh = $item['qty_oh'] ?? 0;
            $batchCode = $item['batch_code'] ?? null;
            if (!$matlId || $qty_user === null || !$batchCode) continue;

            // qty langsung dari input user, baik TW maupun IA
            $qty = $qty_user;

            // Ambil material info dari hasil query di atas
            $material = $materials->get($matlId);
            $matlUom = $material->uom ?? null;
            $matlCode = $material->code ?? null;

            // Untuk IA dan TW, pastikan record IvtBal ada
            $existingIvtBal = IvtBal::where('wh_code', $whCodeFrom)
                ->where('matl_id', $matlId)
                ->where('batch_code', $batchCode)
                ->first();

            if (!$existingIvtBal) {
                // Dapatkan wh_id dari wh_code menggunakan ConfigConst
                $warehouse = ConfigConst::where('str1', $whCodeFrom)->first();
                $whId = $warehouse ? $warehouse->id : null;

                // Buat record baru di IvtBal dengan semua field yang diperlukan
                IvtBal::create([
                    'wh_code' => $whCodeFrom,
                    'wh_id' => $whId,
                    'matl_id' => $matlId,
                    'matl_code' => $matlCode,
                    'matl_uom' => $matlUom,
                    'batch_code' => $batchCode,
                    'qty_oh' => 0,
                    'qty_fgi' => 0,
                ]);
            }

            if ($tr_type === 'IA') {
                $result[] = [
                    'id'        => 0,
                    'tr_seq'    => $seq,
                    'matl_id'   => $matlId,
                    'matl_code' => $matlCode,
                    'matl_uom'  => $matlUom,
                    'batch_code'=> $batchCode,
                    'qty'       => $qty,
                    'wh_id'     => $whIdFrom,
                    'wh_code'   => $whCodeFrom,
                ];
            } else {
                $result[] = [
                    'id'        => 0,
                    'id2'       => 0,
                    'tr_seq'    => $seq,
                    'matl_id'   => $matlId,
                    'matl_code' => $matlCode,
                    'matl_uom'  => $matlUom,
                    'batch_code'=> $batchCode,
                    'qty'       => $qty,
                    'wh_id'     => $whIdFrom,
                    'wh_code'   => $whCodeFrom,
                    'wh_id2'    => $whIdTo,
                    'wh_code2'  => $whCodeTo,
                ];
            }
            $seq++;
        }
        return $result;
    }
    #endregion
}
