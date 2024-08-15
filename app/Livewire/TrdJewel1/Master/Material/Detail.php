<?php

namespace App\Livewire\TrdJewel1\Master\Material;
use App\Livewire\Component\BaseComponent;

use Livewire\Component;

class Detail extends BaseComponent
{
    #region Constant Variables


    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {

    }
    public function render()
    {
        return view($this->renderRoute);
    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events


    #endregion
}
