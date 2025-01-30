<?php

namespace App\Livewire\TrdTire1\Master\Partner;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdTire1\Master\{PartnerDetail, Partner};
use Illuminate\Support\Facades\DB;
use Exception;

class NpwpListComponent extends DetailComponent
{
    public $object_detail;
    public $input_details = [];


    public $rules  = [
        'input_details.*.npwp' => 'required',
        'input_details.*.wp_name' => 'required',
        'input_details.*.wp_location' => 'required',
    ];

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*.npwp'        => $this->trans('npwp'),
            'input_details.*.wp_name'     => $this->trans('wp_name'),
            'input_details.*.wp_location' => $this->trans('wp_location'),
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
        if (!empty($this->objectIdValue)) {
            try {
                // isi default field sesuai field wp_details
                $this->input_details[] = [
                    'npwp' => '',
                    'wp_name' => '',
                    'wp_location' => '',
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
                $this->input_details = $partnerDetail->wp_details ?? [];
            }
        }
    }

    public function SaveNPWP()
    {
        $this->Save();
    }

    protected function onValidateAndSave()
    {
        $wp_detailsArray = [];
        foreach ($this->input_details as $detail) {
            $wp_detailsArray[] = [
                'npwp' => $detail['npwp'],
                'wp_name' => $detail['wp_name'],
                'wp_location' => $detail['wp_location'],
            ];
        }
        $partnerDetail = PartnerDetail::where('partner_id', $this->object->id)->first();
        if ($partnerDetail) {
            $partnerDetail->update(['wp_details' => json_encode($wp_detailsArray)]);
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
