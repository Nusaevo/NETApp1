<?php

namespace App\Livewire\TrdTire2\Master\Partner;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdTire2\Master\{PartnerDetail, Partner};
use Illuminate\Support\Facades\DB;
use Exception;

class ShippingAddressComponent extends DetailComponent
{
    public $object_detail;
    public $input_details = [];
    public $inputs = [
        'grp' => '',
    ];

    public $rules  = [
        'input_details.*.name' => 'required',
        'input_details.*.address' => 'required',
    ];

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*.name'    => $this->trans('name'),
            'input_details.*.address' => $this->trans('address'),
        ];

        if (!empty($this->objectIdValue)) {
            $this->object = Partner::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
            //load detail bank
        }
    }

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function addItem()
    {
        if (!empty($this->objectIdValue) && $this->inputs['grp'] !== 'S') {
            try {
                // isi default field sesuai field wp_details
                $this->input_details[] = [
                    'name' => '',
                    'address' => '',
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong save Header terlebih dahulu.']));
            } else {
                $this->dispatch('error', __('generic.error.save', ['message' => 'Kategori tidak sesuai.']));
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

    public function validateItems()
    {
        //contoh validasi di sini
        // if (empty($this->input_details)) {
        //     $this->dispatch('error', __('generic.error.empty_item'));
        //     return false;
        // }

        // foreach ($this->input_details as $index => $item) {
        //     if (empty($item['matl_id']) || $item['qty'] <= 0 || $item['price'] <= 0) {
        //         $this->dispatch('error', __('generic.error.field_required', ['field' => "Item #$index"]));
        //         return false;
        //     }
        // }

        return true;
    }

    protected function loadDetails()
    {
        //find partner detail where partner_id = this->objecti->id
        if (!empty($this->objectIdValue)) {
            $partnerDetail = PartnerDetail::where('partner_id', $this->object->id)->first();
            if ($partnerDetail) {
                $this->input_details = $partnerDetail->shipping_address ?? [];
            }
        }
    }

    public function SaveShippingAddress()
    {
        if ($this->actionValue === 'Create') {
            $this->input_details[] = [
                'name' => $this->inputs['name'],
                'address' => $this->inputs['address'] . ' - ' . $this->inputs['city'],
            ];
        }
        $this->validate();
        $this->onValidateAndSave();
        $this->dispatch('success', __('Shipping Address saved successfully!'));
    }

    protected function onValidateAndSave()
    {
        $shippingAddressArray = [];
        foreach ($this->input_details as $detail) {
            $shippingAddressArray[] = [
                'name' => $detail['name'],
                'address' => $detail['address'],
            ];
        }

        $partnerDetail = PartnerDetail::where('partner_id', $this->object->id)->first();
        $partnerData = [
            'partner_grp' => $this->object->grp,
            'partner_code' => $this->object->code,
            'shipping_address' => $shippingAddressArray,
        ];

        if ($partnerDetail) {
            $partnerDetail->update($partnerData);
        } else {
            PartnerDetail::create(array_merge(['partner_id' => $this->object->id], $partnerData));
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
