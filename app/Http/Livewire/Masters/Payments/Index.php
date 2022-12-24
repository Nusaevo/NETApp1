<?php

namespace App\Http\Livewire\Masters\Payments;

use Livewire\Component;
use App\Models\Payment;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $payment;
    public $inputs = ['name' => ''];

    public function render()
    {
        return view('livewire.masters.payments.index');
    }

    protected $listeners = [
        'master_payment_edit_mode'     => 'setEditMode',
        'master_payment_edit'          => 'edit',
        'master_payment_delete'        => 'delete',
        'master_payment_destroy'       => 'destroy'
    ];

    protected function rules()
    {
        $_unique_exception = $this->is_edit_mode ? ',' . $this->payment->id : '';
        return [
            'inputs.name'             => 'required|string|min:1|max:128|unique:payments,name' . $_unique_exception
        ];
    }
    protected $messages = [
        'inputs.*.required'       => ':attribute harus diisi.',
        'inputs.*.string'         => ':attribute harus berupa teks.',
        'inputs.*.min'            => ':attribute tidak boleh kurang dari :min karakter.',
        'inputs.*.max'            => ':attribute tidak boleh lebih dari :max karakter.',
        'inputs.*.unique'         => ':attribute sudah ada.'
    ];

    protected $validationAttributes = [
        'inputs.name'           => 'Nama payment'
    ];

    public function store()
    {
        $this->validate();
        payment::create([
            'name' => $this->inputs['name']
        ]);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menambah payment {$this->inputs['name']}."]);
        $this->emit('master_payment_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->payment = payment::findOrFail($id);
        $this->inputs['name'] = $this->payment->name;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->payment->update([
            'name' => $this->inputs['name']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengubah payment {$this->payment->name}."]);
        $this->emit('master_payment_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->payment = payment::findOrFail($id);
    }

    public function destroy()
    {
        $this->payment->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil menghapus payment {$this->payment->name}."]);
        $this->emit('master_payment_refresh');
    }
}
