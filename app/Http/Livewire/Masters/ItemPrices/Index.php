<?php

namespace App\Http\Livewire\Masters\ItemPrices;

use App\Models\CategoryItem;
use Livewire\Component;
use App\Models\PriceCategory;
use App\Models\ItemPrice;
use App\Models\Item;
use App\Traits\LivewireTrait;
use Illuminate\Database\Eloquent\Builder;

class Index extends Component
{
    use LivewireTrait;

    public $inputs = [];
    public $items = [];
    public $item_price = [];
    public $items_amount = [];

    public function mount()
    {
        $this->inputs['name'] = '';
        $this->inputs['category_item_id'] = 0;
        $this->category = CategoryItem::orderByName()->get();
    }
    public function render()
    {
        return view('livewire.masters.item-prices.index');
    }

    protected $listeners = [
        'master_item_price_edit_mode'     => 'setEditMode',
        'master_item_price_search'        => 'search',
        'master_item_price_edit'          => 'edit',
        'master_item_price_update'        => 'update',
        'master_item_price_bulk_update'        => 'bulkUpdate'
    ];

    protected $rules = [
        'items_amount.*'          => 'numeric|min:0|max:9999999999.99'
    ];

    protected $messages = [
        '*.required' => ':attribute wajib di-isi.',
        '*.numeric'  => ':attribute harus berupa numerik.',
        '*.min'      => ':attribute tidak boleh kurang dari :min.',
        '*.max'      => ':attribute tidak boleh lebih dari :max.',
    ];

    protected $validationAttributes = [
        'items_amount.*'          => 'Harga'
    ];

    public function search()
    {
        $category_id = $this->inputs['category_item_id'];
        $keyword = strtolower($this->inputs['name']);

        $this->item_price = ItemPrice::leftJoin('item_units', 'item_units.id', '=', 'item_prices.item_unit_id')
            ->leftJoin('items', 'items.id', '=', 'item_units.item_id')
            ->leftJoin('units', 'units.id', '=', 'item_units.unit_id')
            ->leftJoin('category_items', 'category_items.id', '=', 'items.category_item_id')
            ->leftJoin('price_categories', 'price_categories.id', '=', 'item_prices.price_category_id')
            ->when($category_id, function (Builder $query) use ($category_id) {
                $query->where('category_items.id', $category_id);
            })
            ->when($keyword, function (Builder $query) use ($keyword) {
                $query->whereRaw('LCASE(items.name) like ' . '"%' . $keyword . '%"');
            })
            ->select('item_prices.id as id', 'items.name as item_name', 'units.name as unit_name', 'category_items.name as category_name', 'price_categories.name as price_category_name', 'item_prices.price as price')
            ->get();
        foreach ($this->item_price as $item) {
            if ($item->price > 0) {
                $this->items_amount[$item->id] = round($item->price, 0);
            }
        }
    }
    public function edit($id)
    {
        $this->item = Item::findOrFail($id);
        $this->inputs['name'] = $this->item->name;
        $this->inputs['category_item_id'] = $this->item->category_item_id;
        $this->setEditMode(true);
    }

    public function store()
    {
        $this->validate();

        foreach ($this->item_price as $item) {
            if (isset($this->items_amount[$item->id])) {
                ItemPrice::findOrFail($item->id)->update([
                    'price' => $this->items_amount[$item->id]
                ]);
            }
        }
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengupdate harga"]);
        $this->search();
    }

    // public function delete($id)
    // {
    //     $this->setEditMode(false);
    //     $this->item = Item::findOrFail($id);
    // }

    // public function destroy()
    // {
    //     $this->item->delete();
    //     $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus item <b>{$this->item->name}.<b>"]);
    //     $this->emit('master_item_refresh');
    // }

}
