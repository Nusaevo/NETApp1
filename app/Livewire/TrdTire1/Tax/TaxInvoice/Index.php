<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $selectedItems = [];
    public $taxDocNum = ''; // Input nomor faktur
    public $actionType = ''; // Jenis aksi: 'set', 'change', 'delete'

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
        $this->taxDocNum = ''; // Reset input
        $this->dispatch('open-modal-nomor-faktur');
    }

            public function submitNomorFaktur()
    {
                // Untuk action delete, tidak perlu validasi nomor faktur
        if ($this->actionType === 'delete') {
            DB::beginTransaction();
            try {
                // Hapus nomor faktur untuk order yang dipilih
                // Gunakan nilai 0 untuk menandakan nomor faktur telah dihapus
                OrderHdr::whereIn('id', $this->selectedOrderIds)->update(['tax_doc_num' => 0]);

                DB::commit();
                $this->dispatch('close-modal-nomor-faktur');
                $this->dispatch('success', 'Nomor faktur berhasil dihapus');
                $this->dispatch('refreshDatatable');

            } catch (\Exception $e) {
                DB::rollBack();
                $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
            }
            return;
        }

        // Validasi untuk action set dan change
        $this->validate($this->rules, $this->messages);

                $taxDocNum = (int) $this->taxDocNum;

        // Cek apakah nomor faktur sudah digunakan (exclude nilai 0 yang menandakan dihapus)
        $existingOrder = OrderHdr::where('tax_doc_num', $taxDocNum)
            ->where('tax_doc_num', '!=', 0)
            ->first();
        if ($existingOrder && $this->actionType !== 'change') {
            $this->dispatch('error', "Nomor faktur {$taxDocNum} sudah digunakan");
            return;
        }

        DB::beginTransaction();
        try {
            switch ($this->actionType) {
                case 'set':
                    // Set nomor faktur untuk order yang dipilih
                    OrderHdr::whereIn('id', $this->selectedOrderIds)->update(['tax_doc_num' => $taxDocNum]);
                    break;

                case 'change':
                    // Ubah nomor faktur untuk order yang dipilih
                    OrderHdr::whereIn('id', $this->selectedOrderIds)->update(['tax_doc_num' => $taxDocNum]);
                    break;
            }

            DB::commit();
            $this->dispatch('close-modal-nomor-faktur');
            $this->dispatch('success', 'Nomor faktur berhasil disimpan');
            $this->dispatch('refreshDatatable');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
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
