<?php

namespace App\Livewire\TrdTire1\Report\ReportTrxMaterial;

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
    public $brand; // brand dari sales reward
    public $point_flag = false; // checkbox untuk semua point
    protected $masterService;

    public $results = [];

    protected $listeners = [
        'onSrCodeChanged'
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
            $whereDate .= " AND dh.tr_date >= :start_date";
            $bindings['start_date'] = $this->startCode;
        }
                if (!empty($this->endCode)) {
            $whereDate .= " AND dh.tr_date <= :end_date";
            $bindings['end_date'] = $this->endCode;
        }

        // Tentukan jenis JOIN berdasarkan checkbox point_flag
        $salesRewardJoin = $this->point_flag ? "LEFT OUTER JOIN" : "JOIN";

        // Tambahkan kondisi WHERE untuk memfilter data ketika tidak menggunakan LEFT OUTER JOIN
        $whereSalesReward = $this->point_flag ? "" : " AND r.reward IS NOT NULL";

        $query = "
            SELECT
                t.matl_code AS kode_brg,
                t.matl_desc AS nama_barang,
                COALESCE(ROUND(r.reward / r.qty, 3), 0) AS point,
                t.qty_beli,
                COALESCE(ROUND(t.qty_beli / r.qty * r.reward, 0), 0) AS point_beli,
                t.qty_jual,
                COALESCE(ROUND(t.qty_jual / r.qty * r.reward, 0), 0) AS point_jual
            FROM (
                SELECT
                    COALESCE(b.matl_id, j.matl_id) AS matl_id,
                    COALESCE(b.code, j.code) AS matl_code,
                    COALESCE(b.name, j.name) AS matl_desc,
                    COALESCE(b.qty_beli, 0) AS qty_beli,
                    COALESCE(j.qty_jual, 0) AS qty_jual
                FROM (
                    SELECT od.matl_id, m.code, m.name, SUM(dp.qty) qty_beli
                    FROM deliv_hdrs dh
                    JOIN deliv_packings dp ON dp.trhdr_id = dh.id
                    JOIN order_dtls od ON od.id = dp.reffdtl_id
                    JOIN materials m ON m.id = od.matl_id AND m.brand = :brand
                    WHERE dh.tr_type = 'PD'
                        $whereDate
                    GROUP BY od.matl_id, m.code, m.name
                ) b
                LEFT JOIN (
                    SELECT dpi.matl_id, m.code, m.name, SUM(dpi.qty) qty_jual
                    FROM deliv_hdrs dh
                    JOIN deliv_pickings dpi ON dpi.trpacking_id IN (
                        SELECT id FROM deliv_packings WHERE trhdr_id = dh.id
                    )
                    JOIN materials m ON m.id = dpi.matl_id AND m.brand = :brand
                    WHERE dh.tr_type = 'SD'
                        $whereDate
                    GROUP BY dpi.matl_id, m.code, m.name
                ) j ON j.matl_id = b.matl_id

                UNION

                SELECT
                    j.matl_id,
                    j.code AS matl_code,
                    j.name AS matl_desc,
                    0 AS qty_beli,
                    j.qty_jual
                FROM (
                    SELECT dpi.matl_id, m.code, m.name, SUM(dpi.qty) qty_jual
                    FROM deliv_hdrs dh
                    JOIN deliv_pickings dpi ON dpi.trpacking_id IN (
                        SELECT id FROM deliv_packings WHERE trhdr_id = dh.id
                    )
                    JOIN materials m ON m.id = dpi.matl_id AND m.brand = :brand
                    WHERE dh.tr_type = 'SD'
                        $whereDate
                    GROUP BY dpi.matl_id, m.code, m.name
                ) j
                LEFT JOIN (
                    SELECT od.matl_id, m.code, m.name, SUM(dp.qty) qty_beli
                    FROM deliv_hdrs dh
                    JOIN deliv_packings dp ON dp.trhdr_id = dh.id
                    JOIN order_dtls od ON od.id = dp.reffdtl_id
                    JOIN materials m ON m.id = od.matl_id AND m.brand = :brand
                    WHERE dh.tr_type = 'PD'
                        $whereDate
                    GROUP BY od.matl_id, m.code, m.name
                ) b ON b.matl_id = j.matl_id
                WHERE b.matl_id IS NULL
            ) t
            $salesRewardJoin sales_rewards r ON r.matl_code = t.matl_code AND r.code = :sr_code
            WHERE 1=1 $whereSalesReward
            ORDER BY t.matl_code
        ";
        // dd($query);

        $rows = DB::connection(Session::get('app_code'))->select($query, $bindings);

        // Langsung gunakan hasil query tanpa grouping karena sudah dalam format yang diinginkan
        $this->results = $rows;
    }

    public function resetFilters()
    {
        $this->category = '';
        $this->startCode = '';
        $this->endCode = '';
        $this->point_flag = false;
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
