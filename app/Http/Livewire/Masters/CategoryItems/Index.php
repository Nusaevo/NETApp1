<?php

namespace App\Http\Livewire\Masters\CategoryItems;

use Livewire\Component;
use App\Models\CategoryItem;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $categoryItem;
    public $inputs = ['name' => ''];

    public function render()
    {
        return view('livewire.masters.category-items.index');
    }

    protected $listeners = [
        'master_category_item_edit_mode'     => 'setEditMode',
        'master_category_item_edit'          => 'edit',
        'master_category_item_delete'        => 'delete',
        'master_category_item_destroy'       => 'destroy'
    ];

    protected function rules()
    {
            $_unique_exception = $this->is_edit_mode ? ','.$this->categoryItem->id : '';
            return [
                'inputs.name'             => 'required|string|min:1|max:128|unique:category_items,name'. $_unique_exception
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
        'inputs.name'           => 'Nama Kategori Barang'
    ];

    public function store()
    {
        $this->validate();
        CategoryItem::create([
            'name' => $this->inputs['name']
        ]);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menambah kategori barang {$this->inputs['name']}."]);
        $this->emit('master_category_item_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->categoryItem = CategoryItem::findOrFail($id);
        $this->inputs['name'] = $this->categoryItem->name;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->categoryItem->update([
            'name' => $this->inputs['name']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah kategori barang {$this->categoryItem->name}."]);
        $this->emit('master_category_item_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->categoryItem = CategoryItem::findOrFail($id);
    }

    public function destroy()
    {
        $this->categoryItem->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus kategori barang {$this->categoryItem->name}."]);
        $this->emit('master_category_item_refresh');
    }

}
