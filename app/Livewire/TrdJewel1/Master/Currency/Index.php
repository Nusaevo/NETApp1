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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
