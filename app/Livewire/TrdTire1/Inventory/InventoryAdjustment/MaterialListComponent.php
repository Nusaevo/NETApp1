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
    public $wh_code; // Add this property
    public $isEdit = "false";
    public $inputs = []; // Add this property

    protected $rules = [
        'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $wh_code = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
        $this->wh_code = $wh_code;
    }


    public function onReset()
    {
        $this->reset('input_details'); // Reset input_details instead of inputs
        $this->object = new IvttrHdr();
        $this->object = new IvttrDtl();
    }

    protected function onPreRender()
    {
        $this->isEdit = $this->isEditOrView() ? 'true' : 'false';
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->warehousesType = $this->masterService->getWarehouseType();
        $ivttrDtl = IvttrDtl::where('trhdr_id', $this->objectIdValue)->first();
        if ($ivttrDtl) {
            $materialIds = IvtBal::where('wh_code', $ivttrDtl->wh_code)->pluck('matl_id')->toArray();
            // dd($materialIds );
            $this->materials = Material::whereIn('id', $materialIds)->get()
                ->map(fn($m) => [
                    'value' => $m->id,
                    'label' => $m->code . " - " . $m->name,
                ]);
        } else {
            $this->materials = collect();
        }



        if (!empty($this->objectIdValue)) {
            $this->object = IvttrHdr::find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
        }
    }

    public function addItem()
    {
        // Cek apakah nilai wh_code sudah dipilih
        if (empty($this->inputs['wh_code'])) {
            $this->dispatch('error', 'Mohon pilih gudang terlebih dahulu.');
            return;
        }
        // Tambahkan item baru dan sertakan nilai wh_code
        $this->input_details[] = [
            'matl_id' => null,
            'qty'     => 0,
            'wh_code' => $this->inputs['wh_code']
        ];
        // dd($this->input_details);
    }

    public function onMaterialChanged($key, $matlId, $whCode)
    {
        // Contoh logika penyesuaian: cek apakah material yang dipilih sesuai dengan gudang
        $material = Material::find($matlId);
        if ($material && $material->warehouse_code != $whCode) {
            $this->dispatch('error', 'Material tidak sesuai dengan gudang yang dipilih.');
            return;
        }
        // Jika sesuai, simpan matl_id pada item yang bersangkutan
        $this->input_details[$key]['matl_id'] = $matlId;

        // Selanjutnya, Anda bisa memanggil fungsi untuk mengambil data ivtBall sesuai wh_code
        $this->getIvtBall($whCode);
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

        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->calculateTotalAmount();
        $this->calculateTotalDiscount();

        $this->dispatch('updateAmount', [
            'total_amount' => $this->total_amount,
            'total_discount' => $this->total_discount,
            'total_tax' => $this->total_tax,
            'total_dpp' => $this->total_dpp,
        ]);
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
            $this->recalculateTotals();
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
                $this->updateItemAmount($key); // Ensure each input item is initialized and updated
            }
        }
    }
    public function SaveItem()
    {
        $this->Save();
    }

    public function onValidateAndSave()
    {
        // Save or update new items
        foreach ($this->input_details as $key => $detail) {
            $tr_seq = $key + 1;

            // Save matl_id and matl_code to ivttrDtl
            $ivttrDtl = IvttrDtl::firstOrNew([
                'trhdr_id' => $this->objectIdValue,
                'tr_seq' => $tr_seq,
            ]);
            $ivttrDtl->matl_id = $detail['matl_id'];
            // $ivttrDtl->matl_code = $detail['matl_code'];
            $ivttrDtl->save();
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
