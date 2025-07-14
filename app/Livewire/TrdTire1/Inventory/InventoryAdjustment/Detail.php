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
            $this->inputs['tr_id'] = $this->object->tr_id;
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
        $this->inputs['tr_date']  = date('Y-m-d');
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

        $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'SALESORDER_LASTID');

        // Setelah header tersimpan, periksa tr_type
        if ($this->inputs['tr_type'] === 'TW') {
            $this->dispatch('toggleWarehouseDropdown', true);
        } else {
            $this->dispatch('toggleWarehouseDropdown', false);
        }

        // Process material details based on transaction type
        $this->processMaterialDetails();

        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Inventory.InventoryAdjustment.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
    }

    protected function processMaterialDetails()
    {
        if ($this->inputs['tr_type'] === 'TW') {
            // --- Logika TW (Transfer) ---
            $pair = 1;
            foreach ($this->input_details as $detail) {
                if (!$detail['is_editable']) {
                    continue;
                }
                $transferQty = $detail['qty'];
                $materialId = $detail['matl_id'];

                // Ambil batch stok dari gudang sumber secara FIFO
                $sourceBatches = IvtBal::where('wh_code', $this->inputs['wh_code'])
                    ->where('matl_id', $materialId)
                    ->orderBy('batch_code', 'asc')
                    ->get();

                if ($sourceBatches->isEmpty()) {
                    $sourceBatches->push(IvtBal::create([
                        'wh_code'    => $this->inputs['wh_code'],
                        'matl_id'    => $materialId,
                        'matl_code'  => $detail['matl_code'] ?? '',
                        'matl_uom'   => $detail['matl_uom'] ?? '',
                        'batch_code' => $detail['batch_code'] ?? '1',
                        'qty_oh'     => 0,
                    ]));
                }

                foreach ($sourceBatches as $batch) {
                    if ($transferQty <= 0) break;
                    if ($batch->qty_oh <= 0) continue;

                    $deduct = min($transferQty, $batch->qty_oh);
                    $batch->qty_oh -= $deduct;
                    $batch->save();

                    // Pencatatan pengurangan di sumber (tr_seq negatif)
                    $sourceDtl = IvttrDtl::where('trhdr_id', $this->object->id)
                        ->where('wh_code', $this->inputs['wh_code'])
                        ->where('matl_id', $materialId)
                        ->where('batch_code', $batch->batch_code)
                        ->first();
                    if ($sourceDtl) {
                        $sourceDtl->qty += (-$deduct);
                        $sourceDtl->save();
                    } else {
                        IvttrDtl::create([
                            'trhdr_id'   => $this->object->id,
                            'tr_seq'     => -$pair,
                            'wh_code'    => $this->inputs['wh_code'],
                            'matl_id'    => $materialId,
                            'tr_id'      => $this->object->id,
                            'matl_code'  => $batch->matl_code,
                            'matl_uom'   => $batch->matl_uom,
                            'batch_code' => $batch->batch_code,
                            'ivt_id'     => $batch->id,
                            'qty'        => -$deduct,
                        ]);
                    }

                    // Transfer ke gudang tujuan (wh_code2)
                    if (!empty($this->inputs['wh_code2'])) {
                        $destWarehouse = ConfigConst::where('str1', $this->inputs['wh_code2'])->first();
                        $dest_wh_id = $destWarehouse ? $destWarehouse->id : '';

                        $destBatch = IvtBal::where('wh_code', $this->inputs['wh_code2'])
                            ->where('matl_id', $materialId)
                            ->where('matl_uom', $batch->matl_uom)
                            ->where('batch_code', $batch->batch_code)
                            ->where('wh_id', $dest_wh_id)
                            ->first();

                        if ($destBatch) {
                            $destBatch->increment('qty_oh', $deduct);
                            $destBatch->refresh();
                        } else {
                            $destBatch = IvtBal::create([
                                'wh_code'    => $this->inputs['wh_code2'],
                                'matl_id'    => $materialId,
                                'matl_code'  => $batch->matl_code,
                                'matl_uom'   => $batch->matl_uom,
                                'batch_code' => $batch->batch_code,
                                'wh_id'      => $dest_wh_id,
                                'qty_oh'     => $deduct,
                            ]);
                        }

                        $destDtl = IvttrDtl::where('trhdr_id', $this->object->id)
                            ->where('wh_code', $this->inputs['wh_code2'])
                            ->where('matl_id', $materialId)
                            ->where('batch_code', $destBatch->batch_code)
                            ->first();
                        if ($destDtl) {
                            $destDtl->qty += $deduct;
                            $destDtl->save();
                        } else {
                            IvttrDtl::create([
                                'trhdr_id'   => $this->object->id,
                                'tr_seq'     => $pair,
                                'wh_code'    => $this->inputs['wh_code2'],
                                'matl_id'    => $materialId,
                                'tr_id'      => $this->object->id,
                                'matl_code'  => $destBatch->matl_code,
                                'matl_uom'   => $destBatch->matl_uom,
                                'batch_code' => $destBatch->batch_code,
                                'ivt_id'     => $destBatch->id,
                                'qty'        => $deduct,
                            ]);
                        }
                    }
                    $pair++;
                    $transferQty -= $deduct;
                }

                // Jika masih ada sisa transferQty, cari batch sumber berikutnya yang masih memiliki stok
                if ($transferQty > 0) {
                    $remainingBatch = $sourceBatches->first(function ($batch) {
                        return $batch->qty_oh > 0;
                    });
                    if ($remainingBatch) {
                        $remainingBatch->qty_oh -= $transferQty;
                        $remainingBatch->save();

                        $sourceDtl = IvttrDtl::where('trhdr_id', $this->object->id)
                            ->where('wh_code', $this->inputs['wh_code'])
                            ->where('matl_id', $materialId)
                            ->where('batch_code', $remainingBatch->batch_code)
                            ->first();
                        if ($sourceDtl) {
                            $sourceDtl->qty += (-$transferQty);
                            $sourceDtl->save();
                        } else {
                            IvttrDtl::create([
                                'trhdr_id'   => $this->object->id,
                                'tr_seq'     => -$pair,
                                'wh_code'    => $this->inputs['wh_code'],
                                'matl_id'    => $materialId,
                                'tr_id'      => $this->object->id,
                                'matl_code'  => $remainingBatch->matl_code,
                                'matl_uom'   => $remainingBatch->matl_uom,
                                'batch_code' => $remainingBatch->batch_code,
                                'ivt_id'     => $remainingBatch->id,
                                'qty'        => -$transferQty,
                            ]);
                        }

                        if (!empty($this->inputs['wh_code2'])) {
                            $destWarehouse = ConfigConst::where('str1', $this->inputs['wh_code2'])->first();
                            $dest_wh_id = $destWarehouse ? $destWarehouse->id : '';

                            $destBatch = IvtBal::where('wh_code', $this->inputs['wh_code2'])
                                ->where('matl_id', $materialId)
                                ->where('matl_uom', $remainingBatch->matl_uom)
                                ->where('batch_code', $remainingBatch->batch_code)
                                ->where('wh_id', $dest_wh_id)
                                ->first();
                            if ($destBatch) {
                                $destBatch->increment('qty_oh', $transferQty);
                                $destBatch->refresh();
                            } else {
                                $destBatch = IvtBal::create([
                                    'wh_code'    => $this->inputs['wh_code2'],
                                    'matl_id'    => $materialId,
                                    'matl_code'  => $remainingBatch->matl_code,
                                    'matl_uom'   => $remainingBatch->matl_uom,
                                    'batch_code' => $remainingBatch->batch_code,
                                    'wh_id'      => $dest_wh_id,
                                    'qty_oh'     => $transferQty,
                                ]);
                            }
                            $destDtl = IvttrDtl::where('trhdr_id', $this->object->id)
                                ->where('wh_code', $this->inputs['wh_code2'])
                                ->where('matl_id', $materialId)
                                ->where('batch_code', $destBatch->batch_code)
                                ->first();
                            if ($destDtl) {
                                $destDtl->qty += $transferQty;
                                $destDtl->save();
                            } else {
                                IvttrDtl::create([
                                    'trhdr_id'   => $this->object->id,
                                    'tr_seq'     => $pair,
                                    'wh_code'    => $this->inputs['wh_code2'],
                                    'matl_id'    => $materialId,
                                    'tr_id'      => $this->object->id,
                                    'matl_code'  => $destBatch->matl_code,
                                    'matl_uom'   => $destBatch->matl_uom,
                                    'batch_code' => $destBatch->batch_code,
                                    'ivt_id'     => $destBatch->id,
                                    'qty'        => $transferQty,
                                ]);
                            }
                        }
                        $pair++;
                        $transferQty = 0;
                    } else {
                        throw new Exception("Stok tidak mencukupi untuk material id: $materialId");
                    }
                }
            }
        } elseif ($this->inputs['tr_type'] === 'IA') {
            // --- Logika IA (Inventory Adjustment) ---
            // Hanya menggunakan satu gudang (wh_code).
            // Jika adjustQty negatif, lakukan pengurangan stok secara FIFO;
            // jika positif, tambahkan stok ke batch terbaru.
            foreach ($this->input_details as $detail) {
                if (!$detail['is_editable']) {
                    continue;
                }
                $adjustQty = $detail['qty'];
                $materialId = $detail['matl_id'];

                if ($adjustQty < 0) {
                    $remainingQty = abs($adjustQty);
                    $batches = IvtBal::where('wh_code', $this->inputs['wh_code'])
                        ->where('matl_id', $materialId)
                        ->orderBy('batch_code', 'asc')
                        ->get();
                    $tr_seq = 1;
                    foreach ($batches as $batch) {
                        if ($remainingQty <= 0) break;
                        if ($batch->qty_oh <= 0) continue;
                        $deduct = min($remainingQty, $batch->qty_oh);
                        $batch->qty_oh -= $deduct;
                        $batch->save();

                        $detailRecord = IvttrDtl::where('trhdr_id', $this->object->id)
                            ->where('wh_code', $this->inputs['wh_code'])
                            ->where('matl_id', $materialId)
                            ->where('batch_code', $batch->batch_code)
                            ->first();
                        if ($detailRecord) {
                            $detailRecord->qty += (-$deduct);
                            $detailRecord->save();
                        } else {
                            IvttrDtl::create([
                                'trhdr_id'   => $this->object->id,
                                'tr_seq'     => $tr_seq,
                                'wh_code'    => $this->inputs['wh_code'],
                                'matl_id'    => $materialId,
                                'tr_id'      => $this->object->id,
                                'matl_code'  => $batch->matl_code,
                                'matl_uom'   => $batch->matl_uom,
                                'batch_code' => $batch->batch_code,
                                'ivt_id'     => $batch->id,
                                'qty'        => -$deduct,
                            ]);
                        }
                        $tr_seq++;
                        $remainingQty -= $deduct;
                    }
                    if ($remainingQty > 0) {
                        throw new Exception("Stok tidak mencukupi untuk material id: $materialId");
                    }
                } elseif ($adjustQty > 0) {
                    $batch = IvtBal::where('wh_code', $this->inputs['wh_code'])
                        ->where('matl_id', $materialId)
                        ->orderBy('id', 'desc')
                        ->first();
                    if ($batch) {
                        $batch->increment('qty_oh', $adjustQty);
                        $batch->refresh();
                    } else {
                        $batch = IvtBal::create([
                            'wh_code'    => $this->inputs['wh_code'],
                            'matl_id'    => $materialId,
                            'matl_code'  => $detail['matl_code'] ?? '',
                            'matl_uom'   => $detail['matl_uom'] ?? '',
                            'batch_code' => $detail['batch_code'] ?? '1',
                            'qty_oh'     => $adjustQty,
                        ]);
                    }

                    $detailRecord = IvttrDtl::where('trhdr_id', $this->object->id)
                        ->where('wh_code', $this->inputs['wh_code'])
                        ->where('matl_id', $materialId)
                        ->where('batch_code', $batch->batch_code)
                        ->first();
                    if ($detailRecord) {
                        $detailRecord->qty += $adjustQty;
                        $detailRecord->save();
                    } else {
                        IvttrDtl::create([
                            'trhdr_id'   => $this->object->id,
                            'tr_seq'     => 1,
                            'wh_code'    => $this->inputs['wh_code'],
                            'matl_id'    => $materialId,
                            'tr_id'      => $this->object->id,
                            'matl_code'  => $batch->matl_code,
                            'matl_uom'   => $batch->matl_uom,
                            'batch_code' => $batch->batch_code,
                            'ivt_id'     => $batch->id,
                            'qty'        => $adjustQty,
                        ]);
                    }
                }
            }
        }
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

    public function onTypeChanged($value)
    {
        $this->inputs['tr_type'] = $value;
        $enabled = $value === 'TW';
        $this->dispatch('toggleWarehouseDropdown', $enabled);
    }

    public function isEditOrView() {
        return $this->actionValue === 'Edit' || $this->actionValue === 'View';
    }

    public function getMaterialLabel($matlId)
    {
        if (!$this->materials) return '';

        $material = $this->materials->firstWhere('value', $matlId);
        return $material['label'] ?? '';
    }

    public function isItemEditable($inputDetail)
    {
        return isset($inputDetail['is_editable']) && $inputDetail['is_editable'];
    }
    #endregion
}
