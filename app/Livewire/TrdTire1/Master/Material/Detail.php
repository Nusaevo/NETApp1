<?php

namespace App\Livewire\TrdTire1\Master\Material;
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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events


    #endregion
}
