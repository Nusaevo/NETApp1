<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Enums\TrdTire1\Status;

class PrintPdf extends BaseComponent
{
    public $orderIds; // Ubah nama properti untuk menyimpan ID
    public $printDate;
    public $orders = []; // Properti untuk menyimpan data order

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }

            $this->printDate = $this->additionalParam; // Use the selected print_date

            // Filter orders based on print_date and other conditions
            $this->orderIds = OrderHdr::where('print_date', $this->printDate)
                ->where('tr_type', 'SO')
                ->whereIn('status_code', [Status::PRINT, Status::OPEN])
                ->whereNull('deleted_at')
                ->pluck('id')
                ->toArray();

            $this->orders = OrderHdr::with(['OrderDtl', 'Partner']) // Fetch required relations
                ->whereIn('id', $this->orderIds)
                ->get();

                // dd($this->orders);
            // $this->object->status_code = Status::PRINT;
            // $this->object->save();
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
