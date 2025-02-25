<?php

namespace App\Livewire\TrdTire1\Inventory\InventoryAdjustment;

use App\Livewire\Component\DetailComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\MatlUom; // Add this import
use App\Models\TrdTire1\Inventories\IvtBal; // Add this import
use App\Models\TrdTire1\Inventories\IvttrDtl; // Add this import
use App\Models\TrdTire1\Inventories\IvttrHdr;
use Exception;
use Illuminate\Support\Facades\DB;

class MaterialListComponent extends DetailComponent
{
    public $materials;
    protected $masterService;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $warehouses;
    public $warehousesType;
    public $tr_code;
    public $input_details = [];
    public $wh_code;
    public $isEdit = "false";
    public $isEditWhCode2 = "false";
    public $inputs = [];
    public $matl_id;
    public $qty;


    protected $rules = [
        'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
    ];

    protected $listeners = [
        'toggleWarehouseDropdown' => 'toggleWarehouseDropdown',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $wh_code = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
        $this->wh_code = $wh_code;
    }


    public function onReset()
    {
        $this->reset('input_details');
        $this->object = new IvttrHdr();
        $this->object = new IvttrDtl();
    }

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->warehousesType = $this->masterService->getWarehouseType();

        // Jika ada objectId, ambil data header dan detailnya
        if (!empty($this->objectIdValue)) {
            $this->object = IvttrHdr::find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
        }

        // Jika sudah terdapat item detail yang tersimpan, anggap data sudah disave
        if (!empty($this->input_details)) {
            $this->isEdit = 'false';
            $this->isEditWhCode2 = 'false';
        } else {
            // Jika belum ada item detail, tentukan berdasarkan tr_type
            $this->isEdit = $this->isEditOrView() ? 'true' : 'false';
            if (isset($this->inputs['tr_type']) && $this->inputs['tr_type'] === 'TW') {
                $this->isEditWhCode2 = 'true';
            } else {
                $this->isEditWhCode2 = 'false';
            }
        }

        // Jika terdapat wh_code, load daftar material berdasarkan gudang tersebut
        if (!empty($this->inputs['wh_code'])) {
            $materialIds = IvtBal::where('wh_code', $this->inputs['wh_code'])
                ->pluck('matl_id')
                ->toArray();
            $this->materials = Material::whereIn('id', $materialIds)
                ->get()
                ->map(fn($m) => [
                    'value' => $m->id,
                    'label' => $m->code . " - " . $m->name,
                ]);
        }
    }


    public function addItem()
    {
        if (empty($this->inputs['wh_code'])) {
            $this->dispatch('error', 'Mohon pilih gudang terlebih dahulu.');
            return;
        }
        $this->input_details[] = [
            'matl_id'     => null,
            'qty'         => 0,
            'wh_code'     => $this->inputs['wh_code'],
            'is_editable' => true,
        ];
        $this->onWarehouseChanged($this->inputs['wh_code']);
    }


    protected function getIvtBall($whCode)
    {
        $data = DB::table('ivtBall')->where('wh_code', $whCode)->get();
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

    public function SaveItem()
    {
        $this->Save();

        foreach ($this->input_details as $key => $detail) {
            $this->input_details[$key]['is_editable'] = false;
        }

        $this->isEdit = 'false';
        $this->isEditWhCode2 = 'false';

        $this->dispatch('toggleWarehouseDropdown', false);
    }



    public function onValidateAndSave()
    {
        $pair = 1;

        foreach ($this->input_details as $detail) {
            // Hanya proses item yang masih bisa diedit
            if (!$detail['is_editable']) {
                continue;
            }

            $transferQty = $detail['qty'];
            $materialId = $detail['matl_id'];

            // Ambil record IvtBal di gudang sumber (wh_code) untuk material tertentu, diurutkan berdasarkan batch_code (FIFO)
            $sourceBatches = IvtBal::where('wh_code', $this->inputs['wh_code'])
                ->where('matl_id', $materialId)
                ->orderBy('batch_code', 'asc')
                ->get();

            // Jika tidak ada record, buat default record dengan batch_code dari detail atau '1'
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

            // Alokasikan transferQty dari batch-batch secara FIFO
            foreach ($sourceBatches as $batch) {
                if ($transferQty <= 0) break;

                // Jika stok di batch ini 0 atau kurang, lewati ke batch selanjutnya
                if ($batch->qty_oh <= 0) {
                    continue;
                }

                // Tentukan jumlah yang akan dikurangkan dari batch ini
                $deduct = min($transferQty, $batch->qty_oh);

                // Update stok di batch sumber
                $batch->qty_oh -= $deduct;
                $batch->save();

                // --- Pencatatan transaksi pengurangan (sumber) ---
                // Cari detail transaksi sumber berdasarkan trhdr_id, wh_code, matl_id, dan batch_code
                $sourceDtl = IvttrDtl::where('trhdr_id', $this->objectIdValue)
                    ->where('wh_code', $this->inputs['wh_code'])
                    ->where('matl_id', $materialId)
                    ->where('batch_code', $batch->batch_code)
                    ->first();

                if ($sourceDtl) {
                    $sourceDtl->qty += (-$deduct);
                    $sourceDtl->save();
                } else {
                    // Jika belum ada, buat record baru dengan tr_seq negatif
                    IvttrDtl::create([
                        'trhdr_id'   => $this->objectIdValue,
                        'tr_seq'     => -$pair,
                        'wh_code'    => $this->inputs['wh_code'],
                        'matl_id'    => $materialId,
                        'tr_id'      => $this->objectIdValue,
                        'matl_code'  => $batch->matl_code,
                        'matl_uom'   => $batch->matl_uom,
                        'batch_code' => $batch->batch_code,
                        'ivt_id'     => $batch->id,
                        'qty'        => -$deduct,
                    ]);
                }

                // --- Proses transfer ke gudang tujuan (wh_code2) ---
                if (!empty($this->inputs['wh_code2'])) {
                    // Dapatkan wh_id untuk gudang tujuan
                    $destWarehouse = ConfigConst::where('str1', $this->inputs['wh_code2'])->first();
                    $dest_wh_id = $destWarehouse ? $destWarehouse->id : '';

                    // Cari record IvtBal di gudang tujuan dengan unique constraint: wh_code, matl_id, matl_uom, batch_code, dan wh_id
                    $destBatch = IvtBal::where('wh_code', $this->inputs['wh_code2'])
                        ->where('matl_id', $materialId)
                        ->where('matl_uom', $batch->matl_uom)
                        ->where('batch_code', $batch->batch_code)
                        ->where('wh_id', $dest_wh_id)
                        ->first();

                    if ($destBatch) {
                        // Jika record sudah ada, tambahkan stok
                        $destBatch->increment('qty_oh', $deduct);
                        $destBatch->refresh();
                    } else {
                        // Jika belum ada, buat record baru dengan qty_oh awal sama dengan $deduct
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

                    // Pencatatan transaksi penambahan (tujuan)
                    $destDtl = IvttrDtl::where('trhdr_id', $this->objectIdValue)
                        ->where('wh_code', $this->inputs['wh_code2'])
                        ->where('matl_id', $materialId)
                        ->where('batch_code', $destBatch->batch_code)
                        ->first();

                    if ($destDtl) {
                        $destDtl->qty += $deduct;
                        $destDtl->save();
                    } else {
                        IvttrDtl::create([
                            'trhdr_id'   => $this->objectIdValue,
                            'tr_seq'     => $pair,
                            'wh_code'    => $this->inputs['wh_code2'],
                            'matl_id'    => $materialId,
                            'tr_id'      => $this->objectIdValue,
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

            // Jika masih ada sisa transferQty, cari batch sumber berikutnya yang memiliki stok
            if ($transferQty > 0) {
                $remainingBatch = $sourceBatches->first(function ($batch) {
                    return $batch->qty_oh > 0;
                });

                if ($remainingBatch) {
                    $remainingBatch->qty_oh -= $transferQty;
                    $remainingBatch->save();

                    $sourceDtl = IvttrDtl::where('trhdr_id', $this->objectIdValue)
                        ->where('wh_code', $this->inputs['wh_code'])
                        ->where('matl_id', $materialId)
                        ->where('batch_code', $remainingBatch->batch_code)
                        ->first();

                    if ($sourceDtl) {
                        $sourceDtl->qty += (-$transferQty);
                        $sourceDtl->save();
                    } else {
                        IvttrDtl::create([
                            'trhdr_id'   => $this->objectIdValue,
                            'tr_seq'     => -$pair,
                            'wh_code'    => $this->inputs['wh_code'],
                            'matl_id'    => $materialId,
                            'tr_id'      => $this->objectIdValue,
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

                        $destDtl = IvttrDtl::where('trhdr_id', $this->objectIdValue)
                            ->where('wh_code', $this->inputs['wh_code2'])
                            ->where('matl_id', $materialId)
                            ->where('batch_code', $destBatch->batch_code)
                            ->first();

                        if ($destDtl) {
                            $destDtl->qty += $transferQty;
                            $destDtl->save();
                        } else {
                            IvttrDtl::create([
                                'trhdr_id'   => $this->objectIdValue,
                                'tr_seq'     => $pair,
                                'wh_code'    => $this->inputs['wh_code2'],
                                'matl_id'    => $materialId,
                                'tr_id'      => $this->objectIdValue,
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
                    $material = Material::find($materialId);
                    throw new Exception("Stok tidak mencukupi untuk material: " . ($material->code ?? $materialId));
                }
            }
        }
    }





    public function onWarehouseChanged($whCode)
    {
        $this->inputs['wh_code'] = $whCode;
        $materialIds = IvtBal::where('wh_code', $whCode)->pluck('matl_id')->toArray();
        $this->materials = Material::whereIn('id', $materialIds)->get()
            ->map(fn($m) => [
                'value' => $m->id,
                'label' => $m->code . " - " . $m->name,
            ]);
        if (!empty($this->input_details)) {
            $lastIndex = count($this->input_details) - 1;
            $this->input_details[$lastIndex]['matl_id'] = $this->materials->first()->value ?? null;
        }
    }

    public function toggleWarehouseDropdown($enabled)
    {
        $this->isEditWhCode2 = $enabled ? 'true' : 'false';
        // if (!$enabled) {
        //     $this->inputs['wh_code2'] = null;
        // }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'filteredMaterials' => $this->materials,
        ]);
    }
}
