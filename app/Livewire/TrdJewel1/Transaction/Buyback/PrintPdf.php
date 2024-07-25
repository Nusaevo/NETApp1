<?php

// App\Livewire\TrdJewel1\Transaction\SalesOrder\PrintPdf.php
namespace App\Livewire\TrdJewel1\Transaction\Buyback;

use Livewire\Component;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\OrderHdr;

class PrintPdf extends BaseComponent
{
    public $printSettings = [
        'item_checked' => false,
        'no_return' => false,
        'trade_in_minus15' => false,
        'sale_minus25' => false,
        'trade_in_minus10' => false,
        'sale_minus20' => false,
    ];

    public function onPreRender()
    {
    }

    public function onPopulateDropdowns()
    {
    }

    protected function onLoadForEdit()
    {
        $this->object = OrderHdr::findOrFail($this->objectIdValue);
        $this->printSettings = json_decode($this->object->print_settings, true) ?? $this->printSettings;
    }


    public function savePrintSettings()
    {
        $this->order->print_settings = json_encode($this->printSettings);
        $this->order->save();
        $this->dispatch('notify', 'Pengaturan cetak disimpan.');
    }

    public function render()
    {
        return view($this->renderRoute)->layout('layout.app');
    }
}
