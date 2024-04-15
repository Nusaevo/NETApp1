<?php

namespace App\Http\Livewire\SysConfig1\ConfigMenu;

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
