<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\CartOrder;

use Livewire\Component;

class Index extends Component
{
    public function mount()
    {
        return redirect()->route('TrdJewel1.Transaction.CartOrder.Detail', ['action' => encryptWithSessionKey('Create')]);
    }

    public function render()
    {

    }
}
