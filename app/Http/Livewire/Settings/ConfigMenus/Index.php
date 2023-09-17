<?php
namespace App\Http\Livewire\Settings\ConfigMenus;

use Livewire\Component;
use App\Models\ConfigMenu;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $configMenus;

    public function mount()
    {
        $this->configMenus = ConfigMenu::all(); // Retrieve all ConfigMenus
    }

    public function render()
    {
        return view('livewire.settings.config-menus.index', [
            'configMenus' => $this->configMenus, // Pass the ConfigMenus data to the view
        ]);
    }

    protected $listeners = [
        'settings_config_menu_edit'  => 'edit',
        'settings_config_menu_delete'  => 'delete',
        'settings_config_menu_detail'  => 'view',
    ];

    public function view($id)
    {
        return redirect()->route('config_menus.detail', ['action' => 'View', 'objectId' => $id]);
    }

    public function edit($id)
    {
        return redirect()->route('config_menus.detail', ['action' => 'Edit', 'objectId' => $id]);
    }

    public function delete($id)
    {
        $configMenu = ConfigMenu::findOrFail($id);
        $configMenu->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menghapus konfigurasi menu {$configMenu->menu_code}."]);
        $this->emit('settings_config_menu_refresh');
    }
}
