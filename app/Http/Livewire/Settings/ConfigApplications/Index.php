<?php

namespace App\Http\Livewire\Settings\ConfigApplications;

use Livewire\Component;
use App\Models\ConfigAppl; // Import the ConfigGroup model
use App\Traits\LivewireTrait;
use Lang;
use Exception;
class Index extends Component
{
    use LivewireTrait;

    public $object;

    public function mount()
    {
        $this->object = ConfigAppl::all();
    }

    public function render()
    {
        return view('livewire.settings.config-applications.index');
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
        return redirect()->route('config_applications.detail', ['action' => 'View', 'objectId' => $id]);
    }

    public function Edit($id)
    {
        return redirect()->route('config_applications.detail', ['action' => 'Edit', 'objectId' => $id]);
    }

    public function SelectObject($id)
    {
        $this->object = ConfigAppl::findOrFail($id);
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
