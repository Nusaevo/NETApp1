<?php
namespace App\Http\Livewire\Settings\ConfigMenus;

use Livewire\Component;
use App\Models\ConfigMenu;
use App\Traits\LivewireTrait;

class Detail extends Component
{
    use LivewireTrait;

    public $configMenu;
    public $action = 'Create'; // Default to Create
    public $objectId;
    public $inputs = [];

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->configMenu = ConfigMenu::find($this->objectId);
            $this->inputs['appl_code'] = $this->configMenu->appl_code;
            $this->inputs['menu_code'] = $this->configMenu->menu_code;
            $this->inputs['menu_caption'] = $this->configMenu->menu_caption;
            $this->inputs['status_code'] = $this->configMenu->status_code;
            $this->inputs['is_active'] = $this->configMenu->is_active;
            // Add other fields as needed
        } else {
            $this->configMenu = new ConfigMenu();
        }
    }

    protected function rules()
    {
        $_unique_exception = $this->action === 'Edit' ? ',' . $this->objectId : '';

        return [
            'inputs.appl_code' => 'required|string|max:20',
            'inputs.menu_code' => 'required|string|max:50',
            'inputs.menu_caption' => 'required|string|max:200',
            'inputs.status_code' => 'required|string|max:1',
            'inputs.is_active' => 'required|string|max:1',
            // Add validation rules for other fields
        ];
    }

    protected $messages = [
        'inputs.*.required' => ':attribute harus diisi.',
        'inputs.*.string' => ':attribute harus berupa teks.',
        'inputs.*.max' => ':attribute tidak boleh lebih dari :max karakter.',
    ];

    protected $validationAttributes = [
        'inputs.appl_code' => 'Appl Code',
        'inputs.menu_code' => 'Menu Code',
        'inputs.menu_caption' => 'Menu Caption',
        'inputs.status_code' => 'Status Code',
        'inputs.is_active' => 'Is Active',
        // Add validation attributes for other fields
    ];

    public function store()
    {
        $this->validate();

        if ($this->action === 'Create') {
            ConfigMenu::create($this->inputs);
        }

        $this->reset(['configMenu', 'action', 'objectId']);
    }

    public function update()
    {
        $this->validate();

        if ($this->action === 'Edit' && $this->configMenu) {
            $this->configMenu->update($this->inputs);
        }

        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' => "Berhasil mengubah config menu {$this->configMenu->menu_code}."]);
        $this->reset(['configMenu', 'action', 'objectId']);
    }

    public function render()
    {
        return view('livewire.settings.config-menus.edit');
    }
}
