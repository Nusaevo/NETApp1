<?php

namespace App\Http\Livewire\TrdJewel1\Master\Material;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Material;

class PrintPdf extends BaseComponent
{
    public $object;

    protected function onPreRender()
    {
        $additionalParams = explode(';', urldecode($this->additionalParam));
        $this->object = Material::withTrashed()->whereIn('id', $additionalParams)->first();
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
