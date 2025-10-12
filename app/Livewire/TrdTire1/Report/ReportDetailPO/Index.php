<?php

namespace App\Livewire\TrdTire1\Report\ReportDetailPO;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Models\TrdTire1\Master\Partner;
use App\Models\Util\GenericExcelExport;
use Illuminate\Support\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Index extends BaseComponent
{
    public $startCode;
    public $endCode;
    public $filterPartner = '';
    public $filterBrand = '';
    public $results = [];

    public $ddPartner = [
        'placeHolder' => "Ketik untuk cari supplier ...",
        'optionLabel' => "code,name,address,city",
        'query' => "SELECT id,code,name,address,city
                    FROM partners
                    WHERE deleted_at IS NULL AND grp = 'V'",
    ];

    protected function onPreRender()
    {
        $this->resetFilters();
    }

    public function search()
    {
        // Validasi: minimal harus ada salah satu filter (tanggal, partner, atau brand)
        if (isNullOrEmptyNumber($this->startCode) && empty($this->filterPartner) && empty($this->filterBrand)) {
            $this->dispatch('notify-swal', [
                'type' => 'warning',
                'message' => 'Mohon lengkapi tanggal awal, pilih supplier, atau pilih brand untuk melakukan pencarian'
            ]);
            // $this->addError('startCode',  "Mohon lengkapi tanggal awal atau pilih customer");
            return;
        }

        $this->resetErrorBag();

        // Jika tanggal kosong, gunakan range yang sangat luas
        if (isNullOrEmptyNumber($this->startCode)) {
            $startDate = '1900-01-01';
            $endDate = '2099-12-31';
        } else {
            $startDate = addslashes($this->startCode);
            $endDate = addslashes($this->endCode ?: $this->startCode);
        }

        // Build filter conditions
        $partnerFilter = $this->filterPartner ? "AND p.id = {$this->filterPartner}" : "";
        $brandFilter = $this->filterBrand ? "AND m.brand = '" . addslashes($this->filterBrand) . "'" : "";

        // Query untuk mendapatkan data Purchase Order
        // Catatan: kolom Kirim/Sisa berasal dari ivt_bals (qty_oh dan qty_fgr) yang dicari berdasarkan matl_code

        $query = "
            SELECT
                oh.tr_code,
                oh.tr_date,
                p.name,
                p.city,
                od.tr_seq,
                od.matl_code,
                od.matl_descr,
                od.qty,
                od.price,
                od.disc_pct,
                od.amt,
                COALESCE(ib.qty_oh, 0)  AS kirim,
                COALESCE(ib.qty_fgr, 0) AS sisa
            FROM order_hdrs oh
            JOIN order_dtls od ON od.trhdr_id = oh.id
            JOIN partners p ON p.id = oh.partner_id
            LEFT JOIN materials m ON m.code = od.matl_code AND m.deleted_at IS NULL
            LEFT JOIN ivt_bals ib ON ib.matl_code = od.matl_code
            WHERE oh.tr_type = 'PO'
                AND oh.tr_date BETWEEN '{$startDate}' AND '{$endDate}'
                {$partnerFilter}
                {$brandFilter}
                AND oh.deleted_at IS NULL
                AND od.deleted_at IS NULL
            ORDER BY oh.tr_code, od.tr_seq
        ";

        // Execute query
        $rows = DB::connection(Session::get('app_code'))->select($query);

        // Group data by nota untuk format yang sesuai
        $groupedResults = [];
        $currentNota = null;
        $notaData = null;

        foreach ($rows as $row) {
            if ($currentNota !== $row->tr_code) {
                // Save previous nota if exists
                if ($notaData) {
                    $groupedResults[] = $notaData;
                }

                // Start new nota
                $currentNota = $row->tr_code;
                $notaData = [
                    'no_nota' => $row->tr_code,
                    'tgl_nota' => $row->tr_date,
                    'nama_customer' => $row->name . ($row->city ? ' - ' . $row->city : ''),
                    'items' => []
                ];
            }

            // Tambahkan item ke nota saat ini
            $notaData['items'][] = [
                'kode' => $row->matl_code,
                'nama_barang' => $row->matl_descr,
                'qty' => $row->qty,
                'harga' => $row->price,
                'disc' => $row->disc_pct,
                'total' => $row->amt,
                'kirim' => $row->kirim,
                'sisa' => $row->sisa,
            ];
        }

        // Add last nota
        if ($notaData) {
            $groupedResults[] = $notaData;
        }

        // Assign results
        $this->results = $groupedResults;
    }

    public function resetFilters()
    {
        $this->startCode = '';
        $this->endCode = '';
        $this->filterPartner = '';
        $this->filterBrand = '';
        $this->results = [];
    }


    public function getPartnerOptions()
    {
        $partners = Partner::query()
            ->select('id', 'name', 'city')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $options = [['value' => '', 'label' => 'Semua Customer']];

        foreach ($partners as $partner) {
            $label = $partner->name;
            if ($partner->city) {
                $label .= ' - ' . $partner->city;
            }
            $options[] = [
                'value' => $partner->id,
                'label' => $label
            ];
        }

        return $options;
    }

    public function getBrandOptions()
    {
        $brands = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->select('brand')
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->whereNull('deleted_at')
            ->distinct()
            ->orderBy('brand')
            ->get();

        $options = [['value' => '', 'label' => 'Semua Brand']];

        foreach ($brands as $b) {
            $options[] = [
                'value' => $b->brand,
                'label' => $b->brand
            ];
        }

        return $options;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function resetResult()
    {
        $this->results = [];
    }

    public function onPartnerChanged()
    {
        // Method ini akan dipanggil ketika partner dipilih dari dropdown search
        // Tidak perlu melakukan apa-apa khusus karena filterPartner sudah ter-update otomatis
    }

    public function downloadExcel()
    {
        // Validasi: pastikan ada data untuk di-export
        if (empty($this->results)) {
            $this->dispatch('notify-swal', [
                'type' => 'warning',
                'message' => 'Tidak ada data untuk di-export. Mohon lakukan pencarian terlebih dahulu.'
            ]);
            return;
        }

        try {
            // Siapkan data untuk Excel
            $excelData = [];
            $rowStyles = [];
            $currentRowIndex = 0;

            foreach ($this->results as $nota) {
                // Hitung subtotal untuk nota ini
                $subTotalAmount = 0;
                foreach ($nota['items'] as $item) {
                    $subTotalAmount += $item['total'];
                }
                $ppn = round($subTotalAmount * 0.11, 0);
                $grandTotal = $subTotalAmount + $ppn;

                // Tambahkan header nota (baris kuning) - baris pertama dengan label
                $excelData[] = [
                    'No. Nota',
                    'T. Order',
                    'Nama Supplier',
                    '',
                    '',
                    'Total',
                    'PPN',
                    'Total Nota'
                ];

                // Styling untuk header labels (background kuning)
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'backgroundColor' => 'FFF6B1', // Kuning muda
                    'bold' => true,
                    'borderTop' => true,
                    'borderBottom' => false
                ];
                $currentRowIndex++;

                // Tambahkan nilai header nota - baris kedua dengan nilai
                $excelData[] = [
                    $nota['no_nota'],
                    $nota['tgl_nota'] ? Carbon::parse($nota['tgl_nota'])->format('d-M-y') : '',
                    $nota['nama_customer'],
                    '',
                    '',
                   $subTotalAmount,
                   $ppn,
                   $grandTotal,
                ];

                // Styling untuk header values (background kuning)
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'backgroundColor' => 'FFF6B1', // Kuning muda
                    'bold' => true,
                    'borderTop' => false,
                    'borderBottom' => true
                ];
                $currentRowIndex++;

                // Tambahkan header kolom detail
                $excelData[] = [
                    'Kode Brg.',
                    'Nama Barang',
                    'Order',
                    'Harga',
                    'Disc.',
                    'Total',
                    'Kirim',
                    'Sisa'
                ];

                // Styling untuk header kolom detail
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'bold' => true,
                    'borderTop' => true
                ];
                $currentRowIndex++;

                // Tambahkan data items
                foreach ($nota['items'] as $item) {
                    $excelData[] = [
                        $item['kode'],
                        $item['nama_barang'],
                       $item['qty'],
                       $item['harga'],
                       $item['disc'],
                       $item['total'],
                       $item['kirim'],
                        $item['sisa'],
                    ];
                    $currentRowIndex++;
                }

                // Tambahkan baris kosong sebagai pemisah antar nota
                $excelData[] = ['', '', '', '', '', '', '', ''];
                $currentRowIndex++;
            }

            // Buat title dan subtitle
            $title = 'LAPORAN ORDER BARANG';
            $subtitle = 'Periode: ' .
                ($this->startCode ? Carbon::parse($this->startCode)->format('d-M-Y') : '-') .
                ' s/d ' .
                ($this->endCode ? Carbon::parse($this->endCode)->format('d-M-Y') : '-');

            // Tambahkan filter info jika ada
            if ($this->filterPartner || $this->filterBrand) {
                $filters = [];
                if ($this->filterPartner) {
                    $partner = Partner::find($this->filterPartner);
                    $filters[] = $partner ? $partner->name : 'Supplier Tidak Ditemukan';
                }
                if ($this->filterBrand) {
                    $filters[] = 'Brand: ' . $this->filterBrand;
                }
                $subtitle .= ' | ' . implode(' | ', $filters);
            }

            // Konfigurasi sheet Excel
            $sheets = [[
                'name' => 'Laporan_Order_Barang',
                'headers' => [], // Kosongkan headers karena kita buat custom header sendiri
                'data' => $excelData,
                'protectedColumns' => [],
                'allowInsert' => false,
                'title' => $title,
                'subtitle' => $subtitle,
                'titleAlignment' => Alignment::HORIZONTAL_LEFT,
                'subtitleAlignment' => Alignment::HORIZONTAL_LEFT,
                'rowStyles' => $rowStyles,
                'columnWidths' => [
                    'A' => 15,  // Kode Brg.
                    'B' => 35,  // Nama Barang
                    'C' => 12,  // Order
                    'D' => 15,  // Harga
                    'E' => 10,  // Disc.
                    'F' => 15,  // Total
                    'G' => 12,  // Kirim
                    'H' => 12   // Sisa
                ],
            ]];

            $filename = 'Laporan_Order_Barang_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();

        } catch (\Exception $e) {
            $this->dispatch('error', 'Error generating Excel: ' . $e->getMessage());
            return;
        }
    }
}
