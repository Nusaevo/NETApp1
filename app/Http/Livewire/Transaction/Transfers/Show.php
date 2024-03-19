<?php

namespace App\Http\Livewire\Transaction\Transfers;

use Livewire\Component;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Item;
use App\Models\Unit;
use App\Models\ItemUnit;
use App\Traits\LivewireTrait;

class Show extends Component
{
    use LivewireTrait;

    public $items;
    public $units;
    public $remarks;

    public $transfer;
    public $transferItem;
    public $inputs = ['qty' => '0', 'qty_defect' => '0', 'remark' => ''];
    public $i = 1;

    public $itemId;

    public function mount($id){
        $this->transfer = Transfer::findOrFail($id);
        $this->items = Item::all();
        $this->units = collect();
        $this->remarks = ['normal','rusak','cacat'];
        # get items with its unit allowed
    }

    public function render()
    {
        return view('livewire.transaction.transfers.show');
    }

    protected $listeners = [
        'transaction_transfer_show_edit_mode'     => 'setEditMode',
        'transaction_transfer_show_edit'          => 'edit',
        'transaction_transfer_show_delete'        => 'delete',
        'transaction_transfer_show_destroy'       => 'destroy'
    ];

    protected $rules = [
        'itemId'  => 'required|exists:items,id',
        'inputs.unit_id'  => 'required|exists:units,id',
        'inputs.qty'  => 'required|numeric|min:0',
        'inputs.qty_defect'  => 'required|numeric|min:0',
        'inputs.remark'  => 'required|string|in:normal,rusak,cacat',
    ];


    protected $messages = [
        'itemId.required'           => ':attribute harus diisi.',
        'inputs.unit_id.required'           => ':attribute harus diisi.',
        'itemId.exists'           => ':attribute tidak ada di master',
        'inputs.unit_id.exists'           => ':attribute tidak ada di master',

        'inputs.qty.required'           => ':attribute harus diisi.',
        'inputs.qty.numeric'           => ':attribute harus numeric.',
        'inputs.qty.min'           => ':attribute minimal :min',
        
        'inputs.qty_defect.required'           => ':attribute harus diisi.',
        'inputs.qty_defect.numeric'           => ':attribute harus numeric.',
        'inputs.qty_defect.min'           => ':attribute minimal :min',

        'inputs.unit_id.exists'           => ':attribute tidak ada di master',
        'inputs.remark.required'           => ':attribute harus diisi.',
        'inputs.remark.string'           => ':attribute harus string.',
        'inputs.remark.in'           => ':attribute tidak ditemukan.',
    ];

    protected $validationAttributes = [
        'itemId'  => 'Item',
        'inputs.unit_id'  => 'Unit',
        'inputs.qty'  => 'Qty',
        'inputs.qty_defect'  => 'Qty defect',
        'inputs.remark'  => 'Kondisi',
    ];

    public function updatedItemId($itemId)
    {
        if (!is_null($itemId)) {
            $this->unitSelectBox($itemId);
        }
    }

    public function unitSelectBox($itemId){
        $this->units = Unit::whereIn('id',ItemUnit::where('item_id', $itemId)->pluck('unit_id')->toArray())->get();
    }

    public function store()
    {
        $this->validate();
        TransferItem::create([
            'item_id' => $this->itemId,
            'unit_id'=> $this->inputs['unit_id'],
            'qty'=> $this->inputs['qty'],
            'qty_defect'=> $this->inputs['qty_defect'],
            'remark'=> $this->inputs['remark'],
            'transfer_id' => $this->transfer->id
        ]);

        # todo transfer rate

        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menyimpan transfer item."]);
        $this->emit('transaction_transfer_show_refresh');
        $this->reset('inputs');
    }

    public function edit($id)
    {
        $this->transferItem = TransferItem::findOrFail($id);
        $this->itemId = $this->transferItem->item_id;
        $this->unitSelectBox($this->transferItem->item_id);
        $this->inputs['unit_id'] = $this->transferItem->unit_id;
        $this->inputs['qty'] = $this->transferItem->qty;
        $this->inputs['qty_defect'] = $this->transferItem->qty_defect;
        $this->inputs['remark'] = $this->transferItem->remark;
        $this->setEditMode(true);
    }

    public function update()
    {
        $this->validate();

        $this->transferItem->update([
            'item_id' => $this->itemId,
            'unit_id'=> $this->inputs['unit_id'],
            'qty'=> $this->inputs['qty'],
            'qty_defect'=> $this->inputs['qty_defect'],
            'remark'=> $this->inputs['remark'],
        ]);
        $this->setEditMode(false);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil mengubah transfer item."]);
        $this->emit('transaction_transfer_show_refresh');
        $this->reset('inputs');
    }

    public function delete($id)
    {
        $this->setEditMode(false);
        $this->transferItem = TransferItem::findOrFail($id);
    }

    public function destroy()
    {
        $this->transferItem->delete();
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Berhasil menghapus transfer item."]);
        $this->emit('transaction_transfer_show_refresh');
    }
}
