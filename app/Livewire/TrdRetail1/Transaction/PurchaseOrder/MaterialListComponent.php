<?php

namespace App\Livewire\TrdRetail1\Transaction\PurchaseOrder;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdRetail1\Master\Material;
use App\Services\TrdRetail1\Master\MasterService;
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl};
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
    public $materialList = [];
    public $searchTerm = '';
    public $selectedMaterials = [];

    protected $rules = [
        'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
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
            'input_details.*.matl_id' => $this->trans('code'),
            'input_details.*.qty' => $this->trans('qty'),
        ];
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
                    'price' => 0.0
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
            $duplicate = collect($this->input_details)->contains(function ($detail, $index) use ($key, $matl_id) {
                return $index != $key && isset($detail['matl_id']) && $detail['matl_id'] == $matl_id;
            });

            if ($duplicate) {
                $this->dispatch('error', 'Material sudah ada dalam daftar.');
                return;
            }

            $material = Material::find($matl_id);
            if ($material) {
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['matl_code'] = $material->code;
                $this->input_details[$key]['price'] = $material->selling_price;
                $this->input_details[$key]['matl_uom'] = $material->MatlUom[0]->id;
                $this->input_details[$key]['matl_descr'] = $material->name;
                $attachment = optional($material->Attachment)->first();
                $this->input_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                $this->updateItemAmount($key);
            } else {
                $this->dispatch('error', 'Material_not_found');
            }
        }
    }

    public function updateItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            $amount = $this->input_details[$key]['qty'] * $this->input_details[$key]['price'];
            $this->input_details[$key]['amt'] = $amount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }

        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);

        // Update totals immediately
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->total_amount = array_sum(
            array_map(function ($detail) {
                $qty = $detail['qty'] ?? 0;
                $price = $detail['price'] ?? 0;
                $amount = $qty * $price;
                return $amount;
            }, $this->input_details),
        );

        $this->total_amount = round($this->total_amount, 2);
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
                $this->updateItemAmount($key);
            }
        }
    }
    public function SaveItem()
    {
        $this->Save();
    }

    public function onValidateAndSave()
    {
        // 1) Validate the input details
        $this->validate();

        // 2) Retrieve existing details from the database
        $existingDetails = OrderDtl::where('trhdr_id', $this->objectIdValue)
            ->where('tr_type', $this->object->tr_type)
            ->get()
            ->keyBy('tr_seq')
            ->toArray();

        // 3) Determine which items to delete (items in DB but not in $this->input_details)
        $itemsToDelete = array_diff_key($existingDetails, $this->input_details);
        foreach ($itemsToDelete as $tr_seq => $detail) {
            $orderDtl = OrderDtl::find($detail['id']);
            if ($orderDtl) {
                $orderDtl->forceDelete();
            }
        }

        // 4) Build the array of items to save.
        //    We must assign tr_seq = $index + 1 so they're saved in the correct sequence.
        $itemsToSave = [];
        foreach ($this->input_details as $index => $detail) {
            $detail['tr_seq'] = $index + 1;
            $detail['tr_id'] = $this->object->tr_id;
            $detail['wh_id'] = 18;
            $detail['wh_code'] = 18;
            $itemsToSave[]    = $detail;
        }
        // 5) Save or update items.
        //    Pass `true` for $createBillingDelivery if you want DelivDtl & BillingDtl created right away.
        $this->object->saveOrderDetails(
            $this->object->tr_type,
            $itemsToSave,
            true  // or false if you do NOT want to create DelivDtl & BillingDtl now
        );
    }


    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function openItemDialogBox()
    {
        $this->searchTerm = '';
        $this->materialList = [];
        $this->selectedMaterials = [];
        $this->dispatch('openItemDialogBox');
    }
    public function searchMaterials()
    {
        $query = Material::query();
        if (!empty($this->searchTerm)) {
            $searchTermUpper = strtoupper($this->searchTerm);
            $query->where(function ($query) use ($searchTermUpper) {
                $query
                    ->whereRaw('UPPER(materials.code) LIKE ?', ['%' . $searchTermUpper . '%'])
                    ->orWhereRaw('UPPER(materials.name) LIKE ?', ['%' . $searchTermUpper . '%'])
                    ->orWhereRaw('UPPER(materials.descr) LIKE ?', ['%' . $searchTermUpper . '%']);
            });
        }

        $this->materialList = $query->get();
    }
    public function selectMaterial($materialID)
    {
        $key = array_search($materialID, $this->selectedMaterials);

        if ($key !== false) {
            unset($this->selectedMaterials[$key]);
            $this->selectedMaterials = array_values($this->selectedMaterials);
        } else {
            $this->selectedMaterials[] = $materialID;
        }
    }

    public function confirmSelection()
    {
        if (empty($this->selectedMaterials)) {
            $this->dispatch('error', 'Silakan pilih setidaknya satu material terlebih dahulu.');
            return;
        }

        foreach ($this->selectedMaterials as $matl_id) {
            $exists = collect($this->input_details)->contains('matl_id', $matl_id);

            if ($exists) {
                $this->dispatch('error', "Material dengan ID $matl_id sudah ada dalam daftar.");
                continue;
            }

            // Jika tidak duplikat, tambahkan ke daftar
            $key = count($this->input_details);
            $this->input_details[] = [
                'matl_id' => $matl_id,
                'qty' => null,
                'price' => 0.0
            ];
            $this->onMaterialChanged($key, $matl_id);
        }

        $this->dispatch('success', 'Item berhasil dipilih.');
        $this->dispatch('closeItemDialogBox');
    }
}
