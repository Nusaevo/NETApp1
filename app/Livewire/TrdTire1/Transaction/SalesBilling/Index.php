<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Transaction\{DelivDtl, DelivHdr, OrderDtl, OrderHdr, BillingHdr};
use Illuminate\Support\Facades\DB;
use App\Services\TrdTire1\Master\MasterService;
use Livewire\Attributes\On;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $deliveryDate = '';
    protected $masterService;
    public $warehouses;
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
            'tr_date' => 'date',
        ]);

        DB::beginTransaction();

        $selectedOrders = BillingHdr::whereIn('id', $this->selectedOrderIds)->get();

        foreach ($selectedOrders as $order) {
            $order->update(['print_date' => $this->tr_date]);
        }

        DB::commit();

        $this->dispatch('close-modal-delivery-date');
        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Tanggal penagihan berhasil disimpan'
        ]);

        $this->dispatch('refreshDatatable');
    }

    public function onPrerender()
    {
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
