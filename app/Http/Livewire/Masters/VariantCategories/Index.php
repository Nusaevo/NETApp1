<?php

namespace App\Http\Livewire\Masters\VariantCategories;

use Livewire\Component;
use App\Models\CategoryVariant;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $categoryVariant;
    public $inputs = ['name' => ''];

    public function render()
    {
        return view('livewire.masters.variant-categories.index');
    }

    protected $listeners = [
        'master_category_variant_edit_mode'     => 'setEditMode',
        'master_category_variant_edit'          => 'edit',
        'master_category_variant_delete'        => 'delete',
        'master_category_variant_destroy'       => 'destroy'
    ];

    protected function rules()
    {
        $_unique_exception = $this->is_edit_mode ? ',' . $this->categoryVariant->id : '';
        return [
            'inputs.name'             => 'required|string|min:1|max:128|unique:category_variants,name' . $_unique_exception
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
        CategoryVariant::create([
            'name' => $this->inputs['name']
        ]);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menambah kategori variant {$this->inputs['name']}."]);
        $this->emit('master_variant_category_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->categoryVariant = CategoryVariant::findOrFail($id);
        $this->inputs['name'] = $this->categoryVariant->name;

        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->categoryVariant->update([
            'name' => $this->inputs['name']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengubah kategori harga {$this->categoryVariant->name}."]);
        $this->emit('master_variant_category_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->categoryVariant = CategoryVariant::findOrFail($id);
    }

    public function destroy()
    {
        $this->categoryVariant->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menghapus kategori harga {$this->categoryVariant->name}."]);
        $this->emit('master_variant_category_refresh');
    }
}
