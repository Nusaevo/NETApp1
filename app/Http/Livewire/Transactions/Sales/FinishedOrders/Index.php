<?php

namespace App\Http\Livewire\Transactions\Sales\FinishedOrders;

use Livewire\Component;
use App\Models\SalesOrder;

class Index extends Component
{
    public function render()
    {
        return view('livewire.transactions.sales.finishedorders.index');
    }

    protected $listeners = [
        'sales_finishorder_index_edit'  => 'editOrder'
    ];
    public function editOrder($id)
    {
        $sales_order = SalesOrder::findOrFail($id);
        $now = new \DateTime();
        $interval = $now->diff($sales_order->created_at);
        if ($interval->format('%H') >= 24 || $interval->format('%d') >= 1) {
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Nota tidak bisa di edit jika sudah melewati 24 jam, mohon buat nota baru!"]);
        } else {
            return redirect()->route('sales.finishedorder.edit', $id);
        }
    }
}
