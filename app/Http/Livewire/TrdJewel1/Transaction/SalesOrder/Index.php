<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\SalesOrder;

use App\Http\Livewire\Component\BaseComponent;
class Index extends BaseComponent
{
    protected function onPreRender()
    {

    }

    public function render()
    {
        return view($this->renderRoute);
    }
}
