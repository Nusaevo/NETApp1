<?php

namespace App\Http\Livewire\Masters\ItemPriceLogs;

use Livewire\Component;
use App\Models\ItemPriceLog;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $ItemPriceLog;

    public function render()
    {
        return view('livewire.masters.item-price-logs.index');
    }
}
