<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;

class PrintPdf extends BaseComponent
{
    public $orders;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        $this->orders = json_decode($objectId, true) ?? [];
    }

    public function render()
    {
        return view('livewire.trd-tire1.tax.tax-invoice.print-pdf', [
            'orders' => OrderHdr::whereIn('id', array_column($this->orders, 'id'))->get()
        ]);
    }
}
