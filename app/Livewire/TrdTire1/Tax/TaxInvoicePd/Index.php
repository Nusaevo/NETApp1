<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoicePd;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use Livewire\Attributes\On;
use App\Livewire\TrdTire1\Tax\TaxInvoice\IndexDataTable as TaxInvoiceTable;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $selectedItems = [];
    public $taxDocNum = ''; // Input nomor faktur
    public $actionType = ''; // Jenis aksi: 'set', 'change', 'delete'
    public $taxDocNumInputs = []; // Input Nomor Faktur per order id

    protected $rules = [
        'taxDocNum' => 'required|numeric|min:1',
    ];

    protected $messages = [
        'taxDocNum.required' => 'Nomor faktur harus diisi.',
        'taxDocNum.numeric' => 'Nomor faktur harus berupa angka.',
        'taxDocNum.min' => 'Nomor faktur harus lebih dari 0.',
    ];

    protected $listeners = [
        'openProsesDateModal',
        'openNomorFakturModal',
    ];

    public function openProsesDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems = $selectedItems;
        $this->dispatch('open-modal-proses-date');
    }

    public function openNomorFakturModal($orderIds, $selectedItems, $actionType)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems = $selectedItems;
        $this->actionType = $actionType;
        $this->taxDocNum = ''; // Reset input global (tidak dipakai lagi)
        // Inisialisasi input nomor faktur per baris
        $this->taxDocNumInputs = [];
        foreach ($this->selectedItems as $item) {
            if (isset($item['id'])) {
                $this->taxDocNumInputs[$item['id']] = $item['faktur'] ?? '';
            }
        }
        $this->dispatch('open-modal-nomor-faktur');
    }

    public function submitNomorFaktur()
    {
                // Untuk action delete, tidak perlu validasi nomor faktur
        if ($this->actionType === 'delete') {
            // Hapus nomor faktur untuk order yang dipilih
            // Gunakan nilai 0 untuk menandakan nomor faktur telah dihapus
            OrderHdr::whereIn('id', $this->selectedOrderIds)->update(['tax_doc_num' => 0]);

            $this->dispatch('clearSelections')->to(TaxInvoiceTable::class);
            $this->dispatch('refreshDatatable')->to(TaxInvoiceTable::class);
            $this->dispatch('close-modal-nomor-faktur');
            $this->dispatch('success', 'Nomor faktur berhasil dihapus');
            return;
        }

        // Tidak ada validasi global, karena input per baris
        // Simpan nomor faktur per order (pertahankan leading zero)
        foreach ($this->selectedOrderIds as $orderId) {
            if (isset($this->taxDocNumInputs[$orderId]) && $this->taxDocNumInputs[$orderId] !== '') {
                $numRaw = trim((string) $this->taxDocNumInputs[$orderId]);
                // Validasi per baris: hanya digit dan nilai numeriknya > 0
                if ($numRaw === '' || !ctype_digit($numRaw) || intval($numRaw) <= 0) {
                    $this->dispatch('error', "Nomor faktur untuk order {$orderId} tidak valid");
                    return;
                }
                // Cek unik berdasarkan string apa adanya (agar '01' berbeda dengan '1')
                $exists = OrderHdr::where('tax_doc_num', $numRaw)
                    ->where('id', '!=', $orderId)
                    ->where('tax_doc_num', '!=', 0)
                    ->exists();
                if ($exists) {
                    $this->dispatch('error', "Nomor faktur {$numRaw} sudah digunakan");
                    return;
                }
                OrderHdr::where('id', $orderId)->update(['tax_doc_num' => $numRaw]);
            }
        }
        $this->dispatch('clearSelections')->to(TaxInvoiceTable::class);
        $this->dispatch('refreshDatatable')->to(TaxInvoiceTable::class);
        $this->dispatch('close-modal-nomor-faktur');
        $this->dispatch('success', 'Nomor faktur berhasil disimpan');
        // $this->dispatch('refreshPage');
    }

    protected function onPreRender()
    {

    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
