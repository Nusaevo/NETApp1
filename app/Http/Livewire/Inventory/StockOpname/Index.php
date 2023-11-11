<?php

namespace App\Http\Livewire\Inventory\StockOpname;

use App\Models\CategoryItem;
use Livewire\Component;
use App\Models\Warehouse;
use App\Models\ItemWarehouse;
use App\Models\Item;
use App\Models\StockOpname;
use App\Traits\LivewireTrait;
use Illuminate\Database\Eloquent\Builder;
use DB;
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
    public $searchButtonClicked = false;

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

        if (trim($keyword) != "" || !empty($warehouse_id) || !empty($category_id)) {
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
                ->selectRaw('IFNULL(item_warehouses.qty, 0) as qty')
                ->selectRaw('IFNULL(item_warehouses.qty_defect, 0) as qty_defect')
                ->get();

            foreach ($this->item_warehouses as $item) {
                if ($item->qty > 0) {
                    $this->item_warehouse_qty[$item->id] = int_qty($item->qty);
                } else {
                    $this->item_warehouse_qty[$item->id] = 0;
                }
                if ($item->qty_defect > 0) {
                    $this->item_warehouse_qty_defect[$item->id] = int_qty($item->qty_defect);
                } else {
                    $this->item_warehouse_qty_defect[$item->id] = 0;
                }
            }
        } else {
            $this->item_warehouses = [];
        }
    }

    public function store()
    {
        $this->validate();
        try {
            DB::beginTransaction(); // Start a database transaction

            foreach ($this->item_warehouses as $item) {
                if (isset($this->item_warehouse_qty[$item->id])) {
                    $itemWarehouse = ItemWarehouse::findOrFail($item->id);
                    $oldQty = $itemWarehouse->qty;
                    $oldQtyDefect = $itemWarehouse->qty_defect;

                    // Update qty dan qty_defect pada item warehouse
                    $itemWarehouse->update([
                        'qty' => $this->item_warehouse_qty[$item->id],
                        'qty_defect' => $this->item_warehouse_qty_defect[$item->id]
                    ]);

                    // Simpan log jika qty atau qty_defect berubah
                    if ($oldQty != $this->item_warehouse_qty[$item->id] || $oldQtyDefect != $this->item_warehouse_qty_defect[$item->id]) {
                        $stockOpname = new StockOpname([
                            'item_warehouse_id' => $itemWarehouse->id,
                            'old_qty' => $oldQty,
                            'new_qty' => $this->item_warehouse_qty[$item->id],
                            'old_qty_defect' => $oldQtyDefect,
                            'new_qty_defect' => $this->item_warehouse_qty_defect[$item->id],
                        ]);
                        $stockOpname->save();
                    }
                }
            }

            DB::commit(); // Commit the database transaction if everything is successful
        } catch (\Exception $e) {
            DB::rollBack(); // Roll back the transaction in case of an error
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Gagal', 'message' => "Error: " . $e->getMessage()]);
        }



        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengupdate qty dan qty defect"]);
        $this->search();
    }
}
