<?php

namespace App\Livewire\TrdTire1\Report\ReportPOGajahTunggal;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
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

        // Ambil kode program (sales reward) untuk dropdown
        $this->rewardOptions = SalesReward::query()
            ->selectRaw('DISTINCT code, descrs')
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get()
            ->map(function ($item) {
                $label = $item->code . ' - ' . ($item->descrs ?? '');
                return [
                    'value' => $item->code,
                    'label' => $label,
                ];
            })
            ->toArray();


        $this->masterService = new MasterService();
        $this->resetFilters();
    }

    // public function onSrCodeChanged() {
    //     // Method ini sudah tidak dipakai karena dropdown diganti menjadi tanggal tagih
    // }

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
                dp.matl_descr AS nama_barang,
                p.name AS nama_pelanggan,
                p.city AS kota_pelanggan,
                dp.qty AS total_ban,
                sr.reward AS point,
                (dp.qty * sr.reward) AS total_point
            FROM deliv_packings dp
            JOIN deliv_hdrs dh ON dh.id = dp.trhdr_id AND dh.tr_type = 'PD'
            JOIN partners p ON p.id = dh.partner_id
            JOIN sales_rewards sr ON sr.code = '{$rewardCode}' AND sr.matl_code = dp.matl_descr
            JOIN materials m ON m.id = dp.matl_id AND m.brand = sr.brand
            WHERE dp.tr_type = 'PD'
                AND dh.reff_date BETWEEN '{$startDate}' AND '{$endDate}'
                {$brandFilter}
                {$categoryFilter}
            ORDER BY p.name, dh.tr_code, dp.matl_descr
        ";
        $rows = DB::connection(Session::get('app_code'))->select($query);
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
            $matlCondition = "AND dp.matl_id = {$matlId}";
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
                dp.matl_id,
                dp.matl_descr,
                dp.qty,
                m.name AS material_name,
                m.descr AS material_description
            FROM deliv_hdrs dh
            JOIN deliv_packings dp ON dp.trhdr_id = dh.id AND dp.tr_type = dh.tr_type
            LEFT JOIN partners p ON p.id = dh.partner_id
            LEFT JOIN materials m ON m.id = dp.matl_id
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
            $query->whereHas('DelivPacking', function ($q) use ($matlId) {
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
