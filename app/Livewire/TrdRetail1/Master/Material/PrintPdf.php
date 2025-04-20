<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Master\MatlUom;

class PrintPdf extends BaseComponent
{
    #region Constant Variables

    public $object;
    public $barcode;
    public $barcodeName;
    public $barcodeCode;
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $itemUnit = MatlUom::find($this->objectIdValue);
        $this->barcode = (string)$itemUnit->barcode;
        $this->barcodeName = $itemUnit->Material->name . " - " . $itemUnit->matl_uom;
        $this->barcodeCode = $itemUnit->barcode;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    #endregion

    #region CRUD Methods

    protected function onReset()
    {
    }

    public function onValidateAndSave()
    {
    }

    #endregion

    #region Component Events

    #endregion
}
