<?php

namespace App\Livewire\TrdJewel1\Master\Material;
use App\Livewire\Component\BaseComponent;

use Livewire\Component;

class Detail extends BaseComponent
{
    protected function onPreRender()
    {

    }
    public function render()
    {
        return view($this->renderRoute);
    }
}
