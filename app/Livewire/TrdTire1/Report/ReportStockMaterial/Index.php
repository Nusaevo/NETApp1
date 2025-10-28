<?php

namespace App\Livewire\TrdTire1\Report\ReportStockMaterial;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\Util\GenericExcelExport;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Index extends BaseComponent
{
    public $codeSalesreward;
    public $brand; // tambah property untuk brand
    public $brandOptions; // tambah property untuk brand options
    public $category;
    public $startCode;
    public $endCode;
    public $beg_date; // tanggal awal dari sales reward
    public $end_date; // tanggal akhir dari sales reward
    protected $masterService;
    public $results = [];

    protected $listeners = [
        'onSrCodeChanged'
    ];

    protected function onPreRender()
    {
        // Ambil brand dari query raw SQL untuk dropdown
        $query = "
            SELECT DISTINCT m.brand
            FROM ivt_bals b
            JOIN materials m ON m.id = b.matl_id
            WHERE (b.qty_oh > 0 OR b.qty_fgi > 0 OR b.qty_fgr > 0)
            AND m.deleted_at IS NULL
        ";
        $this->brandOptions = collect(DB::connection(Session::get('app_code'))->select($query))
            ->map(function ($item) {
                return [
                    'value' => $item->brand,
                    'label' => $item->brand,
                ];
            })->toArray();
    }
    public function search()
    {
        $this->resetErrorBag();

        $brand = addslashes($this->brand);

        $query = "
            SELECT code, name,
                sum(qty_oh_g01) g01,
                sum(qty_oh_g02) g02,
                sum(qty_oh_g04) g04,
                sum(qty_fgi) fgi
            from (
                SELECT m.code, m.name,
                    case when b.wh_code = 'G01' then qty_oh else 0 end qty_oh_g01,
                    case when b.wh_code = 'G02' then qty_oh else 0 end qty_oh_g02,
                    case when b.wh_code = 'G04' then qty_oh else 0 end qty_oh_g04,
                    case when b.wh_code = '' then qty_fgi else 0 end qty_fgi
                FROM ivt_bals b
                join materials m on m.id = b.matl_id
                " . ($brand ? "and m.brand = '{$brand}'" : "") . "
                // where (b.qty_oh != 0 or b.qty_fgi != 0)
                and m.deleted_at IS NULL
            ) a
            group by code, name
            order by code
        ";

        $this->results = DB::connection(Session::get('app_code'))->select($query);
    }

    public function resetFilters()
    {
        $this->brand = '';
        $this->brandOptions = [];
        $this->category = '';
        $this->startCode = '';
        $this->endCode = '';
        $this->results = [];
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

            foreach ($this->results as $row) {
                $rowTotal = ($row->g01 ?? 0) + ($row->g02 ?? 0) + ($row->g04 ?? 0);

                $excelData[] = [
                    $row->code ?? '',
                    $row->name ?? '',
                    is_numeric($row->g01 ?? null) ? rtrim(rtrim(number_format($row->g01, 3, '.', ''), '0'), '.') : '',
                    is_numeric($row->g02 ?? null) ? rtrim(rtrim(number_format($row->g02, 3, '.', ''), '0'), '.') : '',
                    is_numeric($row->g04 ?? null) ? rtrim(rtrim(number_format($row->g04, 3, '.', ''), '0'), '.') : '',
                    is_numeric($rowTotal) ? rtrim(rtrim(number_format($rowTotal, 3, '.', ''), '0'), '.') : '',
                    isset($row->fgi) && is_numeric($row->fgi) ? (string)round($row->fgi, 0) : '0',
                    isset($row->point) && is_numeric($row->point) ? (string)$row->point : '0',
                ];
            }

            // Buat title dan subtitle
            $title = 'LAPORAN STOK BARANG';
            $subtitle = 'Per Tanggal: ' . \Carbon\Carbon::now()->format('d-M-Y');

            // Tambahkan filter info jika ada
            $filters = [];
            if ($this->brand) {
                $filters[] = 'Brand: ' . $this->brand;
            }


            if (!empty($filters)) {
                $subtitle .= ' | ' . implode(' | ', $filters);
            }

            // Konfigurasi sheet Excel
            $sheets = [[
                'name' => 'Laporan_Stok_Barang',
                'headers' => [
                    'Kode',
                    'Nama Barang',
                    'G01',
                    'G02',
                    'G04',
                    'Total',
                    'FGI',
                    'Point'
                ],
                'data' => $excelData,
                'protectedColumns' => [],
                'allowInsert' => false,
                'title' => $title,
                'subtitle' => $subtitle,
                'titleAlignment' => Alignment::HORIZONTAL_LEFT,
                'subtitleAlignment' => Alignment::HORIZONTAL_LEFT,
                'rowStyles' => [],
            ]];

            // Buat filename yang mencerminkan filter
            $filename = 'Laporan_Stok_Barang_';
            if ($this->brand) {
                $filename .= str_replace(' ', '_', $this->brand);
            } else {
                $filename .= 'Semua';
            }
            $filename .= '_' . now()->format('d-m-Y') . '.xlsx';

            return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();

        } catch (\Exception $e) {
            $this->dispatch('error', 'Error generating Excel: ' . $e->getMessage());
            return;
        }
    }
}
