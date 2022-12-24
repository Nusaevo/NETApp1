<?php

namespace App\Http\Livewire\Masters\Units;

use Livewire\Component;
use App\Models\Unit;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $unit;
    public $inputs = ['name' => ''];

    public function render()
    {
        return view('livewire.masters.units.index');
    }

    protected $listeners = [
        'master_unit_edit_mode'     => 'setEditMode',
        'master_unit_edit'          => 'edit',
        'master_unit_delete'        => 'delete',
        'master_unit_destroy'       => 'destroy'
    ];

    protected function rules()
    {
            $_unique_exception = $this->is_edit_mode ? ','.$this->unit->id : '';
            return [
                'inputs.name'             => 'required|string|min:1|max:128|unique:units,name'. $_unique_exception
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
        'inputs.name'           => 'Nama Unit'
    ];

    public function store()
    {
        $this->validate();
        Unit::create([
            'name' => $this->inputs['name']
        ]);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menambah unit {$this->inputs['name']}."]);
        $this->emit('master_unit_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->unit = Unit::findOrFail($id);
        $this->inputs['name'] = $this->unit->name;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->unit->update([
            'name' => $this->inputs['name']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah unit {$this->unit->name}."]);
        $this->emit('master_unit_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->unit = Unit::findOrFail($id);
    }

    public function destroy()
    {
        $this->unit->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus unit {$this->unit->name}."]);
        $this->emit('master_unit_refresh');
    }

}
