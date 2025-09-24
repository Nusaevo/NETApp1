<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\Util\GenericExcelExport;
use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;

class PrintPdf extends BaseComponent
{
    use WithPagination;

    public $masa; // Selected masa (month-year)
    public $object;
    public $objectIdValue;
    public $perPage = 50;

    // Bypass permission check for PrintPdf components
    public $bypassPermissions = true;

    public function getOrdersProperty()
    {
        try {
            if (empty($this->masa)) {
                return collect();
            }

            return OrderHdr::with(['OrderDtl', 'Partner'])
                ->whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$this->masa])
                ->where('tr_type', 'SO')
                ->whereNull('deleted_at')
                ->paginate($this->perPage);
        } catch (\Exception $e) {
            $this->dispatch('error', 'Database error: ' . $e->getMessage());
            return collect();
        }
    }


    protected function onPreRender()
    {
         if ($this->isEditOrView()) {
            if (empty($this->additionalParam)) {
                $this->dispatch('error', 'Parameter tidak ditemukan.');
                return;
            }

            // Handle new query string format or old JSON structure
            try {
                // First decrypt the additionalParam
                $decryptedParam = decryptWithSessionKey($this->additionalParam);

                // Try JSON decode first (new array format)
                $decodedParam = json_decode($decryptedParam, true);
                if (is_array($decodedParam) && json_last_error() === JSON_ERROR_NONE) {
                    // Handle JSON array structure
                    if (isset($decodedParam['type']) && $decodedParam['type'] === 'cetakLaporanPenjualan') {
                        if (isset($decodedParam['selectedMasa'])) {
                            $this->masa = $decodedParam['selectedMasa'];
                        }
                    } else {
                        // Fallback for old structure (simple array)
                        if (!empty($decodedParam) && is_string($decodedParam)) {
                            $this->masa = $decodedParam;
                        }
                    }
                }
            } catch (\Exception $e) {
                // If parsing fails, treat as simple string (backward compatibility)
                try {
                    $this->masa = decryptWithSessionKey($this->additionalParam);
                } catch (\Exception $e2) {
                    $this->masa = $this->additionalParam;
                }
            }

            if (empty($this->masa)) {
                $this->dispatch('error', 'Masa belum dipilih.');
                return;
            }
        }
        $this->getOrdersProperty();
    }

    protected function onLoadForEdit()
    {
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));

        // Pass masa variable to the view
        return view($renderRoute, [
            'masa' => $this->masa,
            'orders' => $this->orders
        ]);
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

    public function downloadExcel()
    {
        if ($this->orders->isEmpty()) {
            $this->dispatch('error', 'Tidak ada data untuk didownload.');
            return;
        }

        // Prepare data for Excel
        $excelData = [];
        $grandTotalDpp = 0;
        $grandTotalPpn = 0;
        $grandTotalJumlah = 0;

        foreach ($this->orders as $order) {
            $totalDpp = 0;
            $totalPpn = 0;
            $totalJumlah = 0;
            $taxPct = (float)($order->tax_pct ?? 0);
            $taxFlag = $order->tax_code ?? 'I';

            foreach ($order->OrderDtl as $detail) {
                // Calculate line amount
                $discPct = (float)($detail->disc_pct ?? 0);
                $lineAmt = isset($detail->amt) && $detail->amt > 0
                    ? (float)$detail->amt
                    : ((float)$detail->qty * (float)$detail->price * (1 - $discPct / 100));

                // Calculate DPP/PPN
                if ($taxFlag === 'I') {
                    $dpp = $taxPct > 0 ? ($lineAmt / (1 + $taxPct / 100)) : $lineAmt;
                    $ppn = $lineAmt - $dpp;
                } elseif ($taxFlag === 'E') {
                    $dpp = $lineAmt;
                    $ppn = $lineAmt * ($taxPct / 100);
                } else {
                    $dpp = $lineAmt;
                    $ppn = 0;
                }

                $dpp2 = $dpp * 11 / 12; // DPP Lain2
                $jumlah = $dpp + $ppn;
                $totalDpp += $dpp;
                $totalPpn += $ppn;
                $totalJumlah += $jumlah;

                $excelData[] = [
                    $order->tax_doc_num ?? '',
                    \Carbon\Carbon::parse($order->tr_date)->format('d-M-Y'),
                    $order->Partner?->name ?? 'N/A',
                    $detail->matl_descr,
                    $detail->qty,
                    number_format($detail->price, 0, ',', '.'),
                    number_format($dpp, 0, ',', '.'),
                    number_format($dpp2, 0, ',', '.'),
                    number_format($ppn, 0, ',', '.'),
                    number_format($jumlah, 0, ',', '.'),
                ];
            }

            // Add subtotal row
            $excelData[] = [
                '', // No. Faktur
                '', // Tgl. Nota
                '', // Nama Customer
                '', // Nama Barang
                '', // Qty
                '', // Harga
                number_format($totalDpp, 0, ',', '.'), // DPP
                '', // DPP Lain2
                number_format($totalPpn, 0, ',', '.'), // PPN
                number_format($totalJumlah, 0, ',', '.'), // JUMLAH
            ];

            $grandTotalDpp += $totalDpp;
            $grandTotalPpn += $totalPpn;
            $grandTotalJumlah += $totalJumlah;
        }

        // Add grand total row
        $excelData[] = [
            '', // No. Faktur
            '', // Tgl. Nota
            '', // Nama Customer
            '', // Nama Barang
            '', // Qty
            '', // Harga
            number_format($grandTotalDpp, 0, ',', '.'), // DPP
            '', // DPP Lain2
            number_format($grandTotalPpn, 0, ',', '.'), // PPN
            number_format($grandTotalJumlah, 0, ',', '.'), // JUMLAH
        ];

        // Create Excel configuration with title and header info
        $title = 'LAPORAN PENJUALAN MASA ' . strtoupper(\Carbon\Carbon::parse($this->masa)->translatedFormat('F Y'));
        $subtitle = 'Periode: ' . \Carbon\Carbon::parse($this->masa)->format('F Y');

        $sheets = [[
            'name' => 'Laporan_Penjualan',
            'headers' => [
                'No. Faktur', 'Tgl. Nota', 'Nama Customer', 'Nama Barang',
                'Qty', 'Harga', 'DPP', 'DPP Lain2', 'PPN', 'JUMLAH'
            ],
            'data' => $excelData,
            'protectedColumns' => [],
            'allowInsert' => false,
            'title' => $title,
            'subtitle' => $subtitle,
        ]];

        $filename = 'Laporan_Penjualan_' . \Carbon\Carbon::parse($this->masa)->format('Y-m') . '.xlsx';

        $this->dispatch('refresh-page');

        return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
    }
}
