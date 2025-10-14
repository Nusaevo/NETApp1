<?php

namespace App\Livewire\TrdTire1\Report\ReportPointCust;

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
        // Ambil code dari sales_rewards untuk dropdown Merk
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
        } else {
            $this->startCode = '';
            $this->endCode = '';
            $this->beg_date = '';
            $this->end_date = '';
            $this->dispatch('error', 'Sales Reward tidak ditemukan.');
        }
    }

    public function search()
    {
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

        // 1. Get dynamic columns for crosstab
        $code = addslashes($this->category);
        $startDate = addslashes($this->startCode);
        $endDate = addslashes($this->endCode ?: $this->startCode);

        // Change column type to text for crosstab
        $colQuery = "SELECT string_agg(DISTINCT format('\"%s\" text', grp), ', ') AS cols FROM sales_rewards WHERE code = '{$code}'";
        $colResult = DB::connection(Session::get('app_code'))->selectOne($colQuery);
        $cols = $colResult ? $colResult->cols : '';

        if (!$cols) {
            $this->dispatch('warning', 'Tidak ada grup pada sales reward ini.');
            $this->results = [];
            return;
        }

        // 2. Build crosstab query (inject parameter directly)
        $crosstab = "
            SELECT * FROM crosstab(
                \$ct\$
                SELECT
                    CASE
                        WHEN r.brand = 'GT RADIAL' THEN
                            CASE WHEN (p.partner_chars->>'GT')::bool
                                THEN p.name || ' - ' || p.city
                                ELSE '_CUSTOMER GTR'
                            END
                        WHEN r.brand = 'GAJAH TUNGGAL' THEN
                            CASE WHEN (p.partner_chars->>'GT')::bool
                                THEN p.name || ' - ' || p.city
                                ELSE '_CUSTOMER GTL'
                            END
                        WHEN r.brand = 'ZENEOS' THEN
                            CASE WHEN (p.partner_chars->>'ZN')::bool
                                THEN p.name || ' - ' || p.city
                                ELSE '_CUSTOMER ZN'
                            END
                        WHEN r.brand = 'IRC' THEN
                            CASE WHEN (p.partner_chars->>'IRC')::bool
                                THEN p.name || ' - ' || p.city
                                ELSE '_CUSTOMER IRC'
                            END
                        ELSE ''
                    END AS customer,
                    r.grp,
                    SUM(d.qty)::int || '|' || SUM(TRUNC(d.qty / r.qty) * r.reward)::int AS point
                FROM order_hdrs h
                JOIN order_dtls d
                  ON d.tr_code = h.tr_code
                 AND d.tr_type = h.tr_type
                 AND d.qty = d.qty_reff
                JOIN materials m ON m.code = d.matl_code
                JOIN partners p ON p.code = h.partner_code
                JOIN sales_rewards r
                  ON r.matl_code = d.matl_code
                 AND r.code = '{$code}'
                WHERE h.tr_type = 'SO'
                  AND h.status_code <> 'X'
                  AND h.tr_date BETWEEN '{$startDate}' AND '{$endDate}'
                GROUP BY 1, r.grp
                ORDER BY 1, 2
                \$ct\$::text,

                \$key\$
                SELECT DISTINCT grp
                FROM sales_rewards
                WHERE code = '{$code}'
                ORDER BY grp
                \$key\$::text
            ) AS ct(customer text, $cols)
        ";

        // 3. Execute crosstab query (no bindings)
        $rows = DB::connection(Session::get('app_code'))->select($crosstab);


        // 4. Assign results
        $this->results = $rows;
        // dd($this->results);
        // dd($this->results);
    }

    public function resetFilters()
    {
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
        // Pastikan ada data
        if (empty($this->results)) {
            $this->dispatch('notify-swal', [
                'type' => 'warning',
                'message' => 'Tidak ada data untuk di-export. Mohon lakukan pencarian terlebih dahulu.'
            ]);
            return;
        }

        try {
            // Tentukan kolom dinamis dari hasil crosstab
            $columns = [];
            if (count($this->results)) {
                $columns = array_keys((array)$this->results[0]);
            }
            $groupColumns = array_values(array_filter($columns, fn($c) => $c !== 'customer'));

            // Header: Customer, untuk setiap grup 1 kolom gabungan "point|ban", lalu kolom Total gabungan
            $headers = ['Customer'];
            foreach ($groupColumns as $grpCol) {
                $headers[] = $grpCol; // nilai sel: "point|ban"
            }
            $headers[] = 'Total';

            // Data rows
            $excelData = [];
            foreach ($this->results as $row) {
                $dataRow = [];
                $customer = $row->customer ?? '';
                $dataRow[] = $customer;

                $rowTotalQty = 0;
                $rowTotalPoint = 0;
                foreach ($groupColumns as $grpCol) {
                    $val = $row->$grpCol ?? '';
                    $parts = explode('|', $val);
                    $qty = isset($parts[0]) ? (int)$parts[0] : 0;   // ban
                    $point = isset($parts[1]) ? (int)$parts[1] : 0; // point
                    $rowTotalQty += $qty;
                    $rowTotalPoint += $point;
                    // format: point|ban
                    $dataRow[] = ($point ?: 0) . '|' . ($qty ?: 0);
                }
                // total gabungan point|ban
                $dataRow[] = ($rowTotalPoint ?: 0) . '|' . ($rowTotalQty ?: 0);
                $excelData[] = $dataRow;
            }

            // Title & subtitle
            $title = 'DATA PENJUALAN GT RADIAL per Customer';
            $subtitleParts = [];
            if ($this->category) {
                $subtitleParts[] = 'Program: ' . $this->category;
            }
            if ($this->startCode || $this->endCode) {
                $subtitleParts[] = 'Periode: ' . ($this->startCode ? \Carbon\Carbon::parse($this->startCode)->format('d-M-Y') : '-')
                    . ' s/d ' . ($this->endCode ? \Carbon\Carbon::parse($this->endCode)->format('d-M-Y') : '-');
            }
            $subtitle = implode(' | ', $subtitleParts);

            $sheets = [[
                'name' => 'Report_Point_Customer',
                'headers' => $headers,
                'data' => $excelData,
                'protectedColumns' => [],
                'allowInsert' => false,
                'title' => $title,
                'subtitle' => $subtitle,
                'titleAlignment' => Alignment::HORIZONTAL_LEFT,
                'subtitleAlignment' => Alignment::HORIZONTAL_LEFT,
                'rowStyles' => [],
            ]];

            // Filename
            $filename = 'Report_Point_Customer_';
            $filename .= ($this->category ? str_replace(' ', '_', $this->category) : 'All');
            if ($this->startCode || $this->endCode) {
                $filename .= '_' . ($this->startCode ? date('Ymd', strtotime($this->startCode)) : '');
                $filename .= '-' . ($this->endCode ? date('Ymd', strtotime($this->endCode)) : '');
            }
            $filename .= '.xlsx';

            return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Error generating Excel: ' . $e->getMessage());
            return;
        }
    }
}
