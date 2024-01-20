<?php

namespace App\Http\Livewire\Masters\Materials;

use Livewire\Component;
use App\Models\Material;
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
        return view('livewire.masters.materials.index');
    }
}
