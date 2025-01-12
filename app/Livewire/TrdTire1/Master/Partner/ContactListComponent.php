<?php

namespace App\Livewire\TrdTire1\Master\Partner;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdTire1\Master\{PartnerDetail, Partner};
use Exception;

class ContactListComponent extends DetailComponent
{
    public $object_detail;
    public $input_details = [];
    public $rules = [
        'input_details.*.contact_name' => 'required|min:1|max:50',
        'input_details.*.position' => 'required|min:1|max:50',
        'input_details.*.phone1' => 'required|max:13',
        'input_details.*.phone2' => 'max:13',
        'input_details.*.email' => 'max:50',
        'input_details.*.contact_address' => 'max:50',
        'input_details.*.contact_note' => 'max:50',
        'input_details.*.date_of_birth' => 'max:50',
    ];


    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*.contact_name'    => 'Nama Kontak',
            'input_details.*.position'        => 'Jabatan',
            'input_details.*.phone1'          => 'Nomor Telepon Utama',
            'input_details.*.phone2'          => 'Nomor Telepon Sekunder',
            'input_details.*.email'           => 'Email',
            'input_details.*.contact_address' => 'Alamat Kontak',
            'input_details.*.contact_note'    => 'Catatan Kontak',
            'input_details.*.date_of_birth'   => 'Tanggal Lahir',
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
                // isi default field sesuai field banks
                $this->input_details[] = [
                    'contact_name' => '',
                    'position' => '',
                    'date_of_birth' => '',
                    'phone1' => '',
                    'phone2' => '',
                    'email' => '',
                    'contact_address' => '',
                    'contact_note' => '',
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
                $this->input_details = $partnerDetail->contacts ?? [];
            }
        }
    }

    public function SaveContact()
    {
        $this->Save();
    }

    protected function onValidateAndSave()
    {
        $banksArray = [];
        foreach ($this->input_details as $detail) {
            $banksArray[] = [
                'contact_name' => $detail['contact_name'],
                'position' => $detail['position'],
                'date_of_birth' => $detail['date_of_birth'],
                'phone1' => $detail['phone1'],
                'phone2' => $detail['phone2'],
                'email' => $detail['email'],
                'contact_address' => $detail['contact_address'],
                'contact_note' => $detail['contact_note'],
            ];
        }
        $partnerDetail = PartnerDetail::where('partner_id', $this->object->id)->first();
        if ($partnerDetail) {
            $partnerDetail->update(['contacts' => json_encode($banksArray)]);
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
