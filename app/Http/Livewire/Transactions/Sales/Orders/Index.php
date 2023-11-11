<?php

namespace App\Http\Livewire\Transactions\Sales\Orders;

use Livewire\Component;
use App\Models\SalesOrder;
use App\Traits\LivewireTrait;

class Index extends Component
{
    use LivewireTrait;
    public $sales_order;
    public function render()
    {
        return view('livewire.transactions.sales.orders.index');
    }

    protected $listeners = [
        'sales_order_index_edit'  => 'editOrder',
        'sales_order_index_delete'  => 'deleteOrder',
    ];
    public function editOrder($id)
    {
        $sales_order = SalesOrder::findOrFail($id);
        $now = new \DateTime();
        $interval = $now->diff($sales_order->created_at);
        if ($interval->format('%H') >= 24 || $interval->format('%d') >= 24) {
            $this->dispatchBrowserEvent('notify-swal', ['type' => 'error', 'title' => 'Berhasil', 'message' =>  "Nota tidak bisa di edit jika sudah melewati 24 jam, mohon buat nota baru!"]);
        } else {
            return redirect()->route('sales.order.detail', $id);
        }
    }

    public function deleteOrder($id)
    {
        $this->sales_order = SalesOrder::findOrFail($id);
        $this->sales_order->delete();
        $this->sales_order->sales_order_details()->delete();
        $this->dispatchBrowserEvent('notify-swal', ['type' => 'success', 'title' => 'Berhasil', 'message' =>  "Berhasil mengahapus customer {$this->sales_order->id}."]);
        $this->emit('sales_order_refresh');
    }
}
