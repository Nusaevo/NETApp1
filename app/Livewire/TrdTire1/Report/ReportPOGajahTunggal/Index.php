<?php

namespace App\Livewire\TrdTire1\Report\ReportPOGajahTunggal;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session, Log};
use App\Services\TrdTire1\Master\MasterService;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\TrdTire1\Transaction\DelivHdr;

class Index extends BaseComponent
{
    public $printDateOptions; // Dropdown tanggal tagih
    public $selectedPrintDate; // Tanggal tagih yang dipilih
    public $brandOptions = [];
    public $categoryOptions = [];
    public $selectedBrand = '';
    public $selectedCategory = '';
    public $startPrintDate;
    public $endPrintDate;
    public $selectedRewardCode = '';
    public $rewardOptions = [];
    protected $masterService;

    public $results = [];

    protected $listeners = [
        'onSrCodeChanged'
    ];

    protected function onPreRender()
    {
        // Ambil distinct print_date dari billing_hdrs untuk dropdown
        $this->printDateOptions = DB::connection(Session::get('app_code'))
            ->table('billing_hdrs')
            ->selectRaw('DISTINCT print_date')
            ->whereNull('deleted_at')
            ->whereNotNull('print_date')
            ->orderBy('print_date', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->print_date,
                    'label' => date('d-m-Y', strtotime($item->print_date)),
                ];
            })
            ->toArray();
        $this->printDateOptions = array_map(fn($v) => (array)$v, $this->printDateOptions);

        // Ambil distinct brand dan category dari tabel materials
        $this->brandOptions = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->selectRaw('DISTINCT brand as value, brand as label')
            ->whereNull('deleted_at')
            ->whereNotNull('brand')
            ->orderBy('brand')
            ->get()
            ->toArray();
        $this->brandOptions = array_map(fn($v) => (array)$v, $this->brandOptions);
        $this->categoryOptions = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->selectRaw('DISTINCT category as value, category as label')
            ->whereNull('deleted_at')
            ->whereNotNull('category')
            ->orderBy('category')
            ->get()
            ->toArray();
        $this->categoryOptions = array_map(fn($v) => (array)$v, $this->categoryOptions);

        // Ambil kode program (sales reward) untuk dropdown dengan tanggal
        $this->rewardOptions = SalesReward::query()
            ->selectRaw('code, descrs, beg_date, end_date')
            ->whereNull('deleted_at')
            ->groupBy('code', 'descrs', 'beg_date', 'end_date')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
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
        $salesReward = SalesReward::where('code', $this->selectedRewardCode)->first();
        if ($salesReward) {
            $this->startPrintDate = $salesReward->beg_date ? date('Y-m-d', strtotime($salesReward->beg_date)) : '';
            $this->endPrintDate = $salesReward->end_date ? date('Y-m-d', strtotime($salesReward->end_date)) : '';
        } else {
            $this->startPrintDate = '';
            $this->endPrintDate = '';
            $this->dispatch('error', 'Sales Reward tidak ditemukan.');
        }
    }

    public function search()
    {
        if (isNullOrEmptyNumber($this->selectedRewardCode)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Kode Program"]));
            $this->addError('selectedRewardCode', "Mohon lengkapi");
            return;
        }
        if (isNullOrEmptyNumber($this->startPrintDate) || isNullOrEmptyNumber($this->endPrintDate)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Periode Tanggal Tagih"]));
            $this->addError('startPrintDate', "Mohon lengkapi");
            $this->addError('endPrintDate', "Mohon lengkapi");
            return;
        }
        $this->resetErrorBag();
        $startDate = addslashes($this->startPrintDate);
        $endDate = addslashes($this->endPrintDate);
        $rewardCode = addslashes($this->selectedRewardCode);
        $brand = addslashes($this->selectedBrand);
        $category = addslashes($this->selectedCategory);
        $brandFilter = $brand ? "AND m.brand = '{$brand}'" : '';
        $categoryFilter = $category ? "AND m.category = '{$category}'" : '';
        $query = "
            SELECT
                dh.reff_date AS tgl_sj,
                dh.tr_code AS no_nota,
                dpi.matl_code AS kode_brg,
                dp.matl_descr AS nama_barang,
                p.name AS nama_pelanggan,
                p.city AS kota_pelanggan,
                dp.qty AS total_ban,
                COALESCE(sr.reward, 0) AS point,
                (dp.qty * COALESCE(sr.reward, 0)) AS total_point
            FROM deliv_packings dp
            JOIN deliv_hdrs dh ON dh.id = dp.trhdr_id AND dh.tr_type = 'PD'
            JOIN partners p ON p.id = dh.partner_id
            JOIN deliv_pickings dpi ON dpi.trpacking_id = dp.id
            JOIN materials m ON m.id = dpi.matl_id
            JOIN sales_rewards sr ON sr.code = '{$rewardCode}'
                AND sr.matl_code = dpi.matl_code
                AND sr.brand = m.brand
            WHERE dp.tr_type = 'PD'
                AND dh.reff_date BETWEEN '{$startDate}' AND '{$endDate}'
                {$brandFilter}
                {$categoryFilter}
            ORDER BY p.name, dh.tr_code, dp.matl_descr
        ";
        $rows = DB::connection(Session::get('app_code'))->select($query);

        // Debug: Cek data step by step
        $deliveryCount = DB::connection(Session::get('app_code'))
            ->table('deliv_hdrs')
            ->where('tr_type', 'PD')
            ->whereBetween('reff_date', [$startDate, $endDate])
            ->count();

        $rewardExists = DB::connection(Session::get('app_code'))
            ->table('sales_rewards')
            ->where('code', $rewardCode)
            ->exists();

        // Debug: Cek join step by step
        $step1 = DB::connection(Session::get('app_code'))
            ->table('deliv_packings as dp')
            ->join('deliv_hdrs as dh', function($join) {
                $join->on('dh.id', '=', 'dp.trhdr_id')
                     ->where('dh.tr_type', '=', 'PD');
            })
            ->where('dp.tr_type', 'PD')
            ->whereBetween('dh.reff_date', [$startDate, $endDate])
            ->count();

        $step2 = DB::connection(Session::get('app_code'))
            ->table('deliv_packings as dp')
            ->join('deliv_hdrs as dh', function($join) {
                $join->on('dh.id', '=', 'dp.trhdr_id')
                     ->where('dh.tr_type', '=', 'PD');
            })
            ->join('partners as p', 'p.id', '=', 'dh.partner_id')
            ->where('dp.tr_type', 'PD')
            ->whereBetween('dh.reff_date', [$startDate, $endDate])
            ->count();

        $step3 = DB::connection(Session::get('app_code'))
            ->table('deliv_packings as dp')
            ->join('deliv_hdrs as dh', function($join) {
                $join->on('dh.id', '=', 'dp.trhdr_id')
                     ->where('dh.tr_type', '=', 'PD');
            })
            ->join('partners as p', 'p.id', '=', 'dh.partner_id')
            ->join('deliv_pickings as dpi', 'dpi.trpacking_id', '=', 'dp.id')
            ->where('dp.tr_type', 'PD')
            ->whereBetween('dh.reff_date', [$startDate, $endDate])
            ->count();

        // Cek sales rewards yang ada
        $salesRewards = DB::connection(Session::get('app_code'))
            ->table('sales_rewards')
            ->where('code', $rewardCode)
            ->get();

        // Cek material codes yang ada di delivery pickings
        $deliveryPickings = DB::connection(Session::get('app_code'))
            ->table('deliv_packings as dp')
            ->join('deliv_hdrs as dh', function($join) {
                $join->on('dh.id', '=', 'dp.trhdr_id')
                     ->where('dh.tr_type', '=', 'PD');
            })
            ->join('deliv_pickings as dpi', 'dpi.trpacking_id', '=', 'dp.id')
            ->join('materials as m', 'm.id', '=', 'dpi.matl_id')
            ->where('dp.tr_type', 'PD')
            ->whereBetween('dh.reff_date', [$startDate, $endDate])
            ->select('dpi.matl_code', 'm.brand', 'm.category')
            ->get();

        Log::info('Report PO Gajah Tunggal Debug:', [
            'query' => $query,
            'row_count' => count($rows),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reward_code' => $rewardCode,
            'delivery_count' => $deliveryCount,
            'reward_exists' => $rewardExists,
            'step1_count' => $step1,
            'step2_count' => $step2,
            'step3_count' => $step3,
            'sales_rewards' => $salesRewards->toArray(),
            'delivery_pickings' => $deliveryPickings->toArray(),
            'brand_filter' => $brandFilter,
            'category_filter' => $categoryFilter
        ]);

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
            $grouped[$customerKey]['details'][] = [
                'tgl_sj' => $row->tgl_sj,
                'no_nota' => $row->no_nota,
                'kode_brg' => $row->kode_brg,
                'nama_barang' => $row->nama_barang,
                'total_ban' => $row->total_ban,
                'point' => $row->point,
                'total_point' => $row->total_point,
            ];
            $grouped[$customerKey]['total_ban'] += $row->total_ban;
            $grouped[$customerKey]['total_point'] += $row->total_point;
        }
        $this->results = array_values($grouped);
    }

    /**
     * Query untuk mengambil data delivery dengan type PD berdasarkan periode reff_date dan matl_id
     *
     * @param string $startDate Tanggal awal periode (format: Y-m-d)
     * @param string $endDate Tanggal akhir periode (format: Y-m-d)
     * @param int|null $matlId ID material (opsional, jika null akan mengambil semua material)
     * @return array
     */
    public function getDeliveryDataByPeriod($startDate, $endDate, $matlId = null)
    {
        $startDate = addslashes($startDate);
        $endDate = addslashes($endDate);

        $matlCondition = '';
        if ($matlId !== null) {
            $matlId = (int) $matlId;
            $matlCondition = "AND dpi.matl_id = {$matlId}";
        }

        $query = "
            SELECT
                dh.id AS deliv_hdr_id,
                dh.tr_code AS deliv_code,
                dh.tr_date AS deliv_date,
                dh.reff_code AS reff_code,
                dh.reff_date AS reff_date,
                dh.partner_id,
                p.name AS partner_name,
                p.city AS partner_city,
                dp.id AS deliv_dtl_id,
                dp.tr_seq AS seq_no,
                dpi.matl_id,
                dp.matl_descr,
                dp.qty,
                m.name AS material_name,
                m.descr AS material_description
            FROM deliv_hdrs dh
            JOIN deliv_packings dp ON dp.trhdr_id = dh.id AND dp.tr_type = dh.tr_type
            JOIN deliv_pickings dpi ON dpi.trpacking_id = dp.id
            LEFT JOIN partners p ON p.id = dh.partner_id
            LEFT JOIN materials m ON m.id = dpi.matl_id
            WHERE dh.tr_type = 'PD'
                AND dh.deleted_at IS NULL
                AND dp.deleted_at IS NULL
                AND dh.reff_date BETWEEN '{$startDate}' AND '{$endDate}'
                {$matlCondition}
            ORDER BY dh.reff_date, dh.tr_code, dp.tr_seq
        ";

        return DB::connection(Session::get('app_code'))->select($query);
    }

        /**
     * Query alternatif menggunakan Eloquent untuk mengambil data delivery
     *
     * @param string $startDate Tanggal awal periode (format: Y-m-d)
     * @param string $endDate Tanggal akhir periode (format: Y-m-d)
     * @param int|null $matlId ID material (opsional)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getDeliveryDataByPeriodEloquent($startDate, $endDate, $matlId = null)
    {
        $query = DelivHdr::with(['DelivPacking.Material', 'Partner'])
            ->where('tr_type', 'PD')
            ->whereBetween('reff_date', [$startDate, $endDate]);

        if ($matlId !== null) {
            $query->whereHas('DelivPacking.DelivPickings', function ($q) use ($matlId) {
                $q->where('matl_id', $matlId);
            });
        }

        return $query->get();
    }

    /**
     * Contoh penggunaan query delivery
     * Method ini dapat dipanggil dari view atau controller lain
     */
    public function searchDeliveryData()
    {
        // Contoh penggunaan dengan periode dan material tertentu
        $startDate = '2024-01-01';
        $endDate = '2024-12-31';
        $matlId = 1; // ID material tertentu, atau null untuk semua material

        // Menggunakan raw query
        $deliveryData = $this->getDeliveryDataByPeriod($startDate, $endDate, $matlId);

        // Atau menggunakan Eloquent
        // $deliveryData = $this->getDeliveryDataByPeriodEloquent($startDate, $endDate, $matlId);

        return $deliveryData;
    }

    public function resetFilters()
    {
        $this->startPrintDate = '';
        $this->endPrintDate = '';
        $this->selectedBrand = '';
        $this->selectedCategory = '';
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
