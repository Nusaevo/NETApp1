<?php

namespace App\Http\Livewire\Settings\ConfigUsers;

use Livewire\Component;
use App\Models\ConfigUser; // Import the User model
use App\Traits\LivewireTrait;
use Lang;
use Exception;
class Index extends Component
{
    use LivewireTrait;

    public $user;

    public function mount()
    {

    }

    public function render()
    {
        return view('livewire.settings.config-users.index'); // Update the view path
    }

    protected $listeners = [
        'settings_user_detail'  => 'View',
        'settings_user_edit'  => 'Edit',
        'settings_user_delete'  => 'Delete',
        'settings_user_disable'  => 'Disable',
        'settings_user_select'  => 'SelectUser',
    ];


    public function View($id)
    {
        return redirect()->route('config_users.detail', ['action' => 'View', 'objectId' => $id]);
    }

    public function Edit($id)
    {
        return redirect()->route('config_users.detail', ['action' => 'Edit', 'objectId' => $id]);
    }

    public function SelectUser($id)
    {

        $this->user = ConfigUser::findOrFail($id);

    }

    public function Disable()
    {
        try {
            $this->user->updateObject($this->user->VersioNumber);
            $this->user->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'title' => Lang::get('generic.success.title'),
                'message' => Lang::get('generic.success.disable', ['object' => "User " . $this->user->name])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'title' => Lang::get('generic.error.title'),
                'message' => Lang::get('generic.error.disable', ['object' => "User " . $this->user->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('settings_user_refresh');
    }
}
