<?php

namespace App\Http\Livewire\Masters\Stores;

use Livewire\Component;
use App\Models\Store;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $store;
    public $inputs = ['name' => '', 'purpose' => 'is_out'];

    public function render()
    {
        return view('livewire.masters.stores.index');
    }

    protected $listeners = [
        'master_store_edit_mode'     => 'setEditMode',
        'master_store_edit'          => 'edit',
        'master_store_delete'        => 'delete',
        'master_store_destroy'       => 'destroy'
    ];

    protected $rules = [
        'inputs.name'           => 'required|string|min:1|max:128|unique:stores,name',
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
        Store::create([
            'name' => $this->inputs['name'],
            'purpose' =>  $this->inputs['purpose']
        ]);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menambah gudang {$this->inputs['name']}."]);
        $this->emit('master_store_idt_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->store = Store::findOrFail($id);
        $this->inputs['name'] = $this->store->name;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->store->update([
            'name' => $this->inputs['name'],
            'purpose' =>  $this->inputs['purpose']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengubah toko {$this->store->name}."]);
        $this->emit('master_store_idt_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->store = Store::findOrFail($id);
    }

    public function destroy()
    {
        $this->store->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menghapus toko {$this->store->name}."]);
        $this->emit('master_store_idt_refresh');
    }
}
