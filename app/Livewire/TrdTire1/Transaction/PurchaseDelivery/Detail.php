<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Exception;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Inventories\IvtBalUnit;
use Illuminate\Support\Facades\DB;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $input_details = [];
    public $suppliers;
    public $warehouses;
    public $partners;
    public $vehicle_type;
    public $tax_invoice;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $materials;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $reffhdrtr_code;

    public $total_amount = 0;
    public $trType = "PD";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];

    protected $masterService;
    public $isPanelEnabled = true;
    public $purchaseOrders = [];

    protected $rules = [
        'inputs.tr_code' => 'required',
        'inputs.wh_code' => 'required',
        'inputs.partner_id' => 'required',
        'input_details.*.qty' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'load-purchase-order-details' => 'loadPurchaseOrderDetails',
        'onPurchaseOrderChanged' => 'onPurchaseOrderChanged' // Add this listener
    ];
    #endregion

    #region Component Lifecycle Methods
    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'inputs.tax' => $this->trans('tax'),
            'input_details.*' => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('matl_id'),
            'input_details.*.qty_order' => $this->trans('qty_order'),
            'input_details.*.price' => $this->trans('price'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->purchaseOrders = $this->masterService->getPurchaseOrders();
        $this->materials = $this->masterService->getMaterials();

        if ($this->isEditOrView()) {
            $this->object = DelivHdr::withTrashed()->find($this->objectIdValue);
            $this->isPanelEnabled = "false";
            // Populate inputs array
            $this->inputs = populateArrayFromModel($this->object);
            // $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->inputs['tr_code'] = $this->object->tr_code;

            // Load reffhdrtr_code from DelivDtl if not set in DelivHdr
            if (empty($this->inputs['reffhdrtr_code'])) {
                $delivDtl = DelivDtl::where('trhdr_id', $this->object->id)->first();
                if ($delivDtl) {
                    $this->inputs['reffhdrtr_code'] = $delivDtl->reffhdrtr_code;
                    $this->inputs['qty'] = $delivDtl->qty;
                }
            }

            // Load partner data
            $partner = Partner::find($this->object->partner_id);
            if ($partner) {
                $this->inputs['partner_id'] = $partner->id;
                $this->inputs['partner_name'] = $partner->name;
            }

            // Load details and purchase order details
            $this->loadDetails();
            // if ($this->inputs['reffhdrtr_code']) {
            //     $this->loadPurchaseOrderDetails($this->inputs['reffhdrtr_code']);
            // }
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new DelivHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['reff_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['send_to'] = "Pelanggan";
        $this->inputs['wh_code'] = 18;
        $this->inputs['reffhdrtr_code'] = ''; // Inisialisasi key reffhdrtr_code
    }

    #endregion

    #region Material List Methods
    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = DelivDtl::GetByDelivHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['order_id'] = $detail->OrderDtl->id;
                $this->input_details[$key]['qty'] = $detail->qty;
                $this->input_details[$key]['qty_order'] = ($detail->OrderDtl->qty - $detail->OrderDtl->qty_reff) + $detail->qty; // Adjust qty_order
                // dd($this->input_details[$key]);
            }
        }
    }

    public function deleteItem($index)
    {
        try {
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            // Jika tidak ada item lagi di input_details, enable kolom reffhdrtr_code dan wh_code
            if (empty($this->input_details)) {
                $this->isPanelEnabled = true; // Enable warehouse and reffhdrtr_code fields
                $this->inputs['reffhdrtr_code'] = null; // Set reffhdrtr_code to null
            }
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    public function onPurchaseOrderChanged($value)
    {
        $this->input_details = []; // Clear existing items
        $this->inputs['reffhdrtr_code'] = $value; // Update the purchase order code

        if ($value) {
            $this->loadPurchaseOrderDetails($value); // Reload details for the new purchase order

            // Load supplier data based on the selected purchase order
            $orderHeader = OrderHdr::where('tr_code', $value)->first();
            if ($orderHeader && $orderHeader->partner) {
                $this->inputs['partner_id'] = $orderHeader->partner->id;
                $this->inputs['partner_name'] = $orderHeader->partner->name;
            } else {
                $this->inputs['partner_id'] = null;
                $this->inputs['partner_name'] = null;
            }
        }
    }

    public function loadPurchaseOrderDetails($reffhdrtr_code)
    {
        $this->input_details = []; // Ensure input_details is cleared
        $orderDetails = OrderDtl::where('tr_code', $reffhdrtr_code)->get();

        foreach ($orderDetails as $detail) {
            $qty_remaining = $detail->qty - $detail->qty_reff;
            $this->input_details[] = [
                'matl_id' => $detail->matl_id,
                'qty_order' => $qty_remaining,
                'matl_descr' => $detail->matl_descr,
                'matl_uom' => $detail->matl_uom,
                'order_id' => $detail->id,
            ];
        }
    }
    #endregion

    #region CRUD Operations
    public function onValidateAndSave()
    {
        // Validasi header
        if (empty($this->inputs['tr_code']) && empty($this->inputs['reffhdrtr_code']) && empty($this->inputs['partner_id'])) {
            $this->dispatch('error', 'Semua field header wajib diisi');
            return;
        }

        // Update data partner jika ada
        if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner->code;
        }
        $this->inputs['tr_type'] = $this->trType;

        // Update info warehouse
        $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();
        if ($warehouse) {
            $this->inputs['wh_id'] = $warehouse->id;
        }

        if ($this->object->isNew()) {
            $this->object->status_code = Status::OPEN;
        }


        $this->object->fill($this->inputs);
        $this->object->save();

        // Validasi detail
        $errorItems = [];
        foreach ($this->input_details as $key => $detail) {
            if (isset($detail['qty']) && $detail['qty'] > $detail['qty_order']) {
                $errorItems[] = $detail['matl_descr'];
            }
        }
        if (!empty($errorItems)) {
            $this->dispatch('error', 'Stok untuk item: ' . implode(', ', $errorItems) . ' sudah dikirim');
            return;
        }

        $existingDetails = DelivDtl::where('trhdr_id', $this->object->id)
            ->where('tr_type', $this->object->tr_type)
            ->get()
            ->keyBy('tr_seq');

        foreach ($this->input_details as $key => $detail) {
            $tr_seq = $key + 1;
            $orderDtl = OrderDtl::find($detail['order_id']);
            $material = Material::find($detail['matl_id']);

            $newQty = $detail['qty'];
            // Perhitungan delta (digunakan di model)
            $oldQty = isset($existingDetails[$tr_seq]) ? $existingDetails[$tr_seq]->qty : 0;

            $detailRecord = DelivDtl::firstOrNew([
                'trhdr_id' => $this->object->id, // Ensure trhdr_id is set correctly
                'tr_seq'   => $tr_seq,
            ]);

            $detailRecord->fill([
                'tr_code'         => $this->object->tr_code,
                'qty'             => $newQty,
                'tr_type'         => $this->trType,
                'matl_id'         => $detail['matl_id'],
                'matl_code'       => $material->code,
                'matl_descr'      => $detail['matl_descr'],
                'matl_uom'        => $detail['matl_uom'],
                'reffdtl_id'      => $orderDtl->id ?? null,
                'reffhdrtr_type'  => $orderDtl ? $orderDtl->OrderHdr->tr_type : null,
                'reffhdrtr_code'  => $this->inputs['reffhdrtr_code'],
                'reffdtltr_seq'   => $orderDtl->tr_seq ?? null,
                'wh_code'         => $this->inputs['wh_code'],
                'wh_id'           => $this->inputs['wh_id'],
            ]);
            $detailRecord->save();
        }
        $existingDetails->each(function ($item) {
            if (!isset($this->input_details[$item->tr_seq - 1])) {
                $item->forceDelete(); // Force delete the DelivDtl record
            }
        });

    }

    public function addItem()
    {
        if (empty($this->inputs['reffhdrtr_code'])) {
            $this->dispatch('error', 'Mohon pilih nota pembelian terlebih dahulu.');
            return;
        }

        // $this->isPanelEnabled = false;

        $this->input_details[] = [
            'matl_id' => null,
            'qty_order' => null,
            'matl_descr' => null,
            'matl_uom' => null,
            'order_id' => null,
            'qty' => null,
        ];
    }

    public function onMaterialChanged($index, $matl_id)
    {
        if (empty($this->inputs['reffhdrtr_code'])) {
            $this->dispatch('error', 'Mohon pilih nota pembelian terlebih dahulu.');
            return;
        }

        $orderDetail = OrderDtl::where('tr_code', $this->inputs['reffhdrtr_code'])
            ->where('matl_id', $matl_id)
            ->first();

        if ($orderDetail) {
            $qty_remaining = $orderDetail->qty - $orderDetail->qty_reff;

            $this->input_details[$index] = array_merge($this->input_details[$index], [
                'matl_id' => $orderDetail->matl_id,
                'qty_order' => $qty_remaining,
                'matl_descr' => $orderDetail->matl_descr,
                'matl_uom' => $orderDetail->matl_uom,
                'order_id' => $orderDetail->id,
            ]);
        } else {
            $this->dispatch('error', 'Material tidak ditemukan pada nota pembelian.');
        }
    }

    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota tidak bisa dihapus karena status Completed');
                return;
            }

            $this->object->status_code = Status::NONACTIVE;
            $this->object->save();
            $this->object->delete();

            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    #endregion

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
