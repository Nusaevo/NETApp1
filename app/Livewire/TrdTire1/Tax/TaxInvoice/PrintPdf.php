<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\Util\GenericExcelExport;
use App\Enums\TrdTire1\Status;
use Livewire\WithPagination;

class PrintPdf extends BaseComponent
{
    use WithPagination;

    public $orderIds = [];
    public $printDate;
    public $type;
    public $perPage = 50;

    // Bypass permission check for PrintPdf components
    public $bypassPermissions = true;

    public function getOrdersProperty()
    {
        try {
            if ($this->type === 'cetakProsesDate') {
                return OrderHdr::with(['OrderDtl', 'Partner'])
                    ->where('print_date', $this->printDate)
                    ->where('tr_type', 'SO')
                    ->whereNull('deleted_at')
                    ->paginate($this->perPage);
            } else if ($this->type === 'single') {
                return OrderHdr::with(['OrderDtl', 'Partner'])
                    ->where('id', $this->objectIdValue)
                    ->whereNull('deleted_at')
                    ->paginate($this->perPage);
            }

            return collect();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Database error: ' . $e->getMessage());
            return collect();
        }
    }

    protected function onPreRender()
    {
        // Parse parameters
        if ($this->isEditOrView()) {
            // Handle single order print (when objectId is not empty)
            if (!empty($this->objectIdValue)) {
                $this->type = 'single';
            }
            // Handle bulk order print (when additionalParam contains order IDs and other data)
            else if (!empty($this->additionalParam)) {
                try {
                    // First decrypt the additionalParam
                    $decryptedParam = decryptWithSessionKey($this->additionalParam);

                    // Parse JSON array format
                    $decodedParam = json_decode($decryptedParam, true);
                    if (is_array($decodedParam) && json_last_error() === JSON_ERROR_NONE) {
                        // Handle JSON array structure
                        if (isset($decodedParam['type'])) {
                            $this->type = $decodedParam['type'];
                            if ($this->type === 'cetakProsesDate' && isset($decodedParam['selectedPrintDate'])) {
                                $this->printDate = $decodedParam['selectedPrintDate'];
                            }
                        }
                    } else {
                        $this->dispatch('error', 'Invalid parameter structure');
                        return;
                    }
                } catch (\Exception $e) {
                    $this->dispatch('error', 'Invalid additional parameters: ' . $e->getMessage());
                    return;
                }
            }

            // Validate based on type
            if ($this->type === 'cetakProsesDate') {
                if (empty($this->printDate)) {
                    $this->dispatch('error', 'Print date not provided for cetakProsesDate');
                    return;
                }
            } else if ($this->type === 'single') {
                if (empty($this->objectIdValue)) {
                    $this->dispatch('error', 'No order ID provided for single print');
                    return;
                }
            }
        }
        $this->getOrdersProperty();
    }

    public function downloadExcel()
    {
        try {
            $orders = $this->orders;
            if ($orders->isEmpty()) {
                $this->dispatch('error', 'Tidak ada data untuk didownload.');
                return;
            }

            // Prepare data for Excel
            $excelData = [];
            foreach ($this->orders as $order) {
                foreach ($order->OrderDtl as $detail) {
                    // Hitung amount baris (fallback ke qty*price*(1-disc)) bila amt null
                    $discPct = (float)($detail->disc_pct ?? 0);
                    $lineAmt = isset($detail->amt) && $detail->amt > 0
                        ? (float)$detail->amt
                        : ((float)$detail->qty * (float)$detail->price * (1 - $discPct / 100));

                    // Ambil DPP dan PPN langsung dari OrderDtl
                    $dpp = (float)($detail->amt_beforetax ?? 0);
                    $ppn = (float)($detail->amt_tax ?? 0);

                    $excelData[] = [
                        $order->tr_code,
                        $order->tax_doc_num ?? '',
                        \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y'),
                        $order->Partner?->name ?? 'N/A',
                        $detail->matl_descr,
                        $detail->qty,
                        number_format($detail->price_beforetax, 0, ',', '.'),
                        number_format($dpp, 0, ',', '.'),
                        number_format($ppn, 0, ',', '.'),
                        number_format($dpp + $ppn, 0, ',', '.'),
                        number_format($lineAmt, 0, ',', '.'),
                    ];
                }
            }

            // Create Excel configuration with title and header info
            $title = 'FAKTUR PAJAK REPORT';
            $subtitle = '';

            if ($this->type === 'cetakProsesDate' && $this->printDate) {
                $subtitle = 'Tanggal Proses: ' . \Carbon\Carbon::parse($this->printDate)->format('d-M-Y');
            } elseif ($this->type === 'single' && $this->objectIdValue) {
                $subtitle = 'Single Order Print - ' . now()->format('d-M-Y H:i:s');
            } else {
                $subtitle = 'Report - ' . now()->format('d-M-Y H:i:s');
            }

            $sheets = [[
                'name' => 'Faktur_Pajak_Report',
                'headers' => [
                    'No. Nota', 'No. Faktur', 'Tanggal', 'Nama Pelanggan',
                    'Nama Barang', 'Qty', 'Harga Pcs', 'DPP', 'PPN',
                    'DPP + PPN', 'Amt Nota'
                ],
                'data' => $excelData,
                'protectedColumns' => [],
                'allowInsert' => false,
                'title' => $title,
                'subtitle' => $subtitle,
            ]];

            $filename = 'Faktur_Pajak_Report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error generating Excel: ' . $e->getMessage());
            return;
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'orders' => $this->orders
        ]);
    }
}
