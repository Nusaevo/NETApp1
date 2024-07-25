<?php

namespace App\Livewire\SysConfig1\AccountSetting;

use App\Livewire\Component\BaseComponent;
use App\Livewire\SysConfig1\ConfigUser\Detail as ConfigUserEditPage;

class Detail extends ConfigUserEditPage
{
    protected function onPreRender()
    {
        $this->bypassPermissions = true;
    }

    public function render()
    {
        return view("livewire.sys-config1.config-user.detail");
    }
}
