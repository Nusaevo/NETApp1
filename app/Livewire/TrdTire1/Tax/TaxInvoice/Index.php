<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use Illuminate\Support\Facades\DB;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $selectedItems = [];

    protected $listeners = [
        'openProsesDateModal',
    ];

    public function openProsesDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems = $selectedItems;
        $this->dispatch('open-modal-proses-date');
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
