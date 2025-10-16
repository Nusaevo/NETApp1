<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\BillingHdr;
use Illuminate\Support\Collection;

class PrintPdf extends BaseComponent
{
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);
        $this->orders = collect();
    }
    public Collection $orders;
    public $selectedOrderIds;
    public $groupedOrders = [];

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
                ->orderBy('billing_hdrs.tr_date')
                ->orderBy('billing_hdrs.tr_code')
                ->get();

            // Group orders by customer (partner_id)
            $this->groupOrdersByCustomer();

            // Update status to PRINT
            BillingHdr::whereIn('id', $this->selectedOrderIds)->update(['status_code' => Status::PRINT]);
        }
    }
    private function groupOrdersByCustomer()
    {
        $this->groupedOrders = [];

        if ($this->orders && $this->orders->count() > 0) {
            // Group orders by partner_id
            $grouped = $this->orders->groupBy('partner_id');

            // Convert to array format for easier handling in view
            foreach ($grouped as $partnerId => $orders) {
                $firstOrder = $orders->first();
                if ($firstOrder && $firstOrder->Partner) {
                    $this->groupedOrders[] = [
                        'partner_id' => $partnerId,
                        'partner' => $firstOrder->Partner,
                        'orders' => $orders,
                        'total_amount' => $orders->sum('amt'),
                        'print_date' => $firstOrder->print_date
                    ];
                }
            }

            // Sort grouped orders by print_date descending (terbaru ke terlama)
            usort($this->groupedOrders, function($a, $b) {
                $dateA = $a['print_date'] ? \Carbon\Carbon::parse($a['print_date']) : \Carbon\Carbon::minValue();
                $dateB = $b['print_date'] ? \Carbon\Carbon::parse($b['print_date']) : \Carbon\Carbon::minValue();
                return $dateB->timestamp - $dateA->timestamp; // Descending order
            });
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

}
