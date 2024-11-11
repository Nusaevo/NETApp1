<?php

namespace App\Livewire\SrvInsur1\Master\Partner;
use App\Livewire\Component\BaseComponent;
class Index extends BaseComponent
{
    protected function onPreRender()
    {

    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
