<?php

namespace App\Http\Livewire\TrdJewel1\Procurement\PurchaseOrder;

use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Http\Livewire\Component\BaseComponent;

class PrintPdf extends BaseComponent
{
    public $object;
    public $objectIdValue;
    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = OrderHdr::findOrFail($this->objectIdValue);
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected function onPopulateDropdowns()
    {

    }

    protected function onReset()
    {
    }

    public function onValidateAndSave()
    {
    }
}
