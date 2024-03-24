<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\Transfers;

use Livewire\Component;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;

    public $transfer;
    public $warehouses;
    public $inputs = ['transfer_date' => '', 'origin_id' => '', 'destination_id' => ''];

    public function render()
    {
        $this->warehouses = Warehouse::all();
        return view('livewire.transaction.transfers.index');
    }

    protected $listeners = [
        'transaction_transfer_edit_mode'     => 'setEditMode',
        'transaction_transfer_show'          => 'show',
        'transaction_transfer_edit'          => 'edit',
        'transaction_transfer_delete'        => 'delete',
        'transaction_transfer_destroy'       => 'destroy'
    ];

    protected $rules = [
        'inputs.transfer_date'  => 'required|date',
        'inputs.origin_id'      => 'required|exists:warehouses,id',
        'inputs.destination_id' => 'required|exists:warehouses,id',
    ];


    protected $messages = [
        'inputs.*.required'       => ':attribute harus diisi.',
        'inputs.*.date'           => ':attribute harus tanggal.',
        'inputs.*.exists'         => ':attribute tidak ada di master'
    ];

    protected $validationAttributes = [
        'inputs.transfer_date'        => 'Tanggal Transfer',
        'inputs.origin_id'            => 'Asal Transfer',
        'inputs.destination_id'       => 'Tujuan Transfer'
    ];

    public function store()
    {
        $this->validate();
        Transfer::create([
            'transfer_date' => $this->inputs['transfer_date'],
            'origin_id'=> $this->inputs['origin_id'],
            'destination_id'=> $this->inputs['destination_id']
        ]);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menyimpan transfer."]);
        $this->emit('transaction_transfer_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->transfer = Transfer::findOrFail($id);
        $this->inputs['transfer_date'] = $this->transfer->transfer_date;
        $this->inputs['origin_id'] = $this->transfer->origin_id;
        $this->inputs['destination_id'] = $this->transfer->destination_id;
        $this->setEditMode(true);
    }

    public function show($id)
    {
        return redirect('/transfer/'.$id);
    }

    public function update()
    {
        $this->validate();

        $this->transfer->update([
            'transfer_date' => $this->inputs['transfer_date'],
            'origin_id'=> $this->inputs['origin_id'],
            'destination_id'=> $this->inputs['destination_id']
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah transfer."]);
        $this->emit('transaction_transfer_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->transfer = Transfer::findOrFail($id);
    }

    public function destroy()
    {
        $this->transfer->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus transfer."]);
        $this->emit('transaction_transfer_refresh');
    }

}
