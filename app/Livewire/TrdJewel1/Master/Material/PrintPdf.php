<?php

namespace App\Livewire\TrdJewel1\Master\Material;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Material;

class PrintPdf extends BaseComponent
{
    #region Constant Variables

    public $object;

    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->object = Material::withTrashed()->find($this->objectIdValue);
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
