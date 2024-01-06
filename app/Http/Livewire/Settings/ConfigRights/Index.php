<?php

namespace App\Http\Livewire\Settings\ConfigRights;

use Livewire\Component;
use App\Models\Settings\ConfigRight;
use App\Traits\LivewireTrait;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
class Index extends Component
{
    use LivewireTrait;

    public $object;

    public function mount()
    {
        $this->object = ConfigRight::all();
    }

    public function render()
    {
        return view('livewire.settings.config-rights.index');
    }

    protected $listeners = [
        'viewData'  => 'View',
        'editData'  => 'Edit',
        'deleteData'  => 'Delete',
        'disableData'  => 'Disable',
        'selectData'  => 'SelectObject',
    ];

    public function View($id)
    {
        return redirect()->route('config_rights.detail', ['action' => Crypt::encryptString('View'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('config_rights.detail', ['action' => Crypt::encryptString('Edit'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function SelectObject($id)
    {
        $this->object = ConfigRight::findOrFail($id);
    }

    public function Disable()
    {
        try {
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'title' => Lang::get('generic.success.title'),
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'title' => Lang::get('generic.error.title'),
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
    }
}
