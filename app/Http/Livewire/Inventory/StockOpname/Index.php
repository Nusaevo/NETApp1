<?php

namespace App\Http\Livewire\Inventory\StockOpname;

use App\Models\CategoryItem;
use Livewire\Component;
use App\Models\Warehouse;
use App\Models\ItemWarehouse;
use App\Models\Item;
use App\Traits\LivewireTrait;
use Illuminate\Database\Eloquent\Builder;

class Index extends Component
{
    use LivewireTrait;
    public $inputs = [];
    public $items = [];
    public $item_warehouses = [];
    public $item_warehouse_qty = [];
    public $item_warehouse_qty_defect = [];
    public $category;
    public $warehouse;

    public function mount()
    {
        $this->inputs['name'] = '';
        $this->inputs['category_item_id'] = 0;
        $this->inputs['warehouse_id'] = 0;
        $this->category = CategoryItem::orderByName()->get();
        $this->warehouse = Warehouse::orderByName()->get();
    }
    public function render()
    {
        return view('livewire.inventory.stock-opname.index');
    }

    protected $listeners = [
        'master_item_warehouse_edit_mode'     => 'setEditMode',
        'master_item_warehouse_search'        => 'search',
        'master_item_warehouse_edit'          => 'edit',
        'master_item_warehouse_update'        => 'update',
        'master_item_warehouse_bulk_update'   => 'bulkUpdate'
    ];

    protected $rules = [
        'item_warehouse_qty_defect.*'          => 'numeric|min:0|max:9999999999.99',
        'item_warehouse_qty.*'          => 'numeric|min:0|max:9999999999.99'
    ];

    protected $messages = [
        '*.required' => ':attribute wajib di-isi.',
        '*.numeric'  => ':attribute harus berupa numerik.',
        '*.min'      => ':attribute tidak boleh kurang dari :min.',
        '*.max'      => ':attribute tidak boleh lebih dari :max.',
    ];

    protected $validationAttributes = [
        'item_warehouse_qty_defect.*'   => 'Qty',
        'item_warehouse_qty.*'          => 'Qty Defect'
    ];

    public function search()
    {
        $category_id = $this->inputs['category_item_id'];
        $warehouse_id = $this->inputs['warehouse_id'];
        $keyword = strtolower($this->inputs['name']);

        $this->item_warehouses = ItemWarehouse::leftJoin('item_units', 'item_units.id', '=', 'item_warehouses.item_unit_id')
            ->leftJoin('items', 'items.id', '=', 'item_units.item_id')
            ->leftJoin('units', 'units.id', '=', 'item_units.unit_id')
            ->leftJoin('category_items', 'category_items.id', '=', 'items.category_item_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'item_warehouses.warehouse_id')
            ->when($category_id, function (Builder $query) use ($category_id) {
                $query->where('category_items.id', $category_id);
            })
            ->when($warehouse_id, function (Builder $query) use ($warehouse_id) {
                $query->where('warehouses.id', $warehouse_id);
            })
            ->when($keyword, function (Builder $query) use ($keyword) {
                $query->whereRaw('LCASE(items.name) like ' . '"%' . $keyword . '%"');
            })
            ->select('item_warehouses.id as id', 'items.name as item_name', 'units.name as unit_name', 'category_items.name as category_name', 'warehouses.name as warehouse_name', 'item_warehouses.qty as qty', 'item_warehouses.qty_defect as qty_defect')
            ->get();

        foreach ($this->item_warehouses as $item) {
            if ($item->qty > 0) {
                $this->item_warehouse_qty[$item->id] = qty($item->qty);
            }
            if ($item->qty_defect > 0) {
                $this->item_warehouse_qty_defect[$item->id] = qty($item->qty_defect);
            }
        }
    }
    public function edit($id)
    {
        $this->item = Item::findOrFail($id);
        $this->inputs['name'] = $this->item->name;
        $this->inputs['category_item_id'] = $this->item->category_item_id;
        $this->inputs['warehouse_id'] = $this->item->warehouse_id;
        $this->setEditMode(true);
    }

    public function store()
    {
        $this->validate();

        foreach ($this->item_warehouses as $item) {
            if (isset($this->item_warehouse_qty[$item->id])) {
                ItemWarehouse::findOrFail($item->id)->update([
                    'qty' => $this->item_warehouse_qty[$item->id],
                    'qty_defect' => $this->item_warehouse_qty_defect[$item->id]
                ]);
            }
        }
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengupdate qty dan qty defect"]);
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
