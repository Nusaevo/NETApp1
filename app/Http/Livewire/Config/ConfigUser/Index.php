<?php

namespace App\Http\Livewire\Config\ConfigUser;

use App\Http\Livewire\Component\BaseComponent;
class Index extends BaseComponent
{
    public function render()
    {
        return view($this->renderRoute);
    }
}
