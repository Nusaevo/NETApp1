<?php

namespace App\Http\Livewire\Settings\ConfigConsts;

use Livewire\Component;
use App\Models\Settings\ConfigConst; // Import the ConfigGroup model
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
    }

    public function render()
    {
        return view('livewire.settings.config-consts.index');
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
        return redirect()->route('config_consts.detail', ['action' => Crypt::encryptString('View'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('config_consts.detail', ['action' => Crypt::encryptString('Edit'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function SelectObject($id)
    {
        $this->object = ConfigConst::findOrFail($id);
    }

    public function Disable()
    {
        try {
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->str1])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->str1, 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
    }
}
