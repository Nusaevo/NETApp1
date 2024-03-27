<?php

namespace App\Http\Livewire\SysConfig1\ConfigApplication;

use App\Http\Livewire\Component\BaseComponent;
class Index extends BaseComponent
{
    public function render()
    {
        return view($this->renderRoute);
    }
}
