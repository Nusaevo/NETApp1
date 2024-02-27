<?php

namespace App\Http\Livewire\Settings\ConfigGroups;

use Livewire\Component;
use App\Models\Settings\ConfigGroup;
use App\Traits\LivewireTrait;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
class Index extends Component
{
    use LivewireTrait;

    public function mount()
    {
    }

    public function render()
    {
        return view('livewire.settings.config-groups.index');
    }
}
