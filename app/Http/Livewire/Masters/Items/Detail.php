<?php

namespace App\Http\Livewire\Masters\Items;

use App\Models\Item;
use Livewire\Component;

class Detail extends Component
{
    public $item;
    public function mount($id)
    {
        $this->item = Item::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.masters.items.detail');
    }

    protected $listeners = ['master_item_detail_refresh' => 'refresh'];

    public function refresh()
    {
        $this->item->refresh();
    }
}
