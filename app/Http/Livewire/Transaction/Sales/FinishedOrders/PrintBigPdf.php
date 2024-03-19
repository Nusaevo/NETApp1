<?php

namespace App\Http\Livewire\Transaction\Sales\FinishedOrders;

use App\Models\SalesOrder;
use Livewire\Component;

class PrintBigPdf extends Component
{
    public $sales_order;

    public function mount($id)
    {
        $this->sales_order = SalesOrder::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.transaction.sales.finishedorders.printbigpdf');
    }
}
