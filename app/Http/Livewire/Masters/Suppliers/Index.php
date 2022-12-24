<?php

namespace App\Http\Livewire\Masters\Suppliers;

use Livewire\Component;
use App\Models\Supplier;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $supplier;
    public $inputs = ['name' => ''];

    public function render()
    {
        return view('livewire.masters.suppliers.index');
    }

    protected $listeners = [
        'master_supplier_edit_mode'     => 'setEditMode',
        'master_supplier_edit'          => 'edit',
        'master_supplier_delete'        => 'delete',
        'master_supplier_destroy'       => 'destroy'
    ];

    protected function rules()
    {
            $_unique_exception = $this->is_edit_mode ? ','.$this->supplier->id : '';
            return [
                'inputs.name'             => 'required|string|min:1|max:128|unique:suppliers,name'. $_unique_exception,
                'inputs.owner'          => 'nullable|string|min:3|max:128',
                'inputs.phone'          => 'nullable|integer|digits_between:9,14'
            ];
    }
    protected $messages = [
        'inputs.*.required'       => ':attribute harus diisi.',
        'inputs.*.string'         => ':attribute harus berupa teks.',
        'inputs.*.integer'        => ':attribute harus berupa angka dan tidak ada nol didepan.',
        'inputs.*.min'            => ':attribute tidak boleh kurang dari :min karakter.',
        'inputs.*.max'            => ':attribute tidak boleh lebih dari :max karakter.',
        'inputs.*.unique'         => ':attribute sudah ada.',
        'inputs.*.boolean'        => ':attribute harus Benar atau Salah.',
        'inputs.*.digits_between' => ':attribute harus diantara :min dan :max karakter.',
        'inputs.*.exists'         => ':attribute tidak ada di master'
    ];

    protected $validationAttributes = [
        'inputs.name'           => 'Nama Supplier',
        'inputs.contact_name'      => 'Nama Kontak',
        'inputs.contact_number'       => 'No Kontak'
    ];

    public function store()
    {
        $this->validate();
        Supplier::create([
            'name' => $this->inputs['name'],
            'contact_name'=> $this->inputs['contact_name'],
            'contact_number'=> $this->inputs['contact_number']
        ]);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menambah penyuplai {$this->inputs['name']}."]);
        $this->emit('master_supplier_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->supplier = Supplier::findOrFail($id);
        $this->inputs['name'] = $this->supplier->name;
        $this->inputs['contact_name'] = $this->supplier->contact_name;
        $this->inputs['contact_number'] = $this->supplier->contact_number;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->supplier->update([
            'name' => $this->inputs['name'],
            'contact_name'=> $this->inputs['contact_name'],
            'contact_number'=> $this->inputs['contact_number']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah penyuplai {$this->supplier->name}."]);
        $this->emit('master_supplier_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->supplier = Supplier::findOrFail($id);
    }

    public function destroy()
    {
        $this->supplier->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus penyuplai {$this->supplier->name}."]);
        $this->emit('master_supplier_refresh');
    }

}
