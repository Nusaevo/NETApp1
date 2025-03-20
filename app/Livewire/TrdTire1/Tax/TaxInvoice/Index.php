<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use Illuminate\Support\Facades\DB;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $print_date = ''; // Add this line
    public $selectedItems = [];

    protected $listeners = [
        'openProsesDateModal',
    ];

    public function openProsesDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems = $selectedItems;
        $this->print_date = '';
        $this->dispatch('open-modal-proses-date');
    }

    public function submitProsesDate()
    {
        $this->validate([
            'print_date' => 'date',
        ]);

        DB::beginTransaction();
        dd($this->print_date);

        $selectedOrders = OrderHdr::whereIn('id', $this->selectedOrderIds)->get();

        // foreach ($selectedOrders as $order) {
        //     $order->update(['print_date' => $this->print_date]);
        // }

        DB::commit();

        $this->dispatch('close-modal-proses-date');
        $this->dispatch('success', 'Tanggal berhasil disimpan');
        $this->dispatch('refreshDatatable');
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
