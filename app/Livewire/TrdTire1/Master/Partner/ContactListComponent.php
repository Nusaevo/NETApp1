<?php

namespace App\Livewire\TrdTire1\Master\Partner;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\PartnerDetail;
use Exception;

class ContactListComponent extends BaseComponent
{
    public $bank = [];
    public $npwp = [];
    public $kontak = [];

    public $rules  = [
        'inputs.name_contact' => 'required|min:1|max:50',
        'inputs.position' => 'nullable|string|max:50',
        'inputs.date_of_birth' => 'nullable|date',
        'inputs.phone1' => 'nullable|string|max:20',
        'inputs.phone2' => 'nullable|string|max:20',
        'inputs.email' => 'nullable|email|max:100',
        'inputs.address_contact' => 'nullable|string|max:255',
        'inputs.note' => 'nullable|string|max:255',
    ];

    protected function onPreRender() {}

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function addBank()
    {
        try {
            $this->bank[] = [];
            $this->dispatch('success', 'Item kosong berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menambahkan item kosong: ' . $e->getMessage());
        }
    }

    public function deleteBank($index)
    {
        try {
            unset($this->bank[$index]);
            $this->bank = array_values($this->bank); // Re-index array
            $this->dispatch('success', 'Item berhasil dihapus.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    public function addNPWP()
    {
        try {
            $this->npwp[] = [];
            $this->dispatch('success', 'Item kosong berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menambahkan item kosong: ' . $e->getMessage());
        }
    }

    public function deleteNpwp($index)
    {
        try {
            unset($this->npwp[$index]);
            $this->npwp = array_values($this->npwp); // Re-index array
            $this->dispatch('success', 'Item berhasil dihapus.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    public function addKontak()
    {
        try {
            $this->kontak[] = [];
            $this->dispatch('success', 'Item kosong berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menambahkan item kosong: ' . $e->getMessage());
        }
    }

    public function deleteKontak($index)
    {
        try {
            unset($this->kontak[$index]);
            $this->kontak = array_values($this->kontak); // Re-index array
            $this->dispatch('success', 'Item berhasil dihapus.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    public function onValidateAndSave()
    {
        try {
            if (!isset($this->inputs) || !is_array($this->inputs)) {
                throw new Exception('Inputs are not properly set or not an array.');
            }

            $wpDetails = [
                'name_contact' => $this->inputs['name_contact'] ?? null
            ];

            dd($wpDetails);

            if (is_array($wpDetails)) {
                $partnerDetail = new PartnerDetail();
                $partnerDetail->wp_details = $wpDetails;
                $partnerDetail->save();


                $this->dispatch('success', 'Data berhasil disimpan.');
            } else {
                throw new Exception('wp_details is not an array.');
            }
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }
}
