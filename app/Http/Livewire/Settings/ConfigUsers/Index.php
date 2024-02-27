<?php

namespace App\Http\Livewire\Settings\ConfigUsers;

use Livewire\Component;
use App\Traits\LivewireTrait;
class Index extends Component
{
    use LivewireTrait;
    public $object;

    public function mount()
    {

    }

    public function render()
    {
        return view('livewire.settings.config-users.index');
    }
}
