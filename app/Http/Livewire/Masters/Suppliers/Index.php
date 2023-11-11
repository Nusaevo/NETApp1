<?php

namespace App\Http\Livewire\Masters\Suppliers;

use Livewire\Component;
use App\Models\Partner; // Import the ConfigGroup model
use App\Traits\LivewireTrait;
use Lang;
use Exception;
class Index extends Component
{
    use LivewireTrait;

    public $object;

    public function mount()
    {
    }

    public function render()
    {
        return view('livewire.masters.suppliers.index');
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
        return redirect()->route('suppliers.detail', ['action' => 'View', 'objectId' => $id]);
    }

    public function Edit($id)
    {
        return redirect()->route('suppliers.detail', ['action' => 'Edit', 'objectId' => $id]);
    }

    public function SelectObject($id)
    {
        $this->object = Partner::findOrFail($id);
    }

    public function Disable()
    {
        try {
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
    }
}
