<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseComponent;
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
