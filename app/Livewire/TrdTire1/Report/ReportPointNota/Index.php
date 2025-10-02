<?php

namespace App\Livewire\TrdTire1\Report\ReportPointNota;

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
                p.name AS nama_pelanggan,
                p.city AS kota_pelanggan,
                od.qty AS total_ban,
                COALESCE(sr.reward, 0) AS point,
                (od.qty * COALESCE(sr.reward, 0)) AS total_point
            FROM order_dtls od
            JOIN order_hdrs oh ON oh.id = od.trhdr_id AND oh.tr_type = 'SO' AND oh.status_code = 'X'
            JOIN partners p ON p.id = oh.partner_id
            $salesRewardJoin sales_rewards sr ON sr.code = :sr_code AND sr.matl_code = od.matl_code
            JOIN materials m ON m.id = od.matl_id AND m.brand = :brand
            WHERE od.tr_type = 'SO'
                $whereDate
                $whereSalesReward
            ORDER BY p.name, oh.tr_code, od.matl_code
        ";
        // dd($query);

        $rows = DB::connection(Session::get('app_code'))->select($query, $bindings);

        // Grouping per customer
        $grouped = [];
        foreach ($rows as $row) {
            $customerKey = $row->nama_pelanggan . ' - ' . $row->kota_pelanggan;
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

        $this->results = array_values($grouped);
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
