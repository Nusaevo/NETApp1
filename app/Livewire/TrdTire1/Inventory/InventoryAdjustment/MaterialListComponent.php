<?php

namespace App\Livewire\TrdTire1\Inventory\InventoryAdjustment;

use App\Livewire\Component\DetailComponent;
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
        $this->isEdit = $this->isEditOrView() ? 'true' : 'false';
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->warehousesType = $this->masterService->getWarehouseType();

        if (!empty($this->objectIdValue)) {
            $this->object = IvttrHdr::find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);

            if (isset($this->inputs['tr_type']) && $this->inputs['tr_type'] === 'TW') {
                $this->isEditWhCode2 = 'true';
            } else {
                $this->isEditWhCode2 = 'false';
            }
            $this->loadDetails();
            if (!empty($this->inputs['wh_code'])) {
                $materialIds = IvtBal::where('wh_code', $this->inputs['wh_code'])->pluck('matl_id')->toArray();
                $this->materials = Material::whereIn('id', $materialIds)->get()
                    ->map(fn($m) => [
                        'value' => $m->id,
                        'label' => $m->code . " - " . $m->name,
                    ]);
            }
        }
    }

    public function addItem()
    {
        if (empty($this->inputs['wh_code'])) {
            $this->dispatch('error', 'Mohon pilih gudang terlebih dahulu.');
            return;
        }
        $this->input_details[] = [
            'matl_id' => null,
            'qty'     => 0,
            'wh_code' => $this->inputs['wh_code']
        ];
        $this->onWarehouseChanged($this->inputs['wh_code']);
    }

    protected function getIvtBall($whCode)
    {
        // Contoh query untuk mengambil data dari ivtBall berdasarkan wh_code
        $data = DB::table('ivtBall')->where('wh_code', $whCode)->get();
        // Lakukan sesuatu dengan data yang didapatkan, misalnya menyimpannya ke property atau dispatch event
        // $this->ivtBallData = $data;
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

            // Jika item sudah ada di DB (biasanya ditandai dengan 'id')
            if (!empty($detail['id'])) {
                // Cari data IvttrDtl dengan id tersebut
                $pos = IvttrDtl::find($detail['id']);

                if ($pos) {
                    // Hapus kedua record: tr_seq positif (misalnya 1) dan negatif (misalnya -1)
                    IvttrDtl::where('trhdr_id', $pos->trhdr_id)
                        ->whereIn('tr_seq', [$pos->tr_seq, -$pos->tr_seq])
                        ->delete();
                }
            }

            // Hapus juga dari array agar tidak muncul lagi di tampilan
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
            // Ambil detail transaksi dengan tr_seq positif (nilai +) saja
            $this->object_detail = IvttrDtl::where('trhdr_id', $this->object->id)
                ->where('tr_seq', '>', 0)
                ->orderBy('tr_seq')
                ->get();

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
            }

            // Jika ada transaksi dengan tr_seq negatif, ambil nilai wh_code-nya sebagai wh_code2
            $negativeDetail = IvttrDtl::where('trhdr_id', $this->object->id)
                ->where('tr_seq', '<', 0)
                ->first();
            if ($negativeDetail) {
                $this->inputs['wh_code2'] = $negativeDetail->wh_code;
            }

            // Ambil wh_code dari transaksi positif (diasumsikan sama untuk seluruh detail)
            if (count($this->object_detail) > 0) {
                $this->inputs['wh_code'] = $this->object_detail->first()->wh_code;
            }
        }
    }


    public function SaveItem()
    {
        $this->Save();
    }

    public function onValidateAndSave()
    {
        // Ambil data IvtBal untuk gudang utama (wh_code)
        $ivtBals = IvtBal::whereIn('matl_id', array_column($this->input_details, 'matl_id'))
            ->where('wh_code', $this->inputs['wh_code'])
            ->get()
            ->keyBy('matl_id');

        // Jika wh_code2 ada, ambil data IvtBal untuk gudang tujuan
        if (!empty($this->inputs['wh_code2'])) {
            $ivtBals2 = IvtBal::whereIn('matl_id', array_column($this->input_details, 'matl_id'))
                ->where('wh_code', $this->inputs['wh_code2'])
                ->get()
                ->keyBy('matl_id');
        }

        foreach ($this->input_details as $key => $detail) {
            $tr_seq = $key + 1;

            if (!isset($ivtBals[$detail['matl_id']])) {
                throw new Exception("Material dengan ID {$detail['matl_id']} tidak ditemukan di gudang {$this->inputs['wh_code']}.");
            }
            $mainBalance = $ivtBals[$detail['matl_id']];

            // Transaksi pertama (positif)
            IvttrDtl::updateOrCreate(
                [
                    'trhdr_id' => $this->objectIdValue,
                    'tr_seq'   => $tr_seq,
                ],
                [
                    'wh_code'    => $this->inputs['wh_code'],
                    'matl_id'    => $detail['matl_id'],
                    'tr_id'      => $this->objectIdValue,
                    'matl_code'  => $mainBalance->matl_code,
                    'matl_uom'   => $mainBalance->matl_uom,
                    'batch_code' => $mainBalance->batch_code,
                    'ivt_id'     => $mainBalance->id,
                    'qty'        => $detail['qty'],
                ]
            );

            // Jika ada wh_code2, simpan transaksi kedua (negatif)
            if (!empty($this->inputs['wh_code2'])) {
                if (!isset($ivtBals2[$detail['matl_id']])) {
                    throw new Exception("Material dengan ID {$detail['matl_id']} tidak ditemukan di gudang {$this->inputs['wh_code2']}.");
                }
                $destBalance = $ivtBals2[$detail['matl_id']];
                $tr_seq2 = - ($key + 1);
                IvttrDtl::updateOrCreate(
                    [
                        'trhdr_id' => $this->objectIdValue,
                        'tr_seq'   => $tr_seq2,
                    ],
                    [
                        'wh_code'    => $this->inputs['wh_code2'],
                        'matl_id'    => $detail['matl_id'],
                        'tr_id'      => $this->objectIdValue,
                        'matl_code'  => $destBalance->matl_code,
                        'matl_uom'   => $destBalance->matl_uom,
                        'batch_code' => $destBalance->batch_code,
                        'ivt_id'     => $destBalance->id,
                        'qty'        => -$detail['qty'],
                    ]
                );
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
        // Automatically set matl_id for the last added item
        if (!empty($this->input_details)) {
            $lastIndex = count($this->input_details) - 1;
            $this->input_details[$lastIndex]['matl_id'] = $this->materials->first()->value ?? null;
        }
    }

    public function toggleWarehouseDropdown($enabled)
    {
        $this->isEditWhCode2 = $enabled ? 'true' : 'false';
        if (!$enabled) {
            $this->inputs['wh_code2'] = null;
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'filteredMaterials' => $this->materials
        ]);
    }
}
