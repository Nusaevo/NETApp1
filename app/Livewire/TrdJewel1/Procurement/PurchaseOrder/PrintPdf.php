<?php

namespace App\Livewire\TrdJewel1\Procurement\PurchaseOrder;

use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Livewire\Component\BaseComponent;

class PrintPdf extends BaseComponent
{
    public $object;
    public $objectIdValue;
    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
        $this->object = OrderHdr::findOrFail($this->objectIdValue);
        }
    }

    protected function onLoadForEdit()
    {
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
