<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use Exception;


class MaterialListComponent extends BaseComponent
{
    public $materials;
    protected $masterService;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_id;
    public $trType = "SO";
    public $input_details = [];

    protected $rules = [
        'input_details.*.qty' => 'nullable', // Ensure quantity is required, numeric, and at least 1
        'input_details.*.price_uom' => 'nullable', // Ensure unit price is required and numeric
        'input_details.*.price_base' => 'nullable', // Ensure unit price is required and numeric
        'input_details.*.matl_desc' => 'nullable', // Description is optional but must be a string with a max length
        'input_details.*.matl_uom' => 'nullable', // Ensure UOM is required and a string
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
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['matl_id'] = 0;
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
                    'trhdr_id' => 0,
                    'tr_seq' => 0,
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
            // dd($this->input_details);
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
                $this->input_details[$key]['matl_desc'] = $material->name;

                // Perhitungan amount berdasarkan qty dan price_uom
                $this->calculateAmount($key);
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
    }
    // Fungsi untuk menghitung amount berdasarkan qty dan price_uom
    public function calculateAmount($key)
    {
        $qty = $this->input_details[$key]['qty'] ?? 0;
        $price_uom = $this->input_details[$key]['price_uom'] ?? 0;

        // Menghitung amount (qty * price_uom)
        $amount = $qty * $price_uom;

        // Menyimpan nilai amount ke dalam input_details
        $this->input_details[$key]['price_base'] = $amount;
    }
    // Fungsi untuk menangani perubahan qty
    public function updatedInputDetails($value, $field)
    {
        // Memastikan kita sedang mengupdate qty
        if (str_contains($field, 'qty')) {
            // Menemukan key berdasarkan nama field
            $key = str_replace(['input_details.', '.qty'], '', $field);
            // Menghitung ulang amount setelah qty diubah
            $this->calculateAmount($key);
        }
    }
    public function updated($propertyName)
    {
        if (str_contains($propertyName, 'input_details.')) {
            $key = explode('.', $propertyName)[1]; // Ambil index
            $this->updateAmount($key);
        }
    }

    public function updateAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price_base'])) {
            $this->input_details[$key]['amount'] =
                $this->input_details[$key]['qty'] * $this->input_details[$key]['price_base'];
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
            // Pastikan matl_id diisi
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
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->trType)->orderBy('tr_seq')->get();
            // if ($this->object_detail->isEmpty()) {
            //     return;
            // }

            // foreach ($this->object_detail as $key => $detail) {
            //     $this->input_details[$key] = populateArrayFromModel($detail);
            //     $this->input_details[$key]['name'] = $detail->Material?->name;
            //     $this->input_details[$key]['id'] = $detail->id;
            //     $this->input_details[$key]['selling_price'] = $detail->Material->jwl_selling_price;
            //     $this->input_details[$key]['sub_total'] = $detail->amt;
            //     $this->input_details[$key]['isOrderedMaterial'] = $detail->Material->isOrderedMaterial();
            //     // $this->input_details[$key]['barcode'] = $detail->Material?->MatlUom[0]->barcode;
            //     // $this->input_details[$key]['image_path'] = $detail->Material?->Attachment->first()?->getUrl() ?? null;
            // }
        }
    }

    public function onValidateAndSave()
    {
        $this->validate();
        dd(vars: $this->input_details);
        try {
            foreach ($this->input_details as $key => $detail) {
                if (is_array($detail) && isset($detail['matl_id'])) {
                    // Set 'tr_seq' secara unik
                    if (!isset($detail['tr_seq']) || $this->isDuplicateTrSeq($detail['trhdr_id'], $detail['tr_seq'])) {
                        $detail['tr_seq'] = $this->getUniqueTrSeq($detail['trhdr_id']);
                    }

                    OrderDtl::updateOrCreate(
                        ['id' => $detail['id'] ?? null],
                        $detail
                    );
                } else {
                    $this->dispatch('error', __('generic.error.matl_id_required'));
                }
            }
            $this->dispatch('success', __('generic.string.save_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.save_item', ['message' => $e->getMessage()]));
        }
    }

    private function isDuplicateTrSeq($trhdr_id, $tr_seq)
    {
        return OrderDtl::where('trhdr_id', $trhdr_id)->where('tr_seq', $tr_seq)->exists();
    }

    private function getUniqueTrSeq($trhdr_id)
    {
        $maxTrSeq = OrderDtl::where('trhdr_id', $trhdr_id)->max('tr_seq');
        return $maxTrSeq ? $maxTrSeq + 1 : 1;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
