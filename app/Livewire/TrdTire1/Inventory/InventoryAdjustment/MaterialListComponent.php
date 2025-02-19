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

class MaterialListComponent extends DetailComponent
{
    public $materials;
    protected $masterService;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    public $wh_code; // Add this property

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
        $this->masterService = new MasterService();
        $ivttrDtl = IvttrDtl::where('trhdr_id', $this->objectIdValue)->first();
        if ($ivttrDtl) {
            $materialIds = IvtBal::where('wh_code', $ivttrDtl->wh_code)->pluck('matl_id')->toArray();
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

    public function onMaterialChanged($key, $matl_id, $wh_code)
    {
        if ($matl_id && $wh_code) {
            $ivtBal = IvtBal::where('matl_id', $matl_id)->where('wh_code', $wh_code)->first();
            if ($ivtBal) {
                $material = Material::find($ivtBal->matl_id);
                if ($material) {
                    $matlUom = MatlUom::where('matl_id', $ivtBal->matl_id)->first();
                    if ($matlUom) {
                        $this->input_details[$key]['matl_id'] = $material->id;
                        $this->input_details[$key]['price'] = $matlUom->selling_price;
                        $this->input_details[$key]['matl_uom'] = $material->uom;
                        $this->input_details[$key]['matl_descr'] = $material->name;
                    } else {
                        $this->dispatch('error', __('generic.error.material_uom_not_found'));
                    }
                } else {
                    $this->dispatch('error', __('generic.error.material_not_found'));
                }
            } else {
                $this->dispatch('error', __('generic.error.ivtbal_not_found'));
            }
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

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = IvttrDtl::where('trhdr_id', $this->object->id)->orderBy('tr_seq')->get();

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
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
            $ivttrDtl->matl_code = $detail['matl_code'];
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
