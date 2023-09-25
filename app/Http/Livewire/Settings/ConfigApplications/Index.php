<?php

namespace App\Http\Livewire\Settings\ConfigApplications;

use Livewire\Component;
use App\Models\ConfigGroup; // Import the ConfigGroup model
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $configGroups;

    public function mount()
    {
        $this->configGroups = ConfigGroup::all(); // Retrieve all ConfigGroups
    }

    public function render()
    {
        return view('livewire.settings.config-groups.index', [
            'configGroups' => $this->configGroups, // Pass the ConfigGroups data to the view
        ]);
    }

    protected $listeners = [
        'settings_config_group_edit'  => 'edit',
        'settings_config_group_delete'  => 'delete',
        'settings_config_group_detail'  => 'view',
    ];

    public function view($id)
    {
        return redirect()->route('config_groups.detail', ['action' => 'View', 'objectId' => $id]);
    }

    public function edit($id)
    {
        return redirect()->route('config_groups.detail', ['action' => 'Edit', 'objectId' => $id]);
    }

    public function delete($id)
    {
        $configGroup = ConfigGroup::findOrFail($id);
        $configGroup->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menghapus konfigurasi grup {$configGroup->group_code}."]);
        $this->emit('settings_config_group_refresh');
    }
}
