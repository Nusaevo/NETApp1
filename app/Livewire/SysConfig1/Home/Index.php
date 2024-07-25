<?php

namespace App\Livewire\SysConfig1\Home;

use App\Livewire\Component\BaseComponent;

use Livewire\Attributes\Layout;
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
