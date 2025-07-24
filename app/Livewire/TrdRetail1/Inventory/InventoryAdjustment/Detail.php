<?php

namespace App\Livewire\TrdRetail1\Inventory\InventoryAdjustment;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Inventories\IvttrHdr;
use App\Models\TrdRetail1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Inventories\IvttrDtl;
use App\Services\TrdRetail1\InventoryService;
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
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    public $wh_code;
    public $isEditWhCode2 = "false";
    public $matl_id;
    public $qty;

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
            $this->inputs['tr_date'] = $this->object->tr_date;
            $this->inputs['tr_code'] = $this->object->tr_code;
            $this->inputs['wh_code'] = $this->object->IvttrDtl->first()->wh_code ?? null;
            $this->inputs['tr_descr'] = $this->object->IvttrDtl->first()->tr_descr ?? null;

            // Load material details
            $this->loadDetails();
        }

        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
        }

        // Set warehouse dropdown state based on tr_type
        if (isset($this->inputs['tr_type']) && $this->inputs['tr_type'] === 'TW') {
            $this->isEditWhCode2 = 'true';
        } else {
            $this->isEditWhCode2 = 'false';
        }

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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'filteredMaterials' => $this->materials,
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
            'qty'         => null,
            'wh_code'     => $this->inputs['wh_code'],
            'is_editable' => true,
        ];
        $this->onWarehouseChanged($this->inputs['wh_code']);
    }

    public function updateItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            $amount = $this->input_details[$key]['qty'] * $this->input_details[$key]['price'];
            $discountPercent = $this->input_details[$key]['disc_pct'] ?? 0;
            $discountAmount = $amount * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amount - $discountAmount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }

        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);
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
            // Ambil detail transaksi tujuan (tr_seq positif)
            $destinationDetails = IvttrDtl::where('trhdr_id', $this->object->id)
                ->where('tr_seq', '>', 0)
                ->orderBy('tr_seq')
                ->get();

            // Grouping berdasarkan matl_id untuk menggabungkan record yang berbeda (misal karena beda batch_code)
            $grouped = $destinationDetails->groupBy('matl_id');
            $this->input_details = [];
            foreach ($grouped as $matlId => $details) {
                $firstDetail = $details->first();
                $totalQty = $details->sum('qty');

                // Ambil data material (opsional, untuk menampilkan kode/nama material)
                $material = Material::find($matlId);

                $this->input_details[] = [
                    'matl_id'    => $matlId,
                    'matl_code'  => $material->code ?? $firstDetail->matl_code,
                    'matl_name'  => $material->name ?? '',
                    'qty'        => $totalQty,
                    'is_editable' => false,
                ];
            }

            // Ambil detail transaksi sumber (tr_seq negatif) untuk mendapatkan wh_code asal
            $sourceDetail = IvttrDtl::where('trhdr_id', $this->object->id)
                ->where('tr_seq', '<', 0)
                ->first();
            if ($sourceDetail) {
                $this->inputs['wh_code'] = $sourceDetail->wh_code;
            }

            // Ambil wh_code tujuan dari transaksi sisi tujuan
            if ($destinationDetails->count() > 0) {
                $destRecord = $destinationDetails->first()->wh_code;
                // Jika format wh_code tujuan adalah "G02 (from G01)", ambil bagian sebelum " (from "
                if (strpos($destRecord, ' (from ') !== false) {
                    $parts = explode(' (from ', $destRecord);
                    $this->inputs['wh_code2'] = trim($parts[0]);
                } else {
                    $this->inputs['wh_code2'] = $destRecord;
                }
            }
        }
    }

    public function onWarehouseChanged($whCode)
    {
        $this->inputs['wh_code'] = $whCode;
        $this->loadMaterialsByWarehouse($whCode);

        if (!empty($this->input_details)) {
            $lastIndex = count($this->input_details) - 1;
            $this->input_details[$lastIndex]['matl_id'] = $this->materials->first()->value ?? null;
        }
    }

    protected function loadMaterialsByWarehouse($whCode)
    {
        $materialIds = IvtBal::where('wh_code', $whCode)->pluck('matl_id')->toArray();
        $this->materials = Material::whereIn('id', $materialIds)->get()
            ->map(fn($m) => [
                'value' => $m->id,
                'label' => $m->code . " - " . $m->name,
            ]);
    }

    public function toggleWarehouseDropdown($enabled)
    {
        $this->isEditWhCode2 = $enabled ? 'true' : 'false';
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
        }

        $warehouseType = ConfigConst::where('str1', $this->inputs['tr_type'])->first();
        if ($warehouseType) {
            $this->inputs['tr_type'] = $warehouseType->str1;
        }

        // Ambil detailData dari prepareBatchCode


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
            $this->inputs['id'] = $this->object->id;

            // dd('tes');
            $detailData = $this->prepareBatchCode();
            // dd($detailData);

            if ($this->actionValue == 'Edit') {
                app(InventoryService::class)->updInventory($this->inputs, $detailData, $this->object->id);
            } else {
                app(InventoryService::class)->addInventory($this->inputs, $detailData);
            }
        } else {
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
            $messageKey = 'generic.string.disable';
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
            $this->dispatch('success', __('generic.string.disable'));
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

    protected function prepareBatchCode()
    {
        $result = [];
        $whIdFrom = $this->inputs['wh_id'];
        $whCodeFrom = $this->inputs['wh_code'];
        $whIdTo = $this->inputs['wh_id2'];
        $whCodeTo = $this->inputs['wh_code2'];

        if (!$whIdFrom || !$whIdTo || empty($this->input_details)) {
            return $result;
        }

        $seq = 1;
        foreach ($this->input_details as $item) {
            $matlId = $item['matl_id'];
            $qtyNeeded = $item['qty'];
            if (!$matlId || $qtyNeeded <= 0) continue;

            // Ambil material info
            $material = Material::find($matlId);
            $matlUom = $material->uom ?? null;
            $matlCode = $material->code ?? null;

            $batches = IvtBal::where('wh_id', $whIdFrom)
                ->where('matl_id', $matlId)
                ->where('qty_oh', '>', 0)
                ->orderBy('batch_code')
                ->orderBy('id')
                ->get();

            foreach ($batches as $batch) {
                if ($qtyNeeded <= 0) break;
                $takeQty = min($batch->qty_oh, $qtyNeeded);
                $result[] = [
                    'tr_seq'    => $seq,
                    'matl_id'   => $matlId,
                    'matl_code' => $matlCode,
                    'matl_uom'  => $matlUom,
                    'batch_code'=> $batch->batch_code,
                    'qty'       => $takeQty,
                    'wh_id'     => $whIdFrom,
                    'wh_code'   => $whCodeFrom,
                    'wh_id2'    => $whIdTo,
                    'wh_code2'  => $whCodeTo,
                ];
                $qtyNeeded -= $takeQty;
                $seq++;
            }
            // dd($result);
        }
        return $result;
    }
    #endregion
}
