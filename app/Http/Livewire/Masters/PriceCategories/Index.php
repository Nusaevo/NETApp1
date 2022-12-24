<?php

namespace App\Http\Livewire\Masters\PriceCategories;

use Livewire\Component;
use App\Models\PriceCategory;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $priceCategory;
    public $inputs = ['name' => ''];

    public function render()
    {
        return view('livewire.masters.price-categories.index');
    }

    protected $listeners = [
        'master_price_category_edit_mode'     => 'setEditMode',
        'master_price_category_edit'          => 'edit',
        'master_price_category_delete'        => 'delete',
        'master_price_category_destroy'       => 'destroy'
    ];

    protected function rules()
    {
            $_unique_exception = $this->is_edit_mode ? ','.$this->priceCategory->id : '';
            return [
                'inputs.name'             => 'required|string|min:1|max:128|unique:price_categories,name'. $_unique_exception
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
        'inputs.name'           => 'Nama Kategori Harga'
    ];

    public function store()
    {
        $this->validate();
        PriceCategory::create([
            'name' => $this->inputs['name']
        ]);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menambah kategori harga {$this->inputs['name']}."]);
        $this->emit('master_price_category_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->priceCategory = PriceCategory::findOrFail($id);
        $this->inputs['name'] = $this->priceCategory->name;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->priceCategory->update([
            'name' => $this->inputs['name']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah kategori harga {$this->priceCategory->name}."]);
        $this->emit('master_price_category_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->priceCategory = PriceCategory::findOrFail($id);
    }

    public function destroy()
    {
        $this->priceCategory->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus kategori harga {$this->priceCategory->name}."]);
        $this->emit('master_price_category_refresh');
    }

}
