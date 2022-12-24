<?php

namespace App\Http\Livewire\Masters\Items\Details;

use App\Models\Unit;
use App\Models\Item;
use App\Models\ItemPrice;
use App\Models\ItemUnit;
use Livewire\Component;
use App\Traits\LivewireTrait;
use App\Models\Warehouse;
use App\Models\ItemWarehouse;
use App\Models\PriceCategory;

class ItemUnitDetail extends Component
{
    use LivewireTrait;

    public $item;
    public $list_item_unit;
    public $unit_from;
    public $unit_to;
    public $inputs = [];

    public function mount(Item $item)
    {
        $this->item = Item::findOrFail($item->id);
        $this->list_item_unit = ItemUnit::ItemId($item->id)->get();
        $this->unit_from = Unit::orderByName()->get();
        $this->unit_to = Unit::orderByName()->get();
        $this->inputs['item_id']=$item->id;
    }

    protected $listeners = [
        'master_item_unit_detail_edit_mode'     => 'setEditMode',
        'master_item_unit_detail_show'          => 'show',
        'master_item_unit_detail_edit'          => 'edit',
        'master_item_unit_detail_delete'        => 'delete',
        'master_item_unit_detail_destroy'       => 'destroy',
        'master_item_unit_detail_refresh'       => 'refresh'
    ];

    public function refresh()
    {
        $this->list_item_unit = ItemUnit::ItemId($this->item->id)->get();
        $this->unit_from = Unit::orderByName()->get();
        $this->unit_to = Unit::orderByName()->get();
    }

    protected function rules()
    {
            return [
                'inputs.unit_from'             => 'required|integer',
                'inputs.unit_to'             => 'required|integer',
                'inputs.multiplier'             => 'required|integer'
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
        'inputs.unit_from'           => 'Satuan Awal',
        'inputs.unit_to'           => 'Satuan Akhir',
        'inputs.multiplier'           => 'Pengali'
    ];

    public function render()
    {
        return view('livewire.masters.items.details.itemunitdetail');
    }

    public function store()
    {
        $this->validate();
        $item_id=$this->inputs['item_id'];
        $unit_id=$this->inputs['unit_from'];
        $unit_to=$this->inputs['unit_to'];

        $itemUnit = ItemUnit::IsDuplicate($item_id,$unit_id)->first();
        if($itemUnit!=null)
        {
            $this->dispatchBrowserEvent('notify-swal',['type' => 'error','title' => 'Item dan unit telah dibuat']);
        }
        else{
            // if($unit_id==$unit_to)
            // {
            //     $this->dispatchBrowserEvent('notify-swal',['type' => 'error','title' => 'Satuan tidak boleh sama']);
            // }else
            // {
                $itemUnit =ItemUnit::create([
                    'item_id' => $this->inputs['item_id'],
                    'unit_id' => $unit_id,
                    'to_unit_id' => $unit_to ?? 0,
                    'multiplier'=> $this->inputs['multiplier'] ?? 0,
                ]);
                $warehouse = Warehouse::all();
                foreach($warehouse as $warehouse)
                {
                    ItemWarehouse::firstOrCreate(
                        [
                            'item_unit_id' =>  $itemUnit->id,
                            'warehouse_id' => $warehouse->id ,
                            'qty' =>  999,
                            'qty_defect' => 0,
                        ]
                    );
                }
                $priceCategory = PriceCategory::all();
                foreach($priceCategory as $priceCategory)
                {
                    ItemPrice::firstOrCreate(
                        [
                            'price' => 0,
                            'item_unit_id' =>  $itemUnit->id,
                            'price_category_id' => $priceCategory->id
                        ]
                    );
                }
                $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menambah Item Unit Baru"]);
                $this->emit('master_item_unit_detail_refresh');
                $this->reset('inputs');
                $this->inputs['item_id']= $item_id;
            //}
        }
    }
    public function edit($id)
    {
        $this->item_unit = ItemUnit::findOrFail($id);

        // if($this->item_unit->unit_id == 1)
        // {
        //     $this->dispatchBrowserEvent('notify-swal',['type' => 'error','title' => 'Satuan pcs tidak bisa diedit']);
        // }
        // else
        // {
            $this->item = Item::findOrFail( $this->item_unit->item_id);
            $this->inputs['item_id']     = $this->item_unit->item_id;
            $this->inputs['unit_from']   = $this->item_unit->unit_id;
            $this->inputs['unit_to']     = $this->item_unit->to_unit_id;
            $this->inputs['multiplier']  = $this->item_unit->multiplier;
            $this->setEditMode(true);
        //}
    }
    public function update()
    {
        $this->validate();
        $item_id=$this->inputs['item_id'];
        $unit_id=$this->inputs['unit_from'];
        $unit_to=$this->inputs['unit_to'];
        $itemUnit = ItemUnit::IsDuplicate($item_id,$unit_id)->first();

        if($itemUnit!=null)
        {
            if($itemUnit->id != $this->item_unit->id)
            {
                $this->dispatchBrowserEvent('notify-swal',['type' => 'error','title' => 'Item dan unit telah dibuat']);
            }else{
                $this->item_unit->update([
                    'item_id' => $this->inputs['item_id'],
                    'unit_id' => $unit_id,
                    'to_unit_id' => $unit_to,
                    'multiplier'=> $this->inputs['multiplier'],
                ]);
                $this->setEditMode(false);
                $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengedit Item Unit"]);
                $this->emit('master_item_unit_detail_refresh');
                $this->reset('inputs');
                $this->inputs['item_id']= $item_id;
            }
        }
        else{
            // if($unit_id==$unit_to)
            // {
            //     $this->dispatchBrowserEvent('notify-swal',['type' => 'error','title' => 'Satuan tidak boleh sama']);
            // }else
            // {
                $this->item_unit->update([
                    'item_id' => $this->inputs['item_id'],
                    'unit_id' => $unit_id,
                    'to_unit_id' => $unit_to,
                    'multiplier'=> $this->inputs['multiplier'],
                ]);
                $this->setEditMode(false);
                $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengedit Item Unit"]);
                $this->emit('master_item_unit_detail_refresh');
                $this->reset('inputs');
                $this->inputs['item_id']= $item_id;
            //}
        }
    }

    public function destroy()
    {
        $this->item_unit->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus item unit ini"]);
        $this->emit('master_item_unit_detail_refresh');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->item_unit = ItemUnit::findOrFail($id);
    }
}
