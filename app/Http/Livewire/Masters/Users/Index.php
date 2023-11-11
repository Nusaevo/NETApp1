<?php

namespace App\Http\Livewire\Masters\Users;

use App\Models\User;
use App\Traits\LivewireTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use LivewireTrait;
    use AuthorizesRequests;

    public $user;
    public $purposes = ['owner' => 'Pemilik Sistem', 'warehouse' => 'Admin Gudang', 'transaction' => 'Admin Transaksi', 'admin' => 'Administrasi Umum'];
    public $inputs = ['name' => '', 'purpose' => 'admin', 'email' => '', 'password' => '', 'password_confirmation' => ''];
    public $users;

    public function mount()
    {
        $this->authorize('user.access');
    }

    public function render()
    {
        return view('livewire.masters.users.index');
    }

    protected $listeners = [
        'master_user_edit_mode' => 'setEditMode',
        'master_user_edit'      => 'edit',
        'master_user_delete'    => 'delete',
        'master_user_destroy'   => 'destroy'
    ];

    protected function rules()
    {
        $_unique_exception = $this->is_edit_mode ? ','.$this->user->id : '';
        return [
            'inputs.name'      => 'required|string|min:3|max:128',
            'inputs.email'     => 'required|string|email|min:5|max:128|unique:users,email'. $_unique_exception,
            'inputs.purpose'   => 'required|in:'.implode(',', array_keys($this->purposes)),
            'inputs.password'  => $this->is_edit_mode ? 'nullable' : 'required' . '|string|confirmed|min:6|max:128',
        ];
    }

    protected $messages = [
        'inputs.*.required'  => ':attribute harus di-isi.',
        'inputs.*.string'    => ':attribute harus berupa teks.',
        'inputs.*.min'       => ':attribute tidak boleh kurang dari :min karakter.',
        'inputs.*.max'       => ':attribute tidak boleh lebih dari :max karakter.',
        'inputs.*.unique'    => ':attribute sudah ada pada pengguna lain.',
        'inputs.*.email'     => ':attribute harus berupa format surat elektronik.',
        'inputs.*.in'        => ':attribute harus diantara :values.',
        'inputs.*.confirmed' => 'Sandi dengan konfirmasi tidak sama.',
    ];

    protected $validationAttributes = [
        'inputs.name'      => 'Nama Akun',
        'inputs.email'     => 'Surat Elektronik Akun',
        'inputs.purpose'   => 'Kegunaan Akun',
        'inputs.password'  => 'Sandi Akun',
    ];

    public function store()
    {
        $this->authorize('user.store');
        $this->validate();
        $user = User::create([
            'name'     => $this->inputs['name'],
            'purpose'  => $this->inputs['purpose'],
            'email'    => $this->inputs['email'],
            'password' => bcrypt($this->inputs['password']),
        ]);

        $user->assignRole($this->inputs['purpose']);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil nenambah akun {$this->inputs['name']}."]);
        $this->emit('master_user_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->authorize('user.update');
        $this->user = User::findOrFail($id);
        $this->inputs['name']      = $this->user->name;
        $this->inputs['purpose']   = $this->user->use;
        $this->inputs['email']     = $this->user->email;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->authorize('user.update');
        $this->validate();

        $this->user->update([
            'name'      => $this->inputs['name'],
            'purpose'   => $this->inputs['purpose'],
            'email'     => $this->inputs['email'],
            'password'  => bcrypt($this->inputs['password']),
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' => "Berhasil mengubah akun {$this->user->name}."]);
        $this->emit('master_user_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->authorize('user.destoy');
        $this->setEditMode(false);
        $this->user = User::findOrFail($id);
    }

    public function destroy()
    {
        $this->authorize('user.destoy');
        $this->user->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' => "Berhasil menghapus akun {$this->user->name}."]);
        $this->emit('master_user_refresh');
    }
}
