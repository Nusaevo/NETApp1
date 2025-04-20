<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseOrder;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Master\MatlUom;
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
    public $tr_code;
    public $input_details = [];
    public $total_amount = 0;
    public $total_discount = 0;
    public $total_tax = 0; // New property for total tax
    public $total_dpp = 0; // New property for total tax
    public $deleted_items = []; // Tambahkan properti untuk melacak item yang dihapus

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
        $this->reset('input_details'); // Reset input_details instead of inputs
        $this->object = new OrderHdr();
        $this->object = new OrderDtl();
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*.matl_id'    => $this->trans('code'),
            'input_details.*.qty'        => $this->trans('qty'),
        ];
        $this->masterService = new MasterService();
        $this->materials = $this->masterService->getMaterials();

        if (!empty($this->objectIdValue)) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
        }

        // Recalculate totals when the component is rendered
        $this->recalculateTotals();
    }

    public function addItem()
    {
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
    }

    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                $matlUom = MatlUom::where('matl_id', $matl_id)->first(); // Fetch MatlUom using matl_id
                if ($matlUom) {
                    $this->input_details[$key]['matl_id'] = $material->id;
                    $this->input_details[$key]['price'] = $matlUom->selling_price; // Use selling_price from MatlUom
                    $this->input_details[$key]['matl_uom'] = $material->uom;
                    $this->input_details[$key]['matl_descr'] = $material->name;
                    $this->updateItemAmount($key);
                } else {
                    $this->dispatch('error', __('generic.error.material_uom_not_found'));
                }
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
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

    private function calculateTotalAmount()
    {
        $this->total_amount = array_sum(array_map(function ($detail) {
            $qty = $detail['qty'] ?? 0;
            $price = $detail['price'] ?? 0;
            $discountPercent = $detail['disc_pct'] ?? 0;
            $amount = $qty * $price;
            $discountAmount = $amount * ($discountPercent / 100);
            return $amount - $discountAmount;
        }, $this->input_details));

        $this->total_amount = round($this->total_amount, 2);
    }

    private function calculateTotalDiscount()
    {
        $this->total_discount = array_sum(array_map(function ($detail) {
            $qty = $detail['qty'] ?? 0;
            $price = $detail['price'] ?? 0;
            $discountPercent = $detail['disc_pct'] ?? 0;
            $amount = $qty * $price;
            $discountAmount = $amount * ($discountPercent / 100);
            return $discountAmount;
        }, $this->input_details));

        $this->total_discount = round($this->total_discount, 2);
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            // Add the item to the deleted_items list
            if (isset($this->input_details[$index]['id'])) {
                $this->deleted_items[] = $this->input_details[$index]['id'];
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

        // Restore qty_fgr for deleted items
        if (!empty($this->deleted_items)) {
            foreach ($this->deleted_items as $deletedItemId) {
                $orderDtl = OrderDtl::find($deletedItemId);
                if ($orderDtl) {
                    $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)
                        ->where('matl_uom', $orderDtl->matl_uom)
                        ->first();
                    if ($matlUom) {
                        $matlUom->qty_fgr -= $orderDtl->qty;
                        $matlUom->save();
                    }
                    $orderDtl->forceDelete(); // Permanently delete the item
                }
            }
            $this->deleted_items = []; // Reset the deleted_items list after processing
        }

        $orderHdr = OrderHdr::find($this->objectIdValue);
        if ($orderHdr) {
            $orderHdr->total_amt = $this->total_amount;

            // Calculate total_amt_tax
            $taxPct = $orderHdr->tax_pct / 100;
            if ($orderHdr->tax_flag === 'I') {
                $orderHdr->total_amt_tax = $this->total_amount;
            } elseif ($orderHdr->tax_flag === 'E') {
                $orderHdr->total_amt_tax = $this->total_amount * (1 + $taxPct);
            } else {
                $orderHdr->total_amt_tax = $this->total_amount;
            }

            $orderHdr->save();
        }
    }

    public function onValidateAndSave()
    {
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
                'tr_code' => $this->object->tr_code,
                'tr_seq' => $tr_seq,
            ]);

            $detail['tr_code'] = $this->object->tr_code;
            $detail['trhdr_id'] = $this->objectIdValue;
            $detail['qty_reff'] = '0';
            $detail['tr_type'] = $this->object->tr_type;

            // Fetch matl_code from matl_id
            $material = Material::find($detail['matl_id']);
            if ($material) {
                $detail['matl_code'] = $material->code;
            }

            $orderDtl->fill($detail);
            $orderDtl->save();
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
