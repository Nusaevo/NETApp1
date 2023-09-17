<?php

namespace App\Http\Livewire\Settings\Users;

use Livewire\Component;
use App\Models\User;
use App\Traits\LivewireTrait;

class Detail extends Component
{
    use LivewireTrait;

    public $user;
    public $action = 'Create'; // Default to Create
    public $objectId;
    public $inputs = ['name' => ''];

    public function mount($action, $objectId = null)
    {
        $this->action = $action;
        $this->objectId = $objectId;
        if (($this->action === 'Edit' || $this->action === 'View') && $this->objectId) {
            $this->user = User::find($this->objectId);
            $this->inputs['first_name'] = $this->user->first_name;
            $this->inputs['last_name'] = $this->user->last_name;
            $this->inputs['email'] = $this->user->email;
            $this->inputs['company'] = $this->user->info->company;
            $this->inputs['phone'] = $this->user->info->phone;

        } else {
            $this->user = new User();
        }
    }

    // Validation rules and other methods...
    protected function rules()
    {
        $_unique_exception = $this->action === 'Edit' ? ',' . $this->objectId : '';
        return [
            'inputs.first_name' => 'required|string|min:1|max:128',
            'inputs.last_name' => 'required|string|min:1|max:128',
            'inputs.email' => $this->action === 'Create' ? 'required|string|min:3|max:128|unique:users,email' . $_unique_exception : 'nullable|string|min:3|max:128|unique:users,email' . $_unique_exception,
        ];
    }

    protected $messages = [
        'inputs.*.required'       => ':attribute harus diisi.',
        'inputs.*.string'         => ':attribute harus berupa teks.',
        'inputs.*.integer'        => ':attribute harus berupa angka dan tidak ada nol didepan.',
        'inputs.*.min'            => ':attribute tidak boleh kurang dari :min karakter.',
        'inputs.*.max'            => ':attribute tidak boleh lebih dari :max karakter.',
        'inputs.*.unique'         => ':attribute sudah ada.',
        'inputs.*.boolean'        => ':attribute harus Benar atau Salah.',
        'inputs.*.digits_between' => ':attribute harus diantara :min dan :max karakter.',
        'inputs.*.exists'         => ':attribute tidak ada di master'
    ];

    protected $validationAttributes = [
        'inputs.first_name'           => 'First Name',
        'inputs.last_name'           => 'Last Name',
        'inputs.email'       => 'Email'

    ];

    public function store()
    {
        $this->validate();

        if ($this->action === 'Create') {
            User::create([
                'first_name' => $this->inputs['first_name'],
                'last_name' => $this->inputs['last_name'],
                'email' => $this->inputs['email'],
                'password' => bcrypt($this->inputs['password']),
            ]);
        }

        $this->reset(['user', 'action', 'objectId']);
    }


    public function update()
    {
        $this->validate();
        if ($this->action === 'Edit') {
            if ($this->user) {
                $this->user->update([
                    'first_name' => $this->inputs['first_name'],
                    'last_name' => $this->inputs['last_name'],
                    'email' => $this->inputs['email'],
                    'password' => bcrypt($this->inputs['password']),
                ]);
            }
        }
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah user {$this->user->first_name}."]);
        $this->reset(['user', 'action', 'objectId']);
    }


    public function render()
    {
        return view('livewire.settings.users.edit');
    }
}
