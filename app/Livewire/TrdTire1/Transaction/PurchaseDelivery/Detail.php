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
    public $isPanelEnabled = "false";
    public $purchaseOrders = [];

    protected $rules = [
        'inputs.tr_date' => 'nullable',
        'inputs.tr_code' => 'required',
        'inputs.reffhdrtr_code' => 'required',
        'inputs.partner_id' => 'required',
        'inputs.send_to' => 'nullable',
        'inputs.tax_payer' => 'nullable',
        'inputs.payment_terms' => 'nullable',
        'inputs.tax' => 'nullable',
        'inputs.due_date' => 'nullable',
        'inputs.cust_reff' => 'nullable',
        'input_details.*.qty_order' => 'required',
        'input_details.*.price' => 'nullable',
        'input_details.*.matl_descr' => 'nullable',
        'input_details.*.matl_uom' => 'nullable',
        'input_details.*.qty' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'onPurchaseOrderSelected' => 'onPurchaseOrderChanged',
        'load-purchase-order-details' => 'loadPurchaseOrderDetails'
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
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->purchaseOrders = $this->masterService->getPurchaseOrders();
        $this->materials = $this->masterService->getMaterials();

        if ($this->isEditOrView()) {
            $this->object = DelivHdr::withTrashed()->find($this->objectIdValue);

            // Populate inputs array
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->inputs['tr_code'] = $this->object->tr_code;

            // Load reffhdrtr_code from DelivDtl if not set in DelivHdr
            if (empty($this->inputs['reffhdrtr_code'])) {
                $delivDtl = DelivDtl::where('trhdr_id', $this->object->id)->first();
                if ($delivDtl) {
                    $this->inputs['reffhdrtr_code'] = $delivDtl->reffhdrtr_code;
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
            if ($this->inputs['reffhdrtr_code']) {
                $this->loadPurchaseOrderDetails($this->inputs['reffhdrtr_code']);
            }
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new DelivHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
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
                $this->input_details[$key]['qty'] = $detail->qty; // Ensure qty is loaded
                $this->input_details[$key]['qty_available'] = $detail->qty; // Add qty_available
            }
        }
    }
    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            $this->input_details[] = [
                'matl_id' => null,
                'qty_order' => null,
                'price' => 0.0,
            ];
        } else {
            $this->dispatch('error', 'Tolong simpan Header terlebih dahulu');
        }
    }

    public function deleteItem($index)
    {
        try {
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);
            $this->dispatch('success', 'Item berhasil dihapus');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['price_uom'] = $material->selling_price;
                $this->input_details[$key]['matl_uom'] = $material->uom;
                $this->input_details[$key]['matl_descr'] = $material->name;
            }
        }
    }

    public function onPurchaseOrderChanged($value)
    {
        if ($value) {
            $order = OrderHdr::where('tr_code', $value)->first();

            if ($order) {
                $partner = Partner::find($order->partner_id);
                $this->inputs['partner_id'] = $partner->id;
                $this->inputs['partner_name'] = $partner->name;
            }

            $this->inputs['reffhdrtr_code'] = $value; // Set purchase order number to reffhdrtr_code
            $this->loadPurchaseOrderDetails($value);
        }
    }

    public function loadPurchaseOrderDetails($reffhdrtr_code)
    {
        $this->input_details = [];
        $orderDetails = OrderDtl::where('tr_code', $reffhdrtr_code)->get();

        foreach ($orderDetails as $detail) {
            $qty_remaining = $detail->qty - $detail->qty_reff;
            $this->input_details[] = [
                'matl_id' => $detail->matl_id,
                'qty_order' => $qty_remaining, // Set calculated quantity to qty_order
                'price' => $detail->price,
                'matl_descr' => $detail->matl_descr,
                'matl_uom' => $detail->matl_uom,
            ];
        }
    }
    #endregion

    #region CRUD Operations
    public function onValidateAndSave()
    {
        $this->validate();

        $errorItems = [];

        foreach ($this->input_details as $key => $detail) {
            // Check if qty exceeds the calculated qty_order
            if (isset($detail['qty']) && $detail['qty'] > $detail['qty_order']) {
                $errorItems[] = $detail['matl_descr'];
            }
        }
        // If there are error items, display the error message and stop the process
        if (!empty($errorItems)) {
            $this->dispatch('error', 'Stok untuk item: ' . implode(', ', $errorItems) . ' Sudah Dikirim');
            return;
        }
        try {
            // Save header
            if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
                $partner = Partner::find($this->inputs['partner_id']);
                $this->inputs['partner_code'] = $partner->code; // Save partner_code
            }
            // Ensure tr_type is set
            $this->inputs['tr_type'] = $this->trType;

            // Save warehouse data
            $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();
            if ($warehouse) {
                $this->inputs['wh_id'] = $warehouse->id; // Save wh_id
            }

            $this->object = DelivHdr::updateOrCreate(
                ['id' => $this->objectIdValue],
                $this->inputs
            );

            // Save details
            $existingDetails = DelivDtl::where('trhdr_id', $this->object->id)
                ->where('tr_type', $this->object->tr_type)
                ->get()
                ->keyBy('tr_seq');

            foreach ($this->input_details as $key => $detail) {
                $tr_seq = $key + 1;
                $orderDtl = OrderDtl::where('tr_code', $this->inputs['reffhdrtr_code'])
                    ->where('matl_id', $detail['matl_id'])
                    ->first();

                $material = Material::find($detail['matl_id']);

                DelivDtl::updateOrCreate([
                    'trhdr_id' => $this->object->id,
                    'tr_seq' => $tr_seq,
                ], [
                    'tr_code' => $this->object->tr_code,  // Using tr_code from DelivHdr
                    'trhdr_id' => $this->object->id,
                    'qty' => isset($existingDetails[$tr_seq]) ? $existingDetails[$tr_seq]->qty + $detail['qty'] : $detail['qty'],  // Add new qty to existing qty
                    'tr_type' => $this->trType,
                    'matl_id' => $detail['matl_id'],
                    'matl_code' => $material->code, // Save matl_code
                    'price' => $detail['price'],
                    'matl_descr' => $detail['matl_descr'],
                    'matl_uom' => $detail['matl_uom'],
                    'reffdtl_id' => $orderDtl->id ?? null,
                    'reffhdrtr_type' => $orderDtl->OrderHdr->tr_type ?? null,
                    'reffhdrtr_code' => $this->inputs['reffhdrtr_code'],  // Saving purchase order number
                    'reffdtltr_seq' => $orderDtl->tr_seq ?? null,
                    'wh_code' => $this->inputs['wh_code'],
                    'wh_id' => $this->inputs['wh_id'],
                ]);

                // Update OrderDtl qty_reff if not null
                if ($orderDtl && isset($detail['qty'])) {
                    $orderDtl->qty_reff += $detail['qty'];
                    $orderDtl->save();
                }

                // Update ivtBal
                // $existingBal = IvtBal::where('matl_id', $detail['matl_id'])
                //     ->where('wh_id', $this->inputs['wh_id']) // Use wh_id instead of wh_code
                //     ->where('batch_code', $detail['batch_code'] ?? date('y/m/d'))
                //     ->first();

                // $qtyChange = (float)$detail['qty'];
                // if ($this->trType === 'PD') {
                //     $qtyChange = -$qtyChange;
                // }

                // if ($existingBal) {
                //     $existingBalQty = $existingBal->qty_oh;
                //     $newQty = $existingBalQty + $qtyChange;
                //     $existingBal->qty_oh = $newQty;
                //     $existingBal->save();

                //     // Update corresponding record in IvtBalUnit
                //     $existingBalUnit = IvtBalUnit::where('matl_id', $detail['matl_id'])
                //         ->where('wh_id', $this->inputs['wh_id']) // Use wh_id instead of wh_code
                //         ->first();
                //     if ($existingBalUnit) {
                //         $existingBalUnitQty = $existingBalUnit->qty_oh;
                //         $existingBalUnit->qty_oh = $existingBalUnitQty + $qtyChange;
                //         $existingBalUnit->save();
                //     }
                // } else {
                //     $inventoryBalData = [
                //         'matl_id' => $detail['matl_id'],
                //         'matl_code' => $material->code,
                //         'matl_uom' => $material->uom,
                //         'matl_descr' => $material->name,
                //         'wh_id' => $this->inputs['wh_id'], // Use wh_id instead of wh_code
                //         'wh_code' => $this->inputs['wh_code'],
                //         'batch_code' => $detail['batch_code'] ?? date('y/m/d'),
                //         'qty_oh' => $qtyChange,
                //     ];
                //     $newIvtBal = IvtBal::create($inventoryBalData);
                //     $inventoryBalUnitsData = [
                //         'ivt_id' => $newIvtBal->id,
                //         'matl_id' => $detail['matl_id'],
                //         'wh_id' => $this->inputs['wh_id'], // Use wh_id instead of wh_code
                //         'matl_uom' => $material->uom,
                //         'unit_code' => $material->uom,
                //         'qty_oh' => $qtyChange,
                //     ];
                //     IvtBalUnit::create($inventoryBalUnitsData);
                // }
            }

            // Delete removed items
            $existingDetails->each(function ($item) use ($existingDetails) {
                if (!isset($this->input_details[$item->tr_seq - 1])) {
                    $item->delete();
                }
            });

            $this->dispatch('success', 'Data berhasil disimpan');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menyimpan data: ' . $e->getMessage());
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

            $this->dispatch('success', 'Data berhasil dihapus');
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
