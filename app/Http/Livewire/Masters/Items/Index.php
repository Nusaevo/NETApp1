<?php

namespace App\Http\Livewire\Masters\Items;

use Livewire\Component;
use App\Models\CategoryItem;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\Warehouse;
use App\Traits\LivewireTrait;
use App\Models\ItemUnit;
use App\Models\Unit;
use App\Models\ItemPrice;
use App\Models\PriceCategory;
class Index extends Component
{
    use LivewireTrait;

    public $item;
    public $category;
    public $unit;
    public $inputs = ['name' => ''];


    public function mount()
    {
        $this->category = CategoryItem::orderByName()->get();
        $this->unit = Unit::orderByName()->get();
    }

    public function render()
    {
        return view('livewire.masters.items.index');
    }

    protected $listeners = [
        'master_item_edit_mode'     => 'setEditMode',
        'master_item_show'          => 'show',
        'master_item_edit'          => 'edit',
        'master_item_delete'        => 'delete',
        'master_item_destroy'       => 'destroy'
    ];

    public function show($id)
    {
        return redirect()->route('item.detail', $id);
    }
    protected function rules()
    {
            $_unique_exception = $this->is_edit_mode ? ','.$this->item->id : '';
            return [
                'inputs.name'             => 'required|string|min:1|max:128|unique:items,name'. $_unique_exception,
                'inputs.category_item_id'           => 'required|integer'
            ];
    }
    protected $messages = [
        'inputs.*.required'       => ':attribute harus diisi.',
        'inputs.*.string'         => ':attribute harus berupa teks.',
        'inputs.*.min'            => ':attribute tidak boleh kurang dari :min karakter.',
        'inputs.*.max'            => ':attribute tidak boleh lebih dari :max karakter.',
        'inputs.*.unique'         => ':attribute sudah ada.'
    ];

    protected $validationAttributes = [
        'inputs.name'           => 'Nama Item',
        'inputs.category_item_id'           => 'Category'
    ];

    public function store()
    {
        $this->validate();
        $item = Item::create([
            'name' => $this->inputs['name'],
            'category_item_id'=> $this->inputs['category_item_id'],
        ]);
        // $itemUnit = ItemUnit::create([
        //     'item_id' =>  $item->id,
        //     'unit_id' => $this->inputs['unit_item_id'],
        //     'to_unit_id' => $this->inputs['unit_item_id'],
        //     'multiplier'=> 1,
        // ]);
        // $warehouse = Warehouse::all();
        // foreach($warehouse as $warehouse)
        // {
        //     ItemWarehouse::firstOrCreate(
        //         [
        //             'item_unit_id' =>  $itemUnit->id,
        //             'warehouse_id' => $warehouse->id ,
        //             'qty' =>  999,
        //             'qty_defect' => 999,
        //         ]
        //     );
        // }
        // $priceCategory = PriceCategory::all();
        // foreach($priceCategory as $priceCategory)
        // {
        //     ItemPrice::firstOrCreate(
        //         [
        //             'price' => 0,
        //             'item_unit_id' =>  $itemUnit->id,
        //             'price_category_id' => $priceCategory->id
        //         ]
        //     );
        // }
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menambah item <b>{$this->inputs['name']}.<b>"]);
        $this->emit('master_item_refresh');
        $this->reset('inputs');
        return redirect()->route('item.detail',['id'=>$item->id]);
    }

    public function edit($id)
    {
        $this->item = Item::findOrFail($id);
        $this->inputs['name'] = $this->item->name;
        $this->inputs['category_item_id'] = $this->item->category_item_id;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->item->update([
            'name' => $this->inputs['name'],
            'category_item_id' => $this->inputs['category_item_id']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>   "Berhasil mengubah item <b>{$this->inputs['name']}.<b>"]);
        $this->emit('master_item_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->item = Item::findOrFail($id);
    }

    public function destroy()
    {
        $this->item->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus item <b>{$this->item->name}.<b>"]);
        $this->emit('master_item_refresh');
    }

}
