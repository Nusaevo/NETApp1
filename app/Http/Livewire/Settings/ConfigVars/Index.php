<?php

namespace App\Http\Livewire\Settings\ConfigVars;

use Livewire\Component;
use App\Models\Settings\ConfigVar; // Import the ConfigGroup model
use App\Traits\LivewireTrait;
class Index extends Component
{
    use LivewireTrait;

    public function mount()
    {
    }

    public function render()
    {
        return view('livewire.settings.config-vars.index');
    }
}
