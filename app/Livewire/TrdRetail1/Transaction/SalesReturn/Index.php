<?php

namespace App\Livewire\TrdRetail1\Transaction\SalesReturn;

use App\Livewire\Component\BaseComponent;

class Index extends BaseComponent
{
    protected function onPreRender()
    {
        // Initialize data if needed
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
