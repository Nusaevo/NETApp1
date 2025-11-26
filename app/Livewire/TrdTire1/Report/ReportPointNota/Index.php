<?php

namespace App\Livewire\TrdTire1\Report\ReportPointNota;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\Util\GenericExcelExport;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class Index extends BaseComponent
{
    public $codeSalesreward;
    public $category;
    public $startCode;
    public $endCode;
    public $beg_date; // tanggal awal dari sales reward
    public $end_date; // tanggal akhir dari sales reward
    public $brand; // brand dari sales reward
    public $point_flag = false; // checkbox untuk semua point
    protected $masterService;

    public $results = [];
    public $grandTotalBan = 0;
    public $grandTotalPoint = 0;

    protected $listeners = [
        'onSrCodeChanged'=>'onSrCodeChanged',
        'DropdownSelected' => 'DropdownSelected'
    ];

    protected function onPreRender()
    {
        // Ambil code unik dari sales_rewards untuk dropdown Merk (distinct code, beg_date, end_date)
        $this->codeSalesreward = SalesReward::query()
            ->selectRaw('DISTINCT code, descrs, beg_date, end_date')
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                // Label: code - descrs
                $label = $item->code . ' - ' . ($item->descrs ?? '');
                return [
                    'value' => $item->code,
                    'label' => $label,
                    'beg_date' => $item->beg_date,
                    'end_date' => $item->end_date,
                ];
            })
            ->toArray();

        $this->masterService = new MasterService();
        $this->resetFilters();
    }

    public function onSrCodeChanged()
    {
        $salesReward = SalesReward::where('code', $this->category)->first();
        if ($salesReward) {
            $this->startCode = $salesReward->beg_date ? date('Y-m-d', strtotime($salesReward->beg_date)) : '';
            $this->endCode = $salesReward->end_date ? date('Y-m-d', strtotime($salesReward->end_date)) : '';
            $this->beg_date = $this->startCode;
            $this->end_date = $this->endCode;
            $this->brand = $salesReward->brand;
        } else {
            $this->startCode = '';
            $this->endCode = '';
            $this->beg_date = '';
            $this->end_date = '';
            $this->brand = '';
            $this->dispatch('error', 'Sales Reward tidak ditemukan.');
        }
    }

    public function search()
    {
        // dd($bindings);
        if (isNullOrEmptyNumber($this->category)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Code"]));
            $this->addError('category', "Mohon lengkapi");
            return;
        }

        if (isNullOrEmptyNumber($this->startCode)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Tanggal Awal"]));
            $this->addError('startCode',  "Mohon lengkapi");
            return;
        }

        $this->resetErrorBag();

        $bindings = [
            'sr_code' => $this->category,
            'brand' => $this->brand,
        ];
        $whereDate = '';
        if (!empty($this->startCode)) {
            $whereDate .= " AND oh.tr_date >= :start_date";
            $bindings['start_date'] = $this->startCode;
        }
        if (!empty($this->endCode)) {
            $whereDate .= " AND oh.tr_date <= :end_date";
            $bindings['end_date'] = $this->endCode;
        }

        // Tentukan jenis JOIN berdasarkan checkbox point_flag
        $salesRewardJoin = $this->point_flag ? "LEFT OUTER JOIN" : "JOIN";

        // Tambahkan kondisi WHERE untuk memfilter data ketika tidak menggunakan LEFT OUTER JOIN
        $whereSalesReward = $this->point_flag ? "" : " AND sr.reward IS NOT NULL";

        $query = "
            SELECT
                oh.tr_date AS tgl_nota,
                oh.tr_code AS no_nota,
                od.matl_code AS kode_brg,
                od.matl_descr AS nama_barang,
                CASE
                    -- Logika CUSTOMER LAIN-LAIN untuk GT RADIAL atau GAJAH TUNGGAL
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand IN ('GT RADIAL', 'GAJAH TUNGGAL')
                         AND (p.partner_chars->>'GT' = 'false' OR p.partner_chars->>'GT' IS NULL)
                    THEN 'CUSTOMER LAIN-LAIN'
                    -- Logika CUSTOMER LAIN-LAIN untuk IRC
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand = 'IRC'
                         AND (p.partner_chars->>'IRC' = 'false' OR p.partner_chars->>'IRC' IS NULL)
                    THEN 'CUSTOMER LAIN-LAIN'
                    -- Logika CUSTOMER LAIN-LAIN untuk ZENEOS
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand = 'ZENEOS'
                         AND (p.partner_chars->>'ZN' = 'false' OR p.partner_chars->>'ZN' IS NULL)
                    THEN 'CUSTOMER LAIN-LAIN'
                    -- Default: tampilkan nama customer normal
                    ELSE p.name
                END AS nama_pelanggan,
                CASE
                    -- Jika CUSTOMER LAIN-LAIN, kosongkan kota
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand IN ('GT RADIAL', 'GAJAH TUNGGAL')
                         AND (p.partner_chars->>'GT' = 'false' OR p.partner_chars->>'GT' IS NULL)
                    THEN ''
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand = 'IRC'
                         AND (p.partner_chars->>'IRC' = 'false' OR p.partner_chars->>'IRC' IS NULL)
                    THEN ''
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand = 'ZENEOS'
                         AND (p.partner_chars->>'ZN' = 'false' OR p.partner_chars->>'ZN' IS NULL)
                    THEN ''
                    ELSE p.city
                END AS kota_pelanggan,
                od.qty AS total_ban,
                COALESCE(sr.reward, 0) AS point,
                (od.qty * COALESCE(sr.reward, 0)) AS total_point,
                -- Field untuk sorting: 1 untuk CUSTOMER LAIN-LAIN, 0 untuk customer normal
                CASE
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand IN ('GT RADIAL', 'GAJAH TUNGGAL')
                         AND (p.partner_chars->>'GT' = 'false' OR p.partner_chars->>'GT' IS NULL)
                    THEN 1
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand = 'IRC'
                         AND (p.partner_chars->>'IRC' = 'false' OR p.partner_chars->>'IRC' IS NULL)
                    THEN 1
                    WHEN sr.brand IS NOT NULL
                         AND sr.brand = 'ZENEOS'
                         AND (p.partner_chars->>'ZN' = 'false' OR p.partner_chars->>'ZN' IS NULL)
                    THEN 1
                    ELSE 0
                END AS is_lain_lain
            FROM order_dtls od
            JOIN order_hdrs oh ON oh.id = od.trhdr_id AND oh.tr_type = 'SO' AND oh.status_code != 'X'
            JOIN partners p ON p.id = oh.partner_id
            $salesRewardJoin sales_rewards sr ON sr.code = :sr_code AND sr.matl_code = od.matl_code
            JOIN materials m ON m.id = od.matl_id AND m.brand = :brand
            WHERE od.tr_type = 'SO'
                $whereDate
                $whereSalesReward
            ORDER BY is_lain_lain ASC, nama_pelanggan, oh.tr_date ASC, oh.tr_code, od.matl_code
        ";
        // dd($query);

        $rows = DB::connection(Session::get('app_code'))->select($query, $bindings);

        // Grouping per customer
        $grouped = [];
        foreach ($rows as $row) {
            // Untuk CUSTOMER LAIN-LAIN, tidak perlu menambahkan kota
            if ($row->nama_pelanggan === 'CUSTOMER LAIN-LAIN') {
                $customerKey = 'CUSTOMER LAIN-LAIN';
            } else {
                $customerKey = $row->nama_pelanggan . ' - ' . $row->kota_pelanggan;
            }

            if (!isset($grouped[$customerKey])) {
                $grouped[$customerKey] = [
                    'customer' => $customerKey,
                    'details' => [],
                    'total_ban' => 0,
                    'total_point' => 0,
                ];
            }
            $grouped[$customerKey]['details'][] = $row;
            $grouped[$customerKey]['total_ban'] += $row->total_ban;
            $grouped[$customerKey]['total_point'] += $row->total_point;
        }

        // Sort hasil: CUSTOMER LAIN-LAIN muncul di akhir
        uasort($grouped, function($a, $b) {
            $aIsLainLain = $a['customer'] === 'CUSTOMER LAIN-LAIN';
            $bIsLainLain = $b['customer'] === 'CUSTOMER LAIN-LAIN';

            // Jika salah satu adalah CUSTOMER LAIN-LAIN, letakkan di akhir
            if ($aIsLainLain && !$bIsLainLain) {
                return 1; // $a di akhir
            }
            if (!$aIsLainLain && $bIsLainLain) {
                return -1; // $b di akhir
            }
            // Jika keduanya sama (keduanya LAIN-LAIN atau keduanya normal), urutkan berdasarkan nama
            return strcmp($a['customer'], $b['customer']);
        });

        $this->results = array_values($grouped);

        // Calculate grand totals
        $this->grandTotalBan = array_sum(array_column($this->results, 'total_ban'));
        $this->grandTotalPoint = array_sum(array_column($this->results, 'total_point'));
    }

    public function resetFilters()
    {
        $this->category = '';
        $this->startCode = '';
        $this->endCode = '';
        $this->point_flag = false;
        $this->results = [];
        $this->grandTotalBan = 0;
        $this->grandTotalPoint = 0;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function resetResult()
    {
        $this->results = [];
        $this->grandTotalBan = 0;
        $this->grandTotalPoint = 0;
    }

    public function downloadExcel()
    {
        try {
            if (empty($this->results)) {
                $this->dispatch('warning', 'Tidak ada data untuk di-export. Silakan lakukan pencarian terlebih dahulu.');
                return;
            }

            // Prepare Excel data with custom header structure
            $excelData = [];
            $rowStyles = [];
            $mergeCells = [];
            $currentRowIndex = 0;

            // Add custom header rows to match the screen layout
            // First header row - "Nama / Alamat Pelanggan" spanning 4 columns
            $excelData[] = [
                'Nama / Alamat Pelanggan',
                '',
                '',
                '',
                '',
                '',
                ''
            ];
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'bold' => true,
                'borderTop' => true,
                'borderBottom' => true,
                'borderLeft' => true,
                'borderRight' => true
            ];
            $mergeCells[] = 'A' . ($currentRowIndex + 1) . ':D' . ($currentRowIndex + 1); // Merge A to D for first header
            $currentRowIndex++;

            // Second header row - column headers
            $excelData[] = [
                'Tgl. Nota',
                'No. Nota',
                'Kode Brg.',
                'Nama Barang',
                'Total Ban',
                'Point',
                'Total Point'
            ];
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'bold' => true,
                'borderBottom' => true,
                'borderLeft' => true,
                'borderRight' => true
            ];
            $currentRowIndex++;

            foreach ($this->results as $groupIndex => $group) {
                // Add detail rows for this customer (no customer header row)
                foreach ($group['details'] as $detail) {
                    $excelData[] = [
                        $detail->tgl_nota ? Carbon::parse($detail->tgl_nota)->format('d-M-Y') : '-',
                        $detail->no_nota,
                        $detail->kode_brg,
                        $detail->nama_barang,
                        fmod($detail->total_ban, 1) == 0 ? number_format($detail->total_ban, 0) : number_format($detail->total_ban, 2),
                        fmod($detail->point, 1) == 0 ? number_format($detail->point, 0) : number_format($detail->point, 2),
                        fmod($detail->total_point, 1) == 0 ? number_format($detail->total_point, 0) : number_format($detail->total_point, 2)
                    ];
                    // Add styling to remove borders from detail rows
                    $rowStyles[] = [
                        'rowIndex' => $currentRowIndex,
                        'removeBorders' => true,
                        'alignment' => Alignment::HORIZONTAL_CENTER,
                        'alignmentCells' => ['E', 'F', 'G'] // Total Ban, Point, dan Total Point
                    ];
                    $currentRowIndex++;
                }

                // Add summary row for this customer with customer name
                $excelData[] = [
                    $group['customer'], // Customer name for total row
                    '',
                    '',
                    '',
                    fmod($group['total_ban'], 1) == 0 ? number_format($group['total_ban'], 0) : number_format($group['total_ban'], 2),
                    '',
                    fmod($group['total_point'], 1) == 0 ? number_format($group['total_point'], 0) : number_format($group['total_point'], 2)
                ];
                $rowStyles[] = [
                    'rowIndex' => $currentRowIndex,
                    'bold' => true,
                    'backgroundColor' => 'F0F8FF',
                    'removeBorders' => true,
                    'alignment' => Alignment::HORIZONTAL_CENTER,
                    'alignmentRange' => ['A', 'D'], // Nama partner (merged A:D)
                    'alignmentCells' => ['E', 'G'] // Total Ban dan Total Point
                ];
                $mergeCells[] = 'A' . ($currentRowIndex + 1) . ':D' . ($currentRowIndex + 1); // Merge A to D for customer total
                $currentRowIndex++;

                // Add empty row between customers (except for the last one)
                if ($groupIndex < count($this->results) - 1) {
                    $excelData[] = ['', '', '', '', '', '', ''];
                    $currentRowIndex++;
                }
            }

            // Add grand total row
            $excelData[] = [
                '',
                '',
                '',
                '',
                fmod($this->grandTotalBan, 1) == 0 ? number_format($this->grandTotalBan, 0) : number_format($this->grandTotalBan, 2),
                '',
                fmod($this->grandTotalPoint, 1) == 0 ? number_format($this->grandTotalPoint, 0) : number_format($this->grandTotalPoint, 2)
            ];
            $rowStyles[] = [
                'rowIndex' => $currentRowIndex,
                'bold' => true,
                'removeBorders' => true,
                'alignment' => Alignment::HORIZONTAL_CENTER,
                'alignmentCells' => ['E', 'G'] // Total Ban dan Total Point
            ];
            $currentRowIndex++;

            // Create title and subtitle
            $title = 'LAPORAN POINT NOTA';
            $subtitle = 'Kode Program: ' . $this->category . ' | Periode: ' .
                ($this->startCode ? Carbon::parse($this->startCode)->format('d-M-Y') : '-') .
                ' s/d ' .
                ($this->endCode ? Carbon::parse($this->endCode)->format('d-M-Y') : '-');

            // Configure Excel sheet
            $sheets = [[
                'name' => 'Laporan_Point_Nota',
                'headers' => [], // Empty headers since we create custom headers in data
                'data' => $excelData,
                'protectedColumns' => [],
                'allowInsert' => false,
                'title' => $title,
                'subtitle' => $subtitle,
                'titleAlignment' => Alignment::HORIZONTAL_LEFT,
                'subtitleAlignment' => Alignment::HORIZONTAL_LEFT,
                'rowStyles' => $rowStyles,
                'mergeCells' => $mergeCells,
                'columnWidths' => [
                    'A' => 15,  // Tgl. Nota
                    'B' => 20,  // No. Nota
                    'C' => 15,  // Kode Brg.
                    'D' => 40,  // Nama Barang
                    'E' => 12,  // Total Ban
                    'F' => 12,  // Point
                    'G' => 15   // Total Point
                ],
            ]];

            $filename = 'Laporan_Point_Nota_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();

        } catch (\Exception $e) {
            $this->dispatch('error', 'Error generating Excel: ' . $e->getMessage());
            return;
        }
    }
}
