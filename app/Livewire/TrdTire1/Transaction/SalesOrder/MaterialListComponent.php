<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use Exception;

class MaterialListComponent extends DetailComponent
{
    public $materials;
    protected $masterService;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_id;
    public $input_details = [];
    public $total_amount = 0;
    public $total_discount = 0;
    public $total_tax = 0; // New property for total tax

    protected $rules = [
        'input_details.*.qty' => 'nullable',
        'input_details.*.price' => 'nullable',
        'input_details.*.price_base' => 'nullable',
        'input_details.*.matl_descr' => 'nullable',
        'input_details.*.matl_uom' => 'nullable',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new OrderHdr();
        $this->object = new OrderDtl();
        $this->inputs = [];
        $this->input_details = [];
    }

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->materials = $this->masterService->getMaterials();

        if (!empty($this->objectIdValue)) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
        }
    }

    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                $this->input_details[] = [
                    'matl_id' => null,
                    'qty' => null,
                    'price' => 0.0,
                    'disc' => 0.0,
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong save Header terlebih dahulu']));
        }
    }

    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['price'] = $material->selling_price;
                $this->input_details[$key]['matl_uom'] = $material->uom;
                $this->input_details[$key]['matl_descr'] = $material->name;
                $this->updateAmount($key);
                $this->updateDiscount($key);
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
    }

    public function updateAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            $amount = $this->input_details[$key]['qty'] * $this->input_details[$key]['price'];
            $discountPercent = $this->input_details[$key]['disc'] ?? 0;
            $discountAmount = $amount * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amount - $discountAmount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }
        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);

        $this->calculateTotalAmount();
        $this->calculateTotalTax(); // Recalculate total tax when amount changes
    }

    public function updateDiscount($key)
    {
        $this->input_details[$key]['disc'] ?? 0;
        $this->calculateTotalDiscount();
        $this->calculateTotalAmount(); // Recalculate total amount when discount changes
    }

    public function calculateTotalDiscount()
    {
        // Calculate the total discount by summing all item discounts
        $this->total_discount = array_sum(array_column($this->input_details, 'disc'));
        $this->dispatch('updateDiscount', $this->total_discount);
    }

    public function calculateTotalAmount()
    {
        $this->total_amount = array_sum(array_map(function ($detail) {
            $qty = $detail['qty'] ?? 0;
            $price = $detail['price'] ?? 0;
            $discountPercent = $detail['disc'] ?? 0;
            $amount = $qty * $price;
            $discountAmount = $amount * ($discountPercent / 100);
            return $amount - $discountAmount;
        }, $this->input_details));

        $this->dispatch('updateAmount', $this->total_amount);
        $this->calculateTotalTax(); // Recalculate total tax when amount changes
    }

    public function calculateTotalTax()
    {
        // Ensure tax and total amount are numeric
        $taxRate = (float)($this->inputs['tax'] ?? 0);
        $totalAmount = (float)$this->total_amount;

        // Calculate total tax
        $this->total_tax = $totalAmount * ($taxRate / 100);
        $this->dispatch('updateTax', $this->total_tax);
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
            $this->calculateTotalDiscount();
            $this->calculateTotalAmount();
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
                $this->updateAmount($key);
            }
            $this->calculateTotalAmount();
            $this->calculateTotalDiscount();
            $this->calculateTotalTax(); // Calculate total tax when details are loaded
        }
    }

    public function SaveItem()
    {
        $this->Save();
    }

    public function onValidateAndSave()
    {
        $this->validate();
        try {
            // Fetch existing details from the database
            $existingDetails = OrderDtl::where('trhdr_id', $this->objectIdValue)
                ->where('tr_type', $this->object->trType)
                ->get()
                ->keyBy('tr_seq')
                ->toArray();

            // Determine which items to delete
            $itemsToDelete = array_diff_key($existingDetails, $this->input_details);
            foreach ($itemsToDelete as $tr_seq => $detail) {
                $orderDtl = OrderDtl::find($detail['id']);
                if ($orderDtl) {
                    $orderDtl->forceDelete();
                }
            }

            // Save or update new items
            foreach ($this->input_details as $key => $detail) {
                $tr_seq = $key + 1;
                $orderDtl = OrderDtl::firstOrNew([
                    'tr_id' => $this->object->tr_id,
                    'tr_seq' => $tr_seq,
                ]);

                $detail['tr_id'] = $this->object->tr_id;
                $detail['trhdr_id'] = $this->objectIdValue;
                $detail['qty_reff'] = $detail['qty'];
                $detail['tr_type'] = $this->object->tr_type;

                $orderDtl->fillAndSanitize($detail);
                $orderDtl->save();
            }
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.save_item', ['message' => $e->getMessage()]));
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
