<?php

namespace App\Http\Livewire\Masters\Suppliers;

use Livewire\Component;
use App\Models\Masters\Partner; // Import the ConfigGroup model
use App\Traits\LivewireTrait;
class Index extends Component
{
    use LivewireTrait;

    public function mount()
    {
    }

    public function render()
    {
        return view('livewire.masters.suppliers.index');
    }
}
