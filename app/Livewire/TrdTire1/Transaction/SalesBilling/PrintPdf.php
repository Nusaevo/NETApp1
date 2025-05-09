<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\BillingHdr;

class PrintPdf extends BaseComponent
{
    public $orders = [];
    public $selectedOrderIds;

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }

            // Decode the encrypted order IDs
            $this->selectedOrderIds = json_decode(decryptWithSessionKey($this->objectIdValue), true);

            // Ambil data dari BillingHdr dan relasi terkait
            $this->orders = BillingHdr::with(['Partner', 'OrderDtl'])
                ->whereIn('id', $this->selectedOrderIds)
                ->get();

            // Update status ke PRINT
            BillingHdr::whereIn('id', $this->selectedOrderIds)->update(['status_code' => Status::PRINT]);
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
