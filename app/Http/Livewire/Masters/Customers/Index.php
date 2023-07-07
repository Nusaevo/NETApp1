<?php

namespace App\Http\Livewire\Masters\Customers;

use Livewire\Component;
use App\Models\Customer;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $customer;
    public $is_edit_mode = false;

    public function mount()
    {
        $this->customer = new Customer();
    }

    public function render()
    {
        return view('livewire.masters.customers.index');
    }

    protected $listeners = [
        'master_customer_edit_mode'     => 'setEditMode',
        'master_customer_edit'          => 'edit',
        'master_customer_delete'        => 'delete',
        'master_customer_destroy'       => 'destroy'
    ];

    protected function rules()
    {
        $uniqueRule = $this->is_edit_mode ? "unique:customers,object_name" : 'unique:customers,object_name';

        return [
            'customer.object_name' => 'required|string|min:1|max:128|' . $uniqueRule,
            'customer.email'       => 'nullable|email|min:3|max:128',
            'customer.phone'       => 'nullable|string|min:3|max:128',
            'customer.address'     => 'nullable|string|min:3|max:128',
        ];
    }

    protected $messages = [
        'customer.object_name.required' => 'Nama customer harus diisi.',
        'customer.object_name.string'   => 'Nama customer harus berupa teks.',
        'customer.object_name.min'      => 'Nama customer tidak boleh kurang dari :min karakter.',
        'customer.object_name.max'      => 'Nama customer tidak boleh lebih dari :max karakter.',
        'customer.object_name.unique'   => 'Nama customer sudah ada.',
        'customer.email.email'          => 'Format email tidak valid.',
        'customer.email.min'            => 'Email tidak boleh kurang dari :min karakter.',
        'customer.email.max'            => 'Email tidak boleh lebih dari :max karakter.',
        'customer.phone.string'         => 'Nomor telepon harus berupa teks.',
        'customer.phone.min'            => 'Nomor telepon tidak boleh kurang dari :min karakter.',
        'customer.phone.max'            => 'Nomor telepon tidak boleh lebih dari :max karakter.',
        'customer.address.string'       => 'Alamat harus berupa teks.',
        'customer.address.min'          => 'Alamat tidak boleh kurang dari :min karakter.',
        'customer.address.max'          => 'Alamat tidak boleh lebih dari :max karakter.',
    ];

    protected $validationAttributes = [
        'customer.object_name' => 'Nama customer',
        'customer.email'       => 'Email',
        'customer.phone'       => 'Nomor Telepon',
        'customer.address'     => 'Alamat',
    ];

    public function store()
    {
        $this->validate();
        Customer::create([
            'object_name' => $this->customer->object_name,
            'email'       => $this->customer->email,
            'phone'       => $this->customer->phone,
            'address'     => $this->customer->address,
        ]);

        $this->dispatchBrowserEvent('notify-swal', [
            'type'    => 'success',
            'title'   => 'Berhasil',
            'message' => "Berhasil menambah customer {$this->customer->object_name}."
        ]);

        $this->emit('master_customer_refresh');
        $this->reset('customer');
    }

    public function edit($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->is_edit_mode = true;
    }

    public function update()
    {
        $this->validate();
        $this->customer->update([
            'object_name' => $this->customer->object_name,
            'email'       => $this->customer->email,
            'phone'       => $this->customer->phone,
            'address'     => $this->customer->address,
        ]);

        $this->is_edit_mode = false;
        $this->dispatchBrowserEvent('notify-swal', [
            'type'    => 'success',
            'title'   => 'Berhasil',
            'message' => "Berhasil mengubah customer {$this->customer->object_name}."
        ]);

        $this->emit('master_customer_refresh');
        $this->reset('customer');
    }

    public function delete($id)
    {
        $this->is_edit_mode = false;
        $this->customer = Customer::findOrFail($id);
    }

    public function destroy()
    {
        $this->customer->delete();
        $this->dispatchBrowserEvent('notify-swal', [
            'type'    => 'success',
            'title'   => 'Berhasil',
            'message' => "Berhasil menghapus customer {$this->customer->object_name}."
        ]);

        $this->emit('master_customer_refresh');
    }
}
