<?php

namespace App\Livewire\TrdJewel1\Master\Currency;
use App\Livewire\Component\BaseComponent;
class Index extends BaseComponent
{
    protected function onPreRender()
    {
        $this->bypassPermissions = true;
    }

    public function render()
    {
        return view($this->renderRoute)->layout('layout.app');
    }
}
