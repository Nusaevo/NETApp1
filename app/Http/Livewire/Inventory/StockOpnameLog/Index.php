<?php

namespace App\Http\Livewire\Inventory\StockOpnameLog;

use Livewire\Component;
use App\Models\StockOpname;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $Stockopname;

    public function render()
    {
        return view('livewire.inventory.stock-opname-log.index');
    }
}
