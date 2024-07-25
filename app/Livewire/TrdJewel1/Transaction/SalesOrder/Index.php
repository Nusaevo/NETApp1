<?php

namespace App\Livewire\TrdJewel1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
class Index extends BaseComponent
{
    protected function onPreRender()
    {

    }

    public function render()
    {
        return view($this->renderRoute)->layout('layout.app');
    }
}
