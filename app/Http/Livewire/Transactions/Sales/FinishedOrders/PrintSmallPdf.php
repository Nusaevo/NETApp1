<?php

namespace App\Http\Livewire\Transactions\Sales\FinishedOrders;

use App\Models\SalesOrder;
use Livewire\Component;

class PrintSmallPdf extends Component
{
    public $sales_order;

    public function mount($id)
    {
        $this->sales_order = SalesOrder::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.transactions.sales.finishedorders.printsmallpdf');
    }
}
