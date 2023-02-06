<?php

namespace App\Http\Livewire\Masters\Customers;


use Livewire\Component;
use App\Models\PriceCategory;
use App\Models\Customer;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $category;
    public $customer;
    public $inputs = ['name' => ''];

    public function mount()
    {
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
        $_unique_exception = $this->is_edit_mode ? ',' . $this->customer->id : '';
        return [
            'inputs.name'             => 'required|string|min:1|max:128|unique:customers,name' . $_unique_exception,
            'inputs.address'           =>  'nullable|string|min:3|max:128',
            'inputs.city'      =>  'nullable|string|min:3|max:128',
            'inputs.npwp'       => 'nullable|integer',
            'inputs.contact_name'       => 'nullable|string|min:3|max:128',
            'inputs.contact_number'       => 'nullable|integer|digits_between:9,14',
            'inputs.email'           =>  'nullable|string|min:3|max:128'
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
        'inputs.name'           => 'Nama customer',
        'inputs.address'           => 'Alamat',
        'inputs.city'      => 'Nama Kota',
        'inputs.npwp'       => 'No NPWP',
        'inputs.contact_name'       => 'Nama Kontak',
        'inputs.contact_number'       => 'No Kontak',
        'inputs.email'       => 'Email'

    ];

    public function store()
    {
        $this->validate();
        Customer::create([
            'name' => $this->inputs['name'],
            'address' => $this->inputs['address'] ?? null,
            'city' => $this->inputs['city'] ?? null,
            'npwp' => $this->inputs['npwp'] ?? null,
            'contact_name' => $this->inputs['contact_name'] ?? null,
            'email' => $this->inputs['email'] ?? null
        ]);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menambah customer {$this->inputs['name']}."]);
        $this->emit('master_customer_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->customer = Customer::findOrFail($id);
        $this->inputs['name'] = $this->customer->name;
        $this->inputs['contact_name'] = $this->customer->contact_name;
        $this->inputs['contact_number'] = $this->customer->contact_number;
        $this->inputs['address'] = $this->customer->address;
        $this->inputs['city'] = $this->customer->city;
        $this->inputs['npwp'] = $this->customer->npwp;
        $this->inputs['price_category_id'] = $this->customer->price_category_id;
        $this->inputs['email'] = $this->customer->email;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();
        $this->customer->update([
            'name' => $this->inputs['name'],
            'contact_name' => $this->inputs['contact_name'],
            'contact_number' => $this->inputs['contact_number'],
            'address' => $this->inputs['address'],
            'city' => $this->inputs['city'],
            'npwp' => $this->inputs['npwp'],
            'price_category_id' => $this->inputs['price_category_id'],
            'email' => $this->inputs['email']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengubah customer {$this->customer->name}."]);
        $this->emit('master_customer_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->customer = Customer::findOrFail($id);
    }

    public function destroy()
    {
        $this->customer->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengahapus customer {$this->customer->name}."]);
        $this->emit('master_customer_refresh');
    }
}
