<?php

namespace App\Http\Livewire\Settings\Users;

use Livewire\Component;
use App\Models\User; // Import the User model
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $user;

    public function mount()
    {

    }

    public function render()
    {
        return view('livewire.settings.users.index'); // Update the view path
    }

    protected $listeners = [
        'settings_user_edit'  => 'edit',
        'settings_user_delete'  => 'delete',
        'settings_user_detail'  => 'view',
    ];


    public function view($id)
    {
        return redirect()->route('users.detail', ['action' => 'View', 'objectId' => $id]);
    }

    public function edit($id)
    {
        return redirect()->route('users.detail', ['action' => 'Edit', 'objectId' => $id]);
    }

    public function delete($id)
    {
        $this->user = User::findOrFail($id);
        $this->user->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengahapus user {$this->user->id}."]);
        $this->emit('settings_user_refresh');
    }
}
