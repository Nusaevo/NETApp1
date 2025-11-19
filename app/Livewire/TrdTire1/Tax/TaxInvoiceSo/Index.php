<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoiceSo;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{OrderHdr, BillingHdr};
use Livewire\Attributes\On;
use App\Livewire\TrdTire1\Tax\TaxInvoiceSo\IndexDataTable as TaxInvoiceTable;

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
            // Hapus nomor faktur untuk billing yang dipilih
            // Gunakan nilai 0 untuk menandakan nomor faktur telah dihapus
            $orderHdrs = OrderHdr::whereIn('id', $this->selectedOrderIds)->get(['id', 'tr_code']);
            foreach ($orderHdrs as $orderHdr) {
                BillingHdr::where('tr_code', $orderHdr->tr_code)
                    ->where('tr_type', 'ARB')
                    ->update(['taxinv_num' => 0]);
            }

            $this->dispatch('clearSelections')->to(TaxInvoiceTable::class);
            $this->dispatch('refreshDatatable')->to(TaxInvoiceTable::class);
            $this->dispatch('close-modal-nomor-faktur');
            $this->dispatch('success', 'Nomor faktur berhasil dihapus');
            return;
        }

        // Tidak ada validasi global, karena input per baris
        // Simpan nomor faktur per billing (pertahankan leading zero)
        $orderHdrs = OrderHdr::whereIn('id', $this->selectedOrderIds)->get(['id', 'tr_code']);

        foreach ($orderHdrs as $orderHdr) {
            $orderId = $orderHdr->id;
            if (isset($this->taxDocNumInputs[$orderId]) && $this->taxDocNumInputs[$orderId] !== '') {
                $numRaw = trim((string) $this->taxDocNumInputs[$orderId]);
                // Validasi per baris: hanya digit dan nilai numeriknya > 0
                if ($numRaw === '' || !ctype_digit($numRaw) || intval($numRaw) <= 0) {
                    $this->dispatch('error', "Nomor faktur untuk nota {$orderHdr->tr_code} tidak valid");
                    return;
                }

                // Cek unik berdasarkan string apa adanya (agar '01' berbeda dengan '1')
                $exists = BillingHdr::where('taxinv_num', $numRaw)
                    ->where('tr_code', '!=', $orderHdr->tr_code)
                    ->where('tr_type', 'ARB')
                    ->where('taxinv_num', '!=', 0)
                    ->exists();
                if ($exists) {
                    $this->dispatch('error', "Nomor faktur {$numRaw} sudah digunakan");
                    return;
                }

                // Update BillingHdr dengan taxinv_num
                BillingHdr::where('tr_code', $orderHdr->tr_code)
                    ->where('tr_type', 'ARB')
                    ->update(['taxinv_num' => $numRaw]);
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
