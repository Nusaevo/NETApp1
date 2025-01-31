<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl};
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

    protected $rules = [
        'input_details.*.qty' => 'required|numeric|min:1', // Ensure quantity is required, numeric, and at least 1
        'input_details.*.price' => 'nullable', // Ensure unit price is required and numeric
        'input_details.*.matl_descr' => 'nullable', // Description is optional but must be a string with a max length
        'input_details.*.matl_uom' => 'nullable', // Ensure UOM is required and a string
    ];

    protected $listeners = [
        'populateMaterialList' => 'onPurchaseOrderSelected',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new DelivHdr();
        $this->object = new DelivDtl();
        $this->inputs = [];
        $this->input_details = [];

    }

    protected function onPreRender()
    {

        $this->customValidationAttributes = [
            'input_details.*' => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('matl_id'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.price' => $this->trans('price'),
        ];
        $this->masterService = new MasterService();
        $this->materials = $this->masterService->getMaterials();

        if (!empty($this->objectIdValue)) {
            $this->object = DelivHdr::withTrashed()->find($this->objectIdValue);
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
                // Update harga satuan, deskripsi, dan UOM
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['price_uom'] = $material->selling_price;
                $this->input_details[$key]['matl_uom'] = $material->uom;
                $this->input_details[$key]['matl_descr'] = $material->name;
                $this->updateAmount($key);

                // Remove automatic calculation of amount
                // $this->calculateAmount($key);
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
    }

    public function calculateAmount($key)
    {
        $qty = $this->input_details[$key]['qty'] ?? 0;
        $price = $this->input_details[$key]['price'] ?? 0;
        $amount = $qty * $price;
        $this->input_details[$key]['price_base'] = $amount;
    }

    // Fungsi untuk menangani perubahan qty
    public function updatedInputDetails($value, $field)
    {
        if (str_contains($field, 'qty')) {
            $key = str_replace(['input_details.', '.qty'], '', $field);
            $this->calculateAmount($key);
        }
    }

    public function updated($propertyName)
    {
        if (str_contains($propertyName, 'input_details.')) {
            $parts = explode('.', $propertyName);
            $key = $parts[1];
            $field = $parts[2];

            if ($field === 'qty') {
                $this->calculateAmount($key);
            }
        }
    }

    public function updateAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            $this->input_details[$key]['amt'] =
                $this->input_details[$key]['qty'] * $this->input_details[$key]['price'];
        }
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
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function validateItems()
    {
        if (empty($this->input_details)) {
            $this->dispatch('error', __('generic.error.empty_item'));
            return false;
        }

        foreach ($this->input_details as $key => $item) {
            if (empty($item['matl_id']) || $item['qty'] <= 0 || $item['price'] <= 0) {
                $this->dispatch('error', __('generic.error.field_required', ['field' => "Item #$key"]));
                return false;
            }
        }

        return true;
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = DelivDtl::GetByDelivHdr($this->object->id, $this->object->tr_type)->orderBy('tr_seq')->get();

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] =  populateArrayFromModel($detail);
                // $this->input_details[$key]['matl_descr'] = $detail->Material->name;
                // $this->input_details[$key]['price'] = $detail->Material->selling_price;

            }
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
            $existingDetails = DelivDtl::where('trhdr_id', $this->objectIdValue)
                ->where('tr_type', $this->object->trType)
                ->get()
                ->keyBy('tr_seq')
                ->toArray();

            // Determine which items to delete
            $itemsToDelete = array_diff_key($existingDetails, $this->input_details);
            foreach ($itemsToDelete as $tr_seq => $detail) {
                $delivDtl = DelivDtl::find($detail['id']);
                if ($delivDtl) {
                    $delivDtl->forceDelete();
                }
            }

            // Save or update new items
            foreach ($this->input_details as $key => $detail) {
                $tr_seq = $key + 1;
                $delivDtl = DelivDtl::firstOrNew([
                    'tr_id' => $this->object->tr_id,
                    'tr_seq' => $tr_seq,
                ]);

                $detail['tr_id'] = $this->object->tr_id;
                $detail['trhdr_id'] = $this->objectIdValue;
                $detail['qty_reff'] = $detail['qty'];
                $detail['tr_type'] = $this->object->tr_type;

                $delivDtl->fill($detail);
                $delivDtl->save();
            }
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.save_item', ['message' => $e->getMessage()]));
        }
    }

    private function isDuplicateTrSeq($trhdr_id, $tr_seq)
    {
        return DelivDtl::where('trhdr_id', $trhdr_id)->where('tr_seq', $tr_seq)->exists();
    }

    private function getUniqueTrSeq($trhdr_id)
    {
        $maxTrSeq = DelivDtl::where('trhdr_id', $trhdr_id)->max('tr_seq');
        return $maxTrSeq ? $maxTrSeq + 1 : 1;
    }

    public function onPurchaseOrderSelected($tr_id)
    {
        $orderDetails = OrderDtl::where('tr_id', $tr_id)->get();

        foreach ($orderDetails as $detail) {
            $this->input_details[] = [
                'matl_id' => $detail->matl_id,
                'qty' => $detail->qty,
                'price' => $detail->price,
                'matl_descr' => $detail->matl_descr,
                'matl_uom' => $detail->matl_uom,
            ];
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
