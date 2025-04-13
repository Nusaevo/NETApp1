<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderDtl;
use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Models\TrdTire1\Master\Material;
use Exception;

class MaterialListComponent extends BaseComponent
{
    public $input_details = [];
    public $materials = [];
    public $purchaseOrders = [];
    public $inputs = [];
    public $isPanelEnabled = true;

    protected $listeners = [
        'onPurchaseOrderChanged' => 'onPurchaseOrderChanged',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        // Ensure $inputs is initialized as an array
        $this->inputs = is_array($this->inputs) ? $this->inputs : [];

        $this->inputs['wh_code'] = $additionalParam['wh_code'] ?? null;
        $this->inputs['tr_type'] = $additionalParam['tr_type'] ?? null;
        $this->inputs['reffhdrtr_code'] = $additionalParam['reffhdrtr_code'] ?? ''; // Initialize reffhdrtr_code
        $this->materials = Material::all();
        $this->purchaseOrders = OrderDtl::distinct()->pluck('tr_code');
    }

    public function onPurchaseOrderChanged($value)
    {
        $this->input_details = [];
        $this->inputs['reffhdrtr_code'] = $value;
        if ($value) {
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
                'qty_order' => $qty_remaining,
                'matl_descr' => $detail->matl_descr,
                'matl_uom' => $detail->matl_uom,
                'order_id' => $detail->id,
            ];
        }
    }

    public function deleteItem($index)
    {
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);

        if (empty($this->input_details)) {
            $this->isPanelEnabled = true;
        }
    }

    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                $this->input_details[] = [
                    'matl_id' => null,
                    'qty' => null,
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong save Header terlebih dahulu']));
        }
    }

    public function render()
    {
        return view('livewire.trd-tire1.transaction.purchase-delivery.material-component');
    }
}
