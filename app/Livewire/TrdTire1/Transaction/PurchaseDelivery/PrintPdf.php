<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;

class PrintPdf extends BaseComponent
{
    public $masa; // Selected masa (month-year)
    public $orders = []; // Orders to be displayed
    public $object;
    public $objectIdValue;

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->additionalParam)) { // Check if 'masa' is passed
                $this->dispatch('error', 'Masa belum dipilih.');
                return;
            }

            $this->masa = $this->additionalParam; // Use the selected masa

            // Fetch orders based on the selected masa
            $this->orders = OrderHdr::with(['OrderDtl', 'Partner']) // Fetch required relations
                ->whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$this->masa])
                ->where('tr_type', 'SO')
                ->whereNull('deleted_at')
                ->get();
        }
    }

    protected function onLoadForEdit()
    {
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    protected function onPopulateDropdowns()
    {

    }

    protected function onReset()
    {
    }

    public function onValidateAndSave()
    {
    }
}
