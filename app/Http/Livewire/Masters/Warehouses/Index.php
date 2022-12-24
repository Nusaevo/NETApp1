<?php

namespace App\Http\Livewire\Masters\Warehouses;

use Livewire\Component;
use App\Models\Warehouse;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $warehouse;
    public $inputs = ['name' => '', 'purpose' => 'is_out'];

    public function render()
    {
        return view('livewire.masters.warehouses.index');
    }

    protected $listeners = [
        'master_warehouse_edit_mode'     => 'setEditMode',
        'master_warehouse_edit'          => 'edit',
        'master_warehouse_delete'        => 'delete',
        'master_warehouse_destroy'       => 'destroy'
    ];

    protected $rules = [
        'inputs.name'           => 'required|string|min:1|max:128|unique:warehouses,name',
        'inputs.purpose'        => 'required|string|in:is_out,is_receive',
    ];

    protected $messages = [
        'inputs.*.required'       => ':attribute harus diisi.',
        'inputs.*.string'         => ':attribute harus berupa teks.',
        'inputs.*.min'            => ':attribute tidak boleh kurang dari :min karakter.',
        'inputs.*.max'            => ':attribute tidak boleh lebih dari :max karakter.',
        'inputs.*.unique'         => ':attribute sudah ada.',
        'inputs.*.in'             => ':attribute tidak diijinkan'
    ];

    protected $validationAttributes = [
        'inputs.name'           => 'Nama gudang',
        'inputs.purpose'        => 'Fungsi gudang'
    ];

    public function store()
    {
        $this->validate();
        Warehouse::create([
            'name' => $this->inputs['name'],
            'purpose'=>  $this->inputs['purpose']
        ]);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menambah gudang {$this->inputs['name']}."]);
        $this->emit('master_warehouse_idt_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->warehouse = Warehouse::findOrFail($id);
        $this->inputs['name'] = $this->warehouse->name;
        $this->inputs['purpose'] = $this->warehouse->purpose;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->warehouse->update([
            'name' => $this->inputs['name'],
            'purpose'=>  $this->inputs['purpose']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah gudang {$this->warehouse->name}."]);
        $this->emit('master_warehouse_idt_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->warehouse = Warehouse::findOrFail($id);
    }

    public function destroy()
    {
        $this->warehouse->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus gudang {$this->warehouse->name}."]);
        $this->emit('master_warehouse_idt_refresh');
    }

}
