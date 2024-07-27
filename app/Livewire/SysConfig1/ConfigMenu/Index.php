<?php

namespace App\Livewire\SysConfig1\ConfigMenu;

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
