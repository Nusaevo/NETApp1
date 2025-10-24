<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\BillingHdr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
            // Ambil tanggal dari order_hdrs.tr_date (tanggal nota SO)
            $this->orders = BillingHdr::with(['Partner'])
                ->leftJoin('order_hdrs', function ($join) {
                    $join->on('order_hdrs.tr_code', '=', 'billing_hdrs.tr_code')
                         ->where('order_hdrs.tr_type', 'SO');
                })
                ->whereIn('billing_hdrs.id', $this->selectedOrderIds)
                ->where('billing_hdrs.tr_type', 'ARB')
                ->whereIn('billing_hdrs.status_code', [Status::ACTIVE, Status::PRINT, Status::OPEN])
                ->select('billing_hdrs.*', DB::raw('order_hdrs.tr_date as order_tr_date'))
                ->orderBy('order_hdrs.tr_date')
                ->orderBy('billing_hdrs.tr_code')
                ->get();

            // Group orders by customer (partner_id) and billing date (print_date)
            $this->groupOrdersByCustomer();

            // Update status to PRINT
            BillingHdr::whereIn('id', $this->selectedOrderIds)->update(['status_code' => Status::PRINT]);
        }
    }
    private function groupOrdersByCustomer()
    {
        $this->groupedOrders = [];

        if ($this->orders && $this->orders->count() > 0) {
            // Group orders by partner_id first
            $groupedByPartner = $this->orders->groupBy('partner_id');

            // For each partner, further group by print_date (billing date)
            foreach ($groupedByPartner as $partnerId => $ordersByPartner) {
                $subGroups = $ordersByPartner->groupBy(function ($order) {
                    return $order->print_date ?: '';
                });

                foreach ($subGroups as $printDate => $orders) {
                    $firstOrder = $orders->first();
                    if ($firstOrder && $firstOrder->Partner) {
                        $this->groupedOrders[] = [
                            'partner_id' => $partnerId,
                            'partner' => $firstOrder->Partner,
                            'orders' => $orders,
                            'total_amount' => $orders->sum('amt'),
                            'print_date' => $printDate
                        ];
                    }
                }
            }

            // Sort grouped orders by partner name first, then by print_date descending
            usort($this->groupedOrders, function($a, $b) {
                // First sort by partner name
                $partnerCompare = strcmp($a['partner']->name, $b['partner']->name);
                if ($partnerCompare !== 0) {
                    return $partnerCompare;
                }

                // If partner names are the same, sort by print_date descending
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
