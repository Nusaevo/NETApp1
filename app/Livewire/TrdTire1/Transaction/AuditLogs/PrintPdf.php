<?php

namespace App\Livewire\TrdTire1\Transaction\AuditLogs;

use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\BillingHdr;

class PrintPdf extends BaseComponent
{
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);
    }
    public $orders = [];
    public $selectedOrderIds;

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->objectId)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }

            $decrypted = decryptWithSessionKey($this->objectId);
            $this->selectedOrderIds = json_decode($decrypted, true);

            // Fetch data dengan filter
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
