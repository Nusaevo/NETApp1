<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdRetail1\Master\{MatlUom, Material};
use App\Services\TrdRetail1\Master\MasterService;
use Exception;

class UomListComponent extends DetailComponent
{
    public $object_detail;
    public $input_details = [];
    protected $masterService;
    public $materialUOM;

    public $rules = [
        'input_details.*.matl_uom' => 'required|string|max:50',
        'input_details.*.reff_uom' => 'required|string|max:50',
        'input_details.*.reff_factor' => 'required|numeric|min:1',
        'input_details.*.base_factor' => 'required|numeric|min:1',
        'input_details.*.barcode' => 'nullable|string|max:50',
        'input_details.*.selling_price' => 'nullable|numeric|min:0',
    ];

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*.matl_uom' => 'Base UOM',
            'input_details.*.reff_uom' => 'Reff UOM',
            'input_details.*.reff_factor' => 'Reff Factor',
            'input_details.*.base_factor' => 'Base Factor',
            'input_details.*.barcode' => 'Barcode',
            'input_details.*.selling_price' => 'Selling Price',
        ];
        $this->masterService = new MasterService();
        $this->materialUOM = $this->masterService->getMatlUOMData(); // Ambil data UOM

        if (!empty($this->objectIdValue)) {
            $this->object = Material::find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
        }
    }

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                $this->input_details[] = [
                    'matl_uom' => '',
                    'reff_uom' => '',
                    'reff_factor' => 1, // Default 1
                    'base_factor' => 1, // Default 1
                    'barcode' => '',
                    'selling_price' => 0,
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong save Material terlebih dahulu']));
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
        if (!empty($this->objectIdValue)) {
            $uoms = MatlUom::where('matl_id', $this->objectIdValue)->get();
            $this->input_details = $uoms->map(function ($uom) {
                return [
                    'matl_uom' => $uom->matl_uom,
                    'reff_uom' => $uom->reff_uom,
                    'reff_factor' => $uom->reff_factor ?? 1,
                    'base_factor' => $uom->base_factor ?? 1,
                    'barcode' => $uom->barcode,
                    'selling_price' => $uom->selling_price,
                ];
            })->toArray();
        }
    }

    public function SaveUom()
    {
        $this->Save();
    }

    protected function onValidateAndSave()
    {
        foreach ($this->input_details as $detail) {
            MatlUom::updateOrCreate(
                ['matl_id' => $this->object->id, 'matl_uom' => $detail['matl_uom']],
                [
                    'reff_uom' => $detail['reff_uom'],
                    'reff_factor' => $detail['reff_factor'] ?? 1,
                    'base_factor' => $detail['base_factor'] ?? 1,
                    'barcode' => $detail['barcode'],
                    'selling_price' => $detail['selling_price'],
                ]
            );
        }
    }

    public function render()
    {
        return view(getViewPath(__NAMESPACE__, class_basename($this)));
    }
}
