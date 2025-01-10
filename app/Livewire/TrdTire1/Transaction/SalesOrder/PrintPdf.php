<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

use App\Models\TrdTire1\Transaction\OrderHdr;
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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
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
