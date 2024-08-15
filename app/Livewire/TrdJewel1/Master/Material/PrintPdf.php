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
        $additionalParams = explode(';', urldecode($this->additionalParam));
        $this->object = Material::withTrashed()->whereIn('id', $additionalParams)->first();
    }

    public function render()
    {
        return view($this->renderRoute);
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
