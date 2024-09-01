<?php

namespace App\Livewire\TrdJewel1\Master\Material;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Material;

class PrintPdf extends BaseComponent
{
    #region Constant Variables

    public $object;
    public $data = [];

    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $decodedParams = json_decode(urldecode($this->additionalParam), true);

        if (is_array($decodedParams)) {
            $this->data = $decodedParams;
        } else {
            $this->notify('error', __('Invalid parameter data.'));
        }
    }

    public function render()
    {
        return view($this->renderRoute, ['data' => $this->data]);
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
