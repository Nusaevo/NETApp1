<?php

namespace App\Http\Livewire\Transactions\PurchasesOrders;

use Livewire\Component;
use App\Traits\LivewireTrait;
class Index extends Component
{
    use LivewireTrait;

    public function mount()
    {
    }

    public function render()
    {
        return view('livewire.transactions.purchases-orders.index');
    }
}
