<?php

namespace App\Livewire\TrdTire1\Master\Partner;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\PartnerDetail;
use App\Models\TrdTire1\Master\Partner;
use Illuminate\Support\Facades\DB;
use Exception;

class BankListComponent extends BaseComponent
{
    public $object_detail;
    public $input_details = [];

    public $rules = [
        'input_details.*.bank_acct' => 'required|min:1|max:50',
        'input_details.*.bank_name' => 'required|min:1|max:50',
        'input_details.*.bank_location' => 'max:50',
    ];

    protected function onPreRender()
    {
        // $this->customValidationAttributes  = [
        //     'input_details.*.tr_date'      => $this->trans('tr_date'),
        //     'input_details.*.payment_term_id'      => $this->trans('payment'),
        //     'input_details.*.partner_id'      => $this->trans('partner'),
        //     'input_details.*'              => $this->trans('product'),
        //     'input_details.*.matl_id' => $this->trans('product'),
        //     'input_details.*.qty' => $this->trans('qty'),
        //     'input_details.*.price' => $this->trans('selling_price'),
        // ];

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
                // isi default field sesuai field banks
                $this->input_details[] = [
                    'bank_acct' => '',
                    'bank_name' => '',
                    'bank_location' => '',
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
                $this->input_details = $partnerDetail->banks ?? [];
            }
        }
    }

    public function SaveBank()
    {
        $this->SaveComponent();
    }

    protected function onValidateAndSave()
    {
        $banksArray = [];
        foreach ($this->input_details as $detail) {
            $banksArray[] = [
                'bank_acct' => $detail['bank_acct'],
                'bank_name' => $detail['bank_name'],
                'bank_location' => $detail['bank_location'],
            ];
        }
        $partnerDetail = PartnerDetail::where('partner_id', $this->object->id)->first();
        if ($partnerDetail) {
            $partnerDetail->update(['banks' => json_encode($banksArray)]);
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
