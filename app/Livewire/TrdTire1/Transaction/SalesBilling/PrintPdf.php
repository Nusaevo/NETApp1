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
        // dd($this->objectIdValue);
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }

            // Decode the encrypted order IDs
            $this->selectedOrderIds = json_decode(decryptWithSessionKey($this->objectIdValue), true);

            // Fetch data with the same filters and relations as in IndexDataTable
            $this->orders = BillingHdr::with(['Partner'])
                ->whereIn('id', $this->selectedOrderIds)
                ->where('billing_hdrs.tr_type', 'ARB')
                ->whereIn('billing_hdrs.status_code', [Status::ACTIVE, Status::PRINT, Status::OPEN])
                ->get();

            // Update status to PRINT
            BillingHdr::whereIn('id', $this->selectedOrderIds)->update(['status_code' => Status::PRINT]);
        }
    }
    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

}
