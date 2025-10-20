<?php

namespace App\Livewire\TrdTire1\Report\ReportSalesPeriod;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Enums\TrdTire1\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Index extends BaseComponent
{
    public $selectedMasa = '';
    public $masaOptions = [];
    public $results = [];
    public $startDate = '';
    public $endDate = '';

    protected function onPreRender()
    {
        $this->loadMasaOptions();
    }

    public function loadMasaOptions()
    {
        $masaData = OrderHdr::selectRaw("TO_CHAR(tr_date, 'YYYY-MM') as filter_value, TO_CHAR(tr_date, 'FMMonth-YYYY') as display_value")
            ->where('order_hdrs.tr_type', 'SO')
            ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP])
            ->where('order_hdrs.tax_doc_flag', 1)
            ->distinct()
            ->orderByRaw("TO_CHAR(tr_date, 'YYYY-MM') DESC")
            ->get();

        // Convert to array format expected by ui-dropdown-select component
        $this->masaOptions = [];

        // Add "Not Selected" option
        $this->masaOptions[] = [
            'value' => '',
            'label' => 'Not Selected'
        ];

        // Add masa options
        foreach ($masaData as $masa) {
            $this->masaOptions[] = [
                'value' => $masa->filter_value,
                'label' => $masa->display_value
            ];
        }
    }

    public function search()
    {
        if (!$this->selectedMasa) {
            $this->dispatch('warning', __('Masa belum dipilih.'));
            $this->addError('selectedMasa', 'Mohon pilih masa');
            return;
        }

        $this->resetErrorBag();

        // Parse masa to get start and end dates
        $yearMonth = $this->selectedMasa;
        $this->startDate = $yearMonth . '-01';
        $this->endDate = date('Y-m-t', strtotime($this->startDate));

        // Query to get sales data grouped by customer
        try {
            $this->results = OrderHdr::select([
                    'partners.name as customer_name',
                    'partners.city as customer_city',
                    DB::raw('COUNT(DISTINCT order_hdrs.id) as total_orders'),
                    DB::raw('SUM(order_dtls.qty) as total_qty'),
                    DB::raw('SUM(order_dtls.amt) as total_amount'),
                    DB::raw('SUM(order_dtls.amt_beforetax) as total_dpp'),
                    DB::raw('SUM(order_dtls.amt_tax) as total_ppn')
                ])
                ->join('order_dtls', 'order_hdrs.id', '=', 'order_dtls.trhdr_id')
                ->join('partners', 'order_hdrs.partner_id', '=', 'partners.id')
                ->where('order_hdrs.tr_type', 'SO')
                ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP])
                ->where('order_hdrs.tax_doc_flag', 1)
                ->whereRaw("TO_CHAR(order_hdrs.tr_date, 'YYYY-MM') = ?", [$this->selectedMasa])
                ->groupBy('partners.id', 'partners.name', 'partners.city')
                ->orderBy('partners.name')
                ->get();

            // Ensure results is always a collection
            if (!is_object($this->results) || !method_exists($this->results, 'toArray')) {
                $this->results = collect([]);
            }
        } catch (\Exception $e) {
            $this->results = collect([]);
            $this->dispatch('error', 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage());
        }
    }

    public function cetakLaporanPenjualan()
    {
        if (!$this->selectedMasa) {
            $this->dispatch('error', 'Masa belum dipilih.');
            return;
        }

        // Check if there are any orders for the selected masa
        $orderCount = OrderHdr::whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$this->selectedMasa])
            ->where('tr_type', 'SO')
            ->whereNull('deleted_at')
            ->count();

        if ($orderCount === 0) {
            $this->dispatch('error', 'Tidak ada data untuk masa yang dipilih.');
            return;
        }

        // Use array structure with JSON encoding
        $paramArray = [
            'selectedMasa' => $this->selectedMasa,
            'type' => 'cetakLaporanPenjualan'
        ];

        return redirect()->route($this->appCode . '.Transaction.PurchaseDelivery.PrintPdf', [
            'action' => encryptWithSessionKey('Edit'),
            'objectId' => encryptWithSessionKey(''),
            'additionalParam' => encryptWithSessionKey(json_encode($paramArray)),
        ]);
    }

    public function resetFilters()
    {
        $this->selectedMasa = '';
        $this->results = [];
        $this->startDate = '';
        $this->endDate = '';
    }

    public function resetResult()
    {
        $this->results = [];
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
