<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\DelivHdr;
use App\Models\TrdTire1\Transaction\OrderHdr;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $deliveryDate = '';
    public $selectedItems = [];
    public $tr_date = ''; // Add this line

    protected $listeners = [
        'openDeliveryDateModal',
    ];

    public function openDeliveryDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems = $selectedItems;
        $this->deliveryDate = '';
        $this->dispatch('open-modal-delivery-date');
    }

    public function submitDeliveryDate()
    {
        $this->validate([
            'tr_date' => 'required|date'
        ]);

        DB::beginTransaction();

        $selectedOrders = OrderHdr::whereIn('id', $this->selectedOrderIds)->get();

        foreach ($selectedOrders as $order) {
            DelivHdr::updateOrCreate(
                [
                    'tr_type' => 'SD',
                    'tr_code' => $order->tr_code,
                ],
                [
                    'tr_date' => $this->tr_date,
                    'partner_id' => $order->partner_id,
                    'partner_code' => $order->partner_code,
                    'status_code' => $order->status_code,
                ]
            );
        }

        DB::commit();

        $this->dispatch('close-modal-delivery-date');
        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Tanggal pengiriman berhasil disimpan'
        ]);

        $this->dispatch('refreshDatatable');
    }

    public function onPrerender()
    {
        // Lakukan logika apapun sebelum render
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
