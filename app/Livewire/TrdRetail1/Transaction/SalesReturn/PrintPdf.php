<?php

// App\Livewire\TrdRetail1\Transaction\SalesOrder\PrintPdf.php
namespace App\Livewire\TrdRetail1\Transaction\SalesReturn;

use Livewire\Component;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Transaction\OrderHdr;
use App\Services\TrdRetail1\Master\MasterService;

class PrintPdf extends BaseComponent
{
    #region Constant Variables

    #endregion

    #region Populate Data methods

    public function onPreRender()
    {
        $this->object = OrderHdr::findOrFail($this->objectIdValue);

    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events


    #endregion


}
