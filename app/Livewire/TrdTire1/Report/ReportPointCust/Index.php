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
            ->selectRaw('code as value, code as label')
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get()
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

        $bindings = [
            'sr_code' => $this->category,
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

        $query = "
            SELECT
                oh.tr_code AS no_nota,
                od.matl_code AS kode_brg,
                od.matl_descr AS nama_barang,
                p.name AS nama_pelanggan,
                p.city AS kota_pelanggan,
                od.qty AS total_ban,
                sr.reward AS point,
                (od.qty * sr.reward) AS total_point
            FROM order_dtls od
            JOIN order_hdrs oh ON oh.id = od.trhdr_id AND oh.tr_type = 'SO' AND oh.deleted_at IS NULL
            JOIN partners p ON p.id = oh.partner_id
            JOIN materials m ON m.id = od.matl_id
            LEFT JOIN sales_rewards sr ON sr.code = :sr_code AND sr.matl_code = od.matl_code
            WHERE od.tr_type = 'SO'
                AND od.deleted_at IS NULL
                $whereDate
                AND sr.code IS NOT NULL
            ORDER BY p.name, oh.tr_code, od.matl_code
        ";

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
