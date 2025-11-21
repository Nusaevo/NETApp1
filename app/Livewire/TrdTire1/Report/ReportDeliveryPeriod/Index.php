<?php

namespace App\Livewire\TrdTire1\Report\ReportDeliveryPeriod;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking};
use App\Models\Util\GenericExcelExport;
use App\Enums\TrdTire1\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class Index extends BaseComponent
{
    public $selectedMasa = '';
    public $masaOptions = [];
    public $results = [];
    public $startDate = '';
    public $endDate = '';
    public $dateFrom = '';
    public $dateTo = '';
    protected $listeners = [
        'DropdownSelected' => 'DropdownSelected'
    ];
    protected function onPreRender()
    {
        $this->loadMasaOptions();
    }

    protected function onReset()
    {
        // Don't reset results data when component is reset
        // This prevents data from being cleared when Livewire re-renders
    }

    public function loadMasaOptions()
    {
        $masaData = DelivHdr::selectRaw("TO_CHAR(tr_date, 'YYYY-MM') as filter_value, TO_CHAR(tr_date, 'FMMonth-YYYY') as display_value")
            ->where('deliv_hdrs.tr_type', 'PD')
            ->whereIn('deliv_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP])
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
        if (!$this->selectedMasa && (!$this->dateFrom || !$this->dateTo)) {
            $this->dispatch('warning', __('Masa atau periode tanggal harus dipilih.'));
            $this->addError('selectedMasa', 'Mohon pilih masa atau periode tanggal');
            return;
        }

        $this->resetErrorBag();

        // Use date range if provided, otherwise use masa
        if ($this->dateFrom && $this->dateTo) {
            $this->startDate = $this->dateFrom;
            $this->endDate = $this->dateTo;
        } else {
            // Parse masa to get start and end dates
            $yearMonth = $this->selectedMasa;
            $this->startDate = $yearMonth . '-01';
            $this->endDate = date('Y-m-t', strtotime($this->startDate));
        }

        // Query to get detailed delivery transaction data
        try {
            $this->results = DelivHdr::select([
                    'deliv_hdrs.tr_code as delivery_no',
                    'deliv_hdrs.reff_date as tgl_sj',
                    'deliv_hdrs.tr_date as delivery_date',
                    'partners.name as customer_name',
                    'deliv_hdrs.tr_code',
                    'partners.city as customer_city',
                    'deliv_packings.matl_descr as item_name',
                    'deliv_packings.qty',
                    'order_dtls.price as p_unit',
                    'order_dtls.amt_beforetax as dpp',
                    'order_dtls.amt_tax as ppn',
                    'order_dtls.amt as total_amount',
                    'deliv_hdrs.amt_shipcost as shipping_cost',
                    'partners.id as partner_id',
                    'order_dtls_ref.price as price_from_order'
                ])
                ->join('deliv_packings', 'deliv_hdrs.id', '=', 'deliv_packings.trhdr_id')
                ->join('order_dtls', 'deliv_packings.reffdtl_id', '=', 'order_dtls.id')
                ->join('partners', 'deliv_hdrs.partner_id', '=', 'partners.id')
                ->leftJoin('order_dtls as order_dtls_ref', function($join) {
                    $join->on('deliv_hdrs.reff_code', '=', 'order_dtls_ref.tr_code')
                         ->on('deliv_packings.matl_descr', '=', 'order_dtls_ref.matl_descr')
                         ->where('order_dtls_ref.tr_type', '=', 'PO');
                })
                ->where('deliv_hdrs.tr_type', 'PD')
                ->whereIn('deliv_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP])
                ->whereBetween('deliv_hdrs.tr_date', [$this->startDate, $this->endDate])
                ->orderBy('deliv_hdrs.tr_date')
                ->orderBy('deliv_hdrs.tr_code')
                ->orderBy('deliv_packings.tr_seq')
                ->get();

            // Ensure results is always a collection
            if (!is_object($this->results) || !method_exists($this->results, 'toArray')) {
                $this->results = collect([]);
            }

            // Store results in session for Excel export
            $resultsArray = is_object($this->results) && method_exists($this->results, 'toArray')
                ? $this->results->toArray()
                : (array) $this->results;
            Session::put('report_delivery_period_results', $resultsArray);
        } catch (\Exception $e) {
            $this->results = collect([]);
            $this->dispatch('error', 'Terjadi kesalahan saat mengambil data: ' . $e->getMessage());
        }
    }

    public function cetakLaporanPengiriman()
    {
        if (!$this->selectedMasa) {
            $this->dispatch('error', 'Masa belum dipilih.');
            return;
        }

        // Check if there are any deliveries for the selected masa
        $deliveryCount = DelivHdr::whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$this->selectedMasa])
            ->where('tr_type', 'PD')
            ->whereNull('deleted_at')
            ->count();

        if ($deliveryCount === 0) {
            $this->dispatch('error', 'Tidak ada data untuk masa yang dipilih.');
            return;
        }

        // Use array structure with JSON encoding
        $paramArray = [
            'selectedMasa' => $this->selectedMasa,
            'type' => 'cetakLaporanPengiriman'
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
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->results = [];
        $this->startDate = '';
        $this->endDate = '';
    }

    public function resetResult()
    {
        $this->results = [];
    }

    public function updatedSelectedMasa()
    {
        // Clear date filters when masa is selected
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->search();
    }

    public function updatedDateFrom()
    {
        // Clear masa when date range is selected
        $this->selectedMasa = '';
        $this->results = [];
        // Don't auto-search, let user click View button
    }

    public function updatedDateTo()
    {
        // Clear masa when date range is selected
        $this->selectedMasa = '';
        $this->results = [];
        // Don't auto-search, let user click View button
    }

    public function downloadExcel()
    {
        // Try to get data from session first, fallback to current results
        $sessionResults = Session::get('report_delivery_period_results', []);

        if (empty($sessionResults) && empty($this->results)) {
            $this->dispatch('warning', __('Tidak ada data untuk diekspor. Silakan lakukan pencarian terlebih dahulu.'));
            return;
        }

        try {
            // Use session data if available, otherwise use current results
            if (!empty($sessionResults)) {
                $this->results = collect($sessionResults)->map(function($item) {
                    return (object) $item;
                });
            }

            // Prepare data for Excel export
            $excelData = $this->prepareExcelData();

            // Generate filename with date range
            $filename = 'Laporan_Pengiriman_Masa';
            if ($this->dateFrom && $this->dateTo) {
                $filename .= '_' . \Carbon\Carbon::parse($this->dateFrom)->format('Y-m-d') . '_to_' . \Carbon\Carbon::parse($this->dateTo)->format('Y-m-d');
            } elseif ($this->selectedMasa) {
                $filename .= '_' . \Carbon\Carbon::parse($this->selectedMasa . '-01')->format('Y-m');
            }
            $filename .= '.xlsx';

            // Create Excel export
            $excelExport = new GenericExcelExport($excelData, $filename);

            return $excelExport->download();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Terjadi kesalahan saat mengekspor data: ' . $e->getMessage());
        }
    }

    private function prepareExcelData(): array
    {
        // Use current results
        $results = $this->results;

        // Prepare headers
        $headers = [
            'No.SJ',
            'TGL.SJ',
            'NO.FP',
            'Nama Barang',
            'Qty',
            'P/Unit',
            'DPP',
            'PPN',
            'JUMLAH'
        ];

        // Prepare data rows
        $data = [];
        $grandTotalQty = 0;
        $grandTotalPrice = 0;
        $grandTotalDpp = 0;
        $grandTotalPpn = 0;
        $grandTotalAmount = 0;
        $currentTrCode = null;
        $trCodeSubtotalQty = 0;
        $trCodeSubtotalPrice = 0;
        $trCodeSubtotalDpp = 0;
        $trCodeSubtotalPpn = 0;
        $trCodeSubtotalAmount = 0;
        $previousTrCodeSubtotalQty = 0;
        $previousTrCodeSubtotalPrice = 0;
        $previousTrCodeSubtotalDpp = 0;
        $previousTrCodeSubtotalPpn = 0;
        $previousTrCodeSubtotalAmount = 0;

        $subtotalRowIndices = []; // Track subtotal row indices for styling
        foreach ($results as $index => $row) {
            // Safety check to ensure $row is an object
            if (!is_object($row)) {
                continue;
            }

            $deliveryNo = $row->tr_code ?? '';
            $tglSj = $row->tgl_sj
                ? \Carbon\Carbon::parse($row->tgl_sj)->format('d-M-Y')
                : '';
            $deliveryDate = $row->delivery_date
                ? \Carbon\Carbon::parse($row->delivery_date)->format('d-M-Y')
                : '';
            $customerName = $row->customer_name ?? '';
            $trCode = $row->tr_code ?? '';
            $itemName = $row->item_name ?? '';
            $qty = $row->qty ?? 0;
            $pUnit = $row->price_from_order ?? $row->p_unit ?? 0;
            $dpp = $row->dpp ?? 0;
            $ppn = $row->ppn ?? 0;
            $totalAmount = $row->total_amount ?? 0;

            // Check if this is a new tr_code (nota)
            $isNewTrCode = $currentTrCode !== $trCode;

            if ($isNewTrCode) {
                // If not the first tr_code, show subtotal for previous tr_code
                if ($currentTrCode !== null) {
                    $previousTrCodeSubtotalQty = $trCodeSubtotalQty;
                    $previousTrCodeSubtotalPrice = $trCodeSubtotalPrice;
                    $previousTrCodeSubtotalDpp = $trCodeSubtotalDpp;
                    $previousTrCodeSubtotalPpn = $trCodeSubtotalPpn;
                    $previousTrCodeSubtotalAmount = $trCodeSubtotalAmount;

                    // Add subtotal row
                    $data[] = [
                        '', // No.SJ
                        '', // TGL.SJ
                        '', // NO.FP
                        '', // Nama Barang
                        $previousTrCodeSubtotalQty, // Qty
                        $previousTrCodeSubtotalPrice, // P/Unit
                        $previousTrCodeSubtotalDpp, // DPP
                        $previousTrCodeSubtotalPpn, // PPN
                        $previousTrCodeSubtotalAmount // JUMLAH
                    ];

                    // Record subtotal row index for styling
                    $subtotalRowIndices[] = count($data) - 1;

                    // Add empty row after subtotal for spacing between notes
                    $data[] = [
                        '', // No.SJ
                        '', // TGL.SJ
                        '', // NO.FP
                        '', // Nama Barang
                        '', // Qty
                        '', // P/Unit
                        '', // DPP
                        '', // PPN
                        '' // JUMLAH
                    ];
                }

                // Reset subtotals for new tr_code
                $currentTrCode = $trCode;
                $trCodeSubtotalQty = 0;
                $trCodeSubtotalPrice = 0;
                $trCodeSubtotalDpp = 0;
                $trCodeSubtotalPpn = 0;
                $trCodeSubtotalAmount = 0;
            }

            // Add to tr_code subtotals
            $trCodeSubtotalQty += $qty;
            $trCodeSubtotalPrice += $pUnit;
            $trCodeSubtotalDpp += $dpp;
            $trCodeSubtotalPpn += $ppn;
            $trCodeSubtotalAmount += $totalAmount;

            // Add to grand totals
            $grandTotalQty += $qty;
            $grandTotalPrice += $pUnit;
            $grandTotalDpp += $dpp;
            $grandTotalPpn += $ppn;
            $grandTotalAmount += $totalAmount;

            // Add data row
            $data[] = [
                $isNewTrCode ? $deliveryNo : '', // No.SJ
                $isNewTrCode ? $tglSj : '', // TGL.SJ
                '', // NO.FP
                $itemName, // Nama Barang
                $qty, // Qty
                $pUnit, // P/Unit
                $dpp, // DPP
                $ppn, // PPN
                $totalAmount // JUMLAH
            ];
        }

        // Add final subtotal for last tr_code
        if ($currentTrCode !== null) {
            $data[] = [
                '', // No.SJ
                '', // TGL.SJ
                '', // NO.FP
                'Subtotal', // Nama Barang
                $trCodeSubtotalQty, // Qty
                $trCodeSubtotalPrice, // P/Unit
                $trCodeSubtotalDpp, // DPP
                $trCodeSubtotalPpn, // PPN
                $trCodeSubtotalAmount // JUMLAH
            ];

            // Record final subtotal row index for styling
            $subtotalRowIndices[] = count($data) - 1;

            // Add empty row after final subtotal for spacing
            $data[] = [
                '', // No.SJ
                '', // TGL.SJ
                '', // NO.FP
                '', // Nama Barang
                '', // Qty
                '', // P/Unit
                '', // DPP
                '', // PPN
                '' // JUMLAH
            ];
        }

        // Grand total row removed as requested

        // Prepare row styles for subtotals
        $rowStyles = [];
        foreach ($subtotalRowIndices as $rowIndex) {
            $rowStyles[] = [
                'rowIndex' => $rowIndex,
                'bold' => true,
                'borderTop' => true,
                // 'backgroundColor' => 'E6E6E6',
                'specificCells' => ['E', 'F', 'G', 'H', 'I']
            ];
        }

        // Prepare title and subtitle
        $title = 'LAPORAN PENGIRIMAN MASA';
        $subtitle = '';
        if ($this->dateFrom && $this->dateTo) {
            $subtitle = strtoupper(\Carbon\Carbon::parse($this->dateFrom)->format('d F Y')) . ' - ' .
                       strtoupper(\Carbon\Carbon::parse($this->dateTo)->format('d F Y'));
        } elseif ($this->selectedMasa) {
            $subtitle = strtoupper(\Carbon\Carbon::parse($this->selectedMasa . '-01')->format('F Y'));
        }

        return [
            [
                'name' => 'Laporan Penjualan',
                'title' => $title,
                'subtitle' => $subtitle,
                'headers' => $headers,
                'data' => $data,
                'columnWidths' => [
                    'A' => 18, // No.SJ
                    'B' => 15, // TGL.SJ
                    'C' => 18, // NO.FP
                    'D' => 35, // Nama Barang
                    'E' => 12, // Qty
                    'F' => 18, // P/Unit
                    'G' => 18, // DPP
                    'H' => 18, // PPN
                    'I' => 18  // JUMLAH
                ],
                'rowStyles' => $rowStyles,
                'allowInsert' => false
            ]
        ];
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
