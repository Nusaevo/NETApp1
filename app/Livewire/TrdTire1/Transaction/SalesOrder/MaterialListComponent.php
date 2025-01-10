<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\OrderDtl;
use Exception;

class MaterialListComponent extends BaseComponent
{
    public $object_detail;
    public $input_details = [];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*' => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('product'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.price' => $this->trans('price'),
        ];

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
                    'qty' => 1,
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

        foreach ($this->input_details as $index => $item) {
            if (empty($item['matl_id']) || $item['qty'] <= 0 || $item['price'] <= 0) {
                $this->dispatch('error', __('generic.error.field_required', ['field' => "Item #$index"]));
                return false;
            }
        }

        return true;
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            // $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->trType)->orderBy('tr_seq')->get();

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
            //     $this->input_details[$key]['barcode'] = $detail->Material?->MatlUom[0]->barcode;
            //     $this->input_details[$key]['image_path'] = $detail->Material?->Attachment->first()?->getUrl() ?? null;
            // }
        }
    }

    public function onValidateAndSave()
    {
        if (!$this->validateItems()) {
            return;
        }

        try {
            $this->input_details['bank'] = $this->banks;
            $this->input_details['npwp'] = $this->npwp;
            $this->input_details['position'] = $this->position;
            $this->input_details['partner_id'] = $this->object->partner_id;
            $this->object_detail->fillAndSanitize($this->input_details);
            $this->object_detail->save();
            // foreach ($this->input_details as $detail) {
            //     $this->object_detail->fillAndSanitize($this->input_details[$key]);
            //     $this->object->save();
            //     OrderDtl::updateOrCreate(
            //         ['id' => $detail['id'] ?? null],
            //         $detail
            //     );
            // }
            $this->dispatch('success', __('generic.string.save_item'));
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
