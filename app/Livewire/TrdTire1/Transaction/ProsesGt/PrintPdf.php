<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderDtl;

class PrintPdf extends BaseComponent
{
    public $orders;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        $orderIds = json_decode($objectId, true) ?? [];
        $this->orders = OrderDtl::with(['OrderHdr', 'OrderHdr.Partner', 'SalesReward'])
            ->whereIn('id', $orderIds)
            ->get();
    }

    public function render()
    {
        return view('livewire.trd-tire1.transaction.proses-gt.print-pdf', [
            'orders' => $this->orders,
        ]);
    }
}
