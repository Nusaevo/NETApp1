<?php

namespace App\Livewire\TrdTire1\Report\ReportPointCust;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\SalesReward;

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
}
