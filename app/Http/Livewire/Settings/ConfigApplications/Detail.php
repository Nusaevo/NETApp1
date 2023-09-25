<?php

namespace App\Http\Livewire\Settings\ConfigApplications;

use Livewire\Component;
use App\Models\ConfigGroup;
use App\Traits\LivewireTrait;

class Detail extends Component
{
    use LivewireTrait;

    public $configGroup;
    public $action = 'Create'; // Default to Create
    public $objectId;
    public $inputs = [];

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;

        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->configGroup = ConfigGroup::find($this->objectId);
            $this->inputs['appl_code'] = $this->configGroup->appl_code;
            $this->inputs['group_code'] = $this->configGroup->group_code;
            $this->inputs['user_code'] = $this->configGroup->user_code;
            $this->inputs['note1'] = $this->configGroup->note1;
            $this->inputs['status_code'] = $this->configGroup->status_code;
            $this->inputs['is_active'] = $this->configGroup->is_active;
            // Add other fields as needed
        } else {
            $this->configGroup = new ConfigGroup();
        }
    }

    protected function rules()
    {
        $_unique_exception = $this->action === 'Edit' ? ',' . $this->objectId : '';

        return [
            'inputs.appl_code' => 'required|string|max:20',
            'inputs.group_code' => 'required|string|max:50',
            'inputs.user_code' => 'required|string|max:50',
            'inputs.note1' => 'required|string|max:200',
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
        'inputs.group_code' => 'Group Code',
        'inputs.user_code' => 'User Code',
        'inputs.note1' => 'Note 1',
        'inputs.status_code' => 'Status Code',
        'inputs.is_active' => 'Is Active',
        // Add validation attributes for other fields
    ];

    public function store()
    {
        $this->validate();

        if ($this->action === 'Create') {
            ConfigGroup::create($this->inputs);
        }

        $this->reset(['configGroup', 'action', 'objectId']);
    }

    public function update()
    {
        $this->validate();

        if ($this->action === 'Edit' && $this->configGroup) {
            $this->configGroup->update($this->inputs);
        }

        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' => "Berhasil mengubah config group {$this->configGroup->group_code}."]);
        $this->reset(['configGroup', 'action', 'objectId']);
    }

    public function render()
    {
        return view('livewire.settings.config-groups.edit');
    }
}
