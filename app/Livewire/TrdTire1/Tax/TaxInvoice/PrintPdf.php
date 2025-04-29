<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Enums\TrdTire1\Status;

class PrintPdf extends BaseComponent
{
    public $orderIds;
    public $printDate;
    public $orders = [];

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }

            $this->printDate = $this->additionalParam;

            $this->orderIds = OrderHdr::where('print_date', $this->printDate)
                ->where('tr_type', 'SO')
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            $this->orders = OrderHdr::with(['OrderDtl', 'Partner'])
                ->whereIn('id', $this->orderIds)
                ->get();

        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
