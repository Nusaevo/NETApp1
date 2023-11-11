<?php

namespace App\Http\Livewire\Masters\Roles;

use App\Traits\LivewireTrait;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use LivewireTrait;

    public $role;
    public $inputs = ['name' => ''];

    public function render()
    {
        return view('livewire.masters.roles.index');
    }

    protected $listeners = [
        'master_role_show'      => 'show',
        'master_role_edit_mode' => 'setEditMode',
        'master_role_edit'      => 'edit',
        'master_role_delete'    => 'delete',
        'master_role_destroy'   => 'destroy'
    ];

    public function show($id)
    {
        if($id == 1) return $this->dispatchBrowserEvent('notify-swal',['type' => 'warning','title' => 'Warning','message' => "Peran ini tidak memiliki detail."]);
        return redirect()->route('role.detail', $id);
    }

    protected function rules()
    {
        $_unique_exception = $this->is_edit_mode ? ','.$this->role->id : '';
        return [
            'inputs.name' => 'required|string|min:1|max:16|unique:roles,name'. $_unique_exception,
        ];
    }

    protected $messages = [
        'inputs.*.required' => ':attribute harus diisi.',
        'inputs.*.string'   => ':attribute harus berupa teks.',
        'inputs.*.min'      => ':attribute tidak boleh kurang dari :min karakter.',
        'inputs.*.max'      => ':attribute tidak boleh lebih dari :max karakter.',
        'inputs.*.unique'   => ':attribute sudah ada.',
    ];

    protected $validationAttributes = [
        'inputs.name' => 'Nama Peran',
    ];

    public function store()
    {
        $this->validate();
        Role::create(['name' => $this->inputs['name']]);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' => "Berhasil nenambah peran {$this->inputs['name']}."]);
        $this->emit('master_role_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->role = Role::findOrFail($id);
        $this->inputs['name'] = $this->role->name;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->role->update(['name' => $this->inputs['name']]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' => "Berhasil menyunting peran {$this->role->name}."]);
        $this->emit('master_role_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->role = Role::findOrFail($id);
    }

    public function destroy()
    {
        $this->role->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' => "Berhasil menghapus peran {$this->role->name}."]);
        $this->emit('master_role_refresh');
    }
}
