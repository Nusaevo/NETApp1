<?php

namespace App\Http\Livewire\Masters\Customers;

use Livewire\Component;
use App\Models\Masters\Partner;
use App\Traits\LivewireTrait;
class Index extends Component
{
    use LivewireTrait;

    public function mount()
    {
    }

    public function render()
    {
        return view('livewire.masters.customers.index');
    }
}
