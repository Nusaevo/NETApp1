<?php

namespace App\Http\Livewire\Transactions\Sales\WarehouseOrders;

use App\Models\SalesOrder;
use Livewire\Component;

class PrintPdf extends Component
{
    public $sales_order;

    public function mount($id)
    {
        $this->sales_order = SalesOrder::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.transactions.sales.warehouseorders.printpdf');
    }
}
