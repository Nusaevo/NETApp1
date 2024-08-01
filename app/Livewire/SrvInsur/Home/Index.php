<?php

namespace App\Livewire\SrvInsur\Home;
use App\Livewire\Component\BaseComponent;

class Index extends BaseComponent
{
    protected function onPreRender()
    {
        $this->bypassPermissions = true;
    }

    public function render()
    {
        return view('livewire.index');
    }
}
