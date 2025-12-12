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
    public $sr_qty = 0;
    public $sr_reward = 0;

    protected $listeners = [
        'onSrCodeChanged' => 'onSrCodeChanged',
         'DropdownSelected' => 'DropdownSelected'
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
        // Increase memory limit untuk handle large dataset
        ini_set('memory_limit', '256M');

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

        // Cek apakah brand IRC
        $isIrcBrand = stripos($rewardCode ?? '', 'IRC') !== false;

        if ($isIrcBrand) {
            // Query khusus untuk IRC - pisahkan Ban Luar dan Ban Dalam berdasarkan category di materials
            // Category bisa berisi: BAN LUAR MOTOR, BAN LUAR MOBIL, BAN DALAM MOTOR, BAN DALAM MOBIL
            // Setiap material ditampilkan sebagai row terpisah, kemudian di-group per dp.id untuk menggabungkan ban luar dan ban dalam
            $query = "
                SELECT
                    dh.reff_date AS tgl_sj,
                    dh.tr_code AS no_nota,
                    dpi.matl_code AS kode_brg,
                    dp.matl_descr AS nama_barang,
                    p.name AS nama_pelanggan,
                    p.city AS kota_pelanggan,
                    CASE WHEN UPPER(m.category) LIKE '%BAN LUAR%' THEN dpi.qty::int ELSE 0 END AS ban_luar,
                    CASE WHEN UPPER(m.category) LIKE '%BAN DALAM%' THEN dpi.qty::int ELSE 0 END AS ban_dalam,
                    dpi.qty::int AS total_ban,
                    CASE WHEN UPPER(m.category) LIKE '%BAN LUAR%' THEN
                        CASE
                            WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(dpi.qty / sr.qty) * COALESCE(sr.reward, 0)
                            ELSE dpi.qty * COALESCE(sr.reward, 0)
                        END
                        ELSE 0
                    END AS point_bl,
                    CASE WHEN UPPER(m.category) LIKE '%BAN DALAM%' THEN
                        CASE
                            WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(dpi.qty / sr.qty) * COALESCE(sr.reward, 0)
                            ELSE dpi.qty * COALESCE(sr.reward, 0)
                        END
                        ELSE 0
                    END AS point_bd,
                    CASE
                        WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(dpi.qty / sr.qty) * COALESCE(sr.reward, 0)
                        ELSE dpi.qty * COALESCE(sr.reward, 0)
                    END AS total_point,
                    sr.qty AS sr_qty,
                    sr.reward AS sr_reward,
                    dp.id AS deliv_packing_id,
                    m.category AS material_category
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
                ORDER BY dh.reff_date, p.name, dh.tr_code, dp.id, dpi.matl_code
                LIMIT 10000
            ";
        } else {
            // Query untuk non-IRC (format lama)
            $query = "
                SELECT
                    dh.reff_date AS tgl_sj,
                    dh.tr_code AS no_nota,
                    dpi.matl_code AS kode_brg,
                    dp.matl_descr AS nama_barang,
                    p.name AS nama_pelanggan,
                    p.city AS kota_pelanggan,
                    dp.qty AS total_ban,
                    CASE
                        WHEN COALESCE(sr.qty, 0) > 0 THEN COALESCE(sr.reward, 0) / sr.qty
                        ELSE COALESCE(sr.reward, 0)
                    END AS point,
                    CASE
                        WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(dp.qty / sr.qty) * COALESCE(sr.reward, 0)
                        ELSE dp.qty * COALESCE(sr.reward, 0)
                    END AS total_point,
                    sr.qty AS sr_qty,
                    sr.reward AS sr_reward
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
                ORDER BY dh.reff_date, p.name, dh.tr_code, dp.matl_descr
                LIMIT 10000
            ";
        }
        $rows = DB::connection(Session::get('app_code'))->select($query);

        // Simpan sr_qty dan sr_reward untuk grand total (ambil dari row pertama)
        if (!empty($rows)) {
            $firstRow = $rows[0];
            $this->sr_qty = (float)($firstRow->sr_qty ?? 0);
            $this->sr_reward = (float)($firstRow->sr_reward ?? 0);
        }

        if ($isIrcBrand) {
            // Untuk IRC, perlu menggabungkan baris yang memiliki deliv_packing_id yang sama
            // Karena satu packing bisa memiliki beberapa material (ban luar dan ban dalam)
            $groupedByPacking = [];
            foreach ($rows as $row) {
                // Gunakan deliv_packing_id sebagai key untuk grouping
                $key = $row->deliv_packing_id ?? ($row->no_nota . '_' . $row->kode_brg);

                if (!isset($groupedByPacking[$key])) {
                    $groupedByPacking[$key] = (object)[
                        'tgl_sj' => $row->tgl_sj,
                        'no_nota' => $row->no_nota,
                        'kode_brg' => $row->kode_brg,
                        'nama_barang' => $row->nama_barang,
                        'nama_pelanggan' => $row->nama_pelanggan,
                        'kota_pelanggan' => $row->kota_pelanggan,
                        'ban_luar' => (int)($row->ban_luar ?? 0),
                        'ban_dalam' => (int)($row->ban_dalam ?? 0),
                        'total_ban' => (int)($row->total_ban ?? 0),
                        'point_bl' => (float)($row->point_bl ?? 0),
                        'point_bd' => (float)($row->point_bd ?? 0),
                        'total_point' => (float)($row->total_point ?? 0),
                        'sr_qty' => (float)($row->sr_qty ?? 0),
                        'sr_reward' => (float)($row->sr_reward ?? 0),
                    ];
                } else {
                    // Tambahkan nilai jika sudah ada (untuk menggabungkan ban luar dan ban dalam dalam satu packing)
                    $groupedByPacking[$key]->ban_luar += (int)($row->ban_luar ?? 0);
                    $groupedByPacking[$key]->ban_dalam += (int)($row->ban_dalam ?? 0);
                    $groupedByPacking[$key]->total_ban += (int)($row->total_ban ?? 0);
                    $groupedByPacking[$key]->point_bl += (float)($row->point_bl ?? 0);
                    $groupedByPacking[$key]->point_bd += (float)($row->point_bd ?? 0);
                    $groupedByPacking[$key]->total_point += (float)($row->total_point ?? 0);
                }
                // Hapus row dari memory setelah diproses
                unset($row);
            }

            // Clear original rows array
            unset($rows);

            // Hitung sisa sekali setelah semua merge selesai (hanya sisa BD)
            foreach ($groupedByPacking as $key => $row) {
                $srQty = $row->sr_qty;
                $srReward = $row->sr_reward;

                $totalBanBD = ($srReward > 0 && $row->point_bd > 0)
                    ? (int)($row->point_bd / $srReward * $srQty)
                    : 0;

                $row->sisa_bd = $row->ban_dalam - $totalBanBD;

                // Simpan sr_qty dan sr_reward untuk BAN LUAR dan BAN DALAM sebelum dihapus
                // (diperlukan untuk menghitung MAX di customer aggregate)
                if (($row->ban_luar ?? 0) > 0) {
                    $row->bl_sr_qty = $srQty;
                    $row->bl_sr_reward = $srReward;
                }
                if (($row->ban_dalam ?? 0) > 0) {
                    $row->bd_sr_qty = $srQty;
                    $row->bd_sr_reward = $srReward;
                }

                // Hapus sr_qty dan sr_reward untuk menghemat memory
                unset($row->sr_qty, $row->sr_reward);
            }

            // Convert to array tanpa membuat copy baru
            $rows = [];
            foreach ($groupedByPacking as $row) {
                $rows[] = $row;
            }
            unset($groupedByPacking);
        }

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
        foreach ($rows as $index => $row) {
            $customerKey = $row->nama_pelanggan . ' - ' . $row->kota_pelanggan;
            if (!isset($grouped[$customerKey])) {
                $grouped[$customerKey] = [
                    'customer' => $customerKey,
                    'details' => [],
                    'total_ban' => 0,
                    'total_point' => 0,
                ];
                if ($isIrcBrand) {
                    $grouped[$customerKey]['ban_luar'] = 0;
                    $grouped[$customerKey]['ban_dalam'] = 0;
                    $grouped[$customerKey]['point_bl'] = 0;
                    $grouped[$customerKey]['point_bd'] = 0;
                    $grouped[$customerKey]['sisa_bd'] = 0;
                    // Simpan semua sr_qty dan sr_reward untuk menghitung MAX
                    $grouped[$customerKey]['bl_sr_qty_list'] = [];
                    $grouped[$customerKey]['bl_sr_reward_list'] = [];
                    $grouped[$customerKey]['bd_sr_qty_list'] = [];
                    $grouped[$customerKey]['bd_sr_reward_list'] = [];
                } else {
                    $grouped[$customerKey]['point'] = 0;
                }
            }

            if ($isIrcBrand) {
                $grouped[$customerKey]['details'][] = [
                    'tgl_sj' => $row->tgl_sj,
                    'no_nota' => $row->no_nota,
                    'kode_brg' => $row->kode_brg,
                    'nama_barang' => $row->nama_barang,
                    'ban_luar' => $row->ban_luar ?? 0,
                    'ban_dalam' => $row->ban_dalam ?? 0,
                    'total_ban' => $row->total_ban ?? 0,
                    'point_bl' => $row->point_bl ?? 0,
                    'point_bd' => $row->point_bd ?? 0,
                    'total_point' => $row->total_point ?? 0,
                    'sisa_bd' => $row->sisa_bd ?? 0,
                ];
                $grouped[$customerKey]['ban_luar'] += $row->ban_luar ?? 0;
                $grouped[$customerKey]['ban_dalam'] += $row->ban_dalam ?? 0;
                // Kumpulkan sr_qty dan sr_reward untuk BAN LUAR dan BAN DALAM
                if (($row->ban_luar ?? 0) > 0 && isset($row->bl_sr_qty) && isset($row->bl_sr_reward)) {
                    $grouped[$customerKey]['bl_sr_qty_list'][] = (float)$row->bl_sr_qty;
                    $grouped[$customerKey]['bl_sr_reward_list'][] = (float)$row->bl_sr_reward;
                }
                if (($row->ban_dalam ?? 0) > 0 && isset($row->bd_sr_qty) && isset($row->bd_sr_reward)) {
                    $grouped[$customerKey]['bd_sr_qty_list'][] = (float)$row->bd_sr_qty;
                    $grouped[$customerKey]['bd_sr_reward_list'][] = (float)$row->bd_sr_reward;
                }
                // point_bl dan point_bd akan dihitung ulang dari aggregate setelah semua data di-aggregate
            } else {
                $grouped[$customerKey]['details'][] = [
                    'tgl_sj' => $row->tgl_sj,
                    'no_nota' => $row->no_nota,
                    'kode_brg' => $row->kode_brg,
                    'nama_barang' => $row->nama_barang,
                    'total_ban' => $row->total_ban,
                    'point' => $row->point ?? 0,
                    'total_point' => $row->total_point,
                ];
            }
            $grouped[$customerKey]['total_ban'] += $row->total_ban ?? 0;
            // total_point akan dihitung ulang menggunakan rumus setelah semua data di-aggregate

            // Unset row setelah diproses untuk menghemat memory
            unset($rows[$index]);
        }

        // Clear rows array
        unset($rows);

        // Simpan flag dan hitung ulang point dan sisa menggunakan rumus yang sama untuk setiap group
        foreach ($grouped as &$group) {
            $group['is_irc'] = $isIrcBrand;

            if ($isIrcBrand) {
                // Untuk IRC, hitung ulang point dari aggregate (seperti di ReportPointNota)
                // Ambil MAX(sr_qty) dan MAX(sr_reward) untuk BAN LUAR dan BAN DALAM secara terpisah
                $blSrQty = !empty($group['bl_sr_qty_list']) ? max($group['bl_sr_qty_list']) : 0;
                $blSrReward = !empty($group['bl_sr_reward_list']) ? max($group['bl_sr_reward_list']) : 0;
                $bdSrQty = !empty($group['bd_sr_qty_list']) ? max($group['bd_sr_qty_list']) : 0;
                $bdSrReward = !empty($group['bd_sr_reward_list']) ? max($group['bd_sr_reward_list']) : 0;

                // Point BL = TRUNC(ban_luar / MAX(sr_qty)) × MAX(sr_reward) untuk BAN LUAR
                $group['point_bl'] = ($blSrQty > 0 && ($group['ban_luar'] ?? 0) > 0)
                    ? (int)(floor($group['ban_luar'] / $blSrQty) * $blSrReward)
                    : 0;

                // Point BD = TRUNC(ban_dalam / MAX(sr_qty)) × MAX(sr_reward) untuk BAN DALAM
                $group['point_bd'] = ($bdSrQty > 0 && ($group['ban_dalam'] ?? 0) > 0)
                    ? (int)(floor($group['ban_dalam'] / $bdSrQty) * $bdSrReward)
                    : 0;

                // Total Point = point_bl + point_bd
                $group['total_point'] = $group['point_bl'] + $group['point_bd'];
                $totalBanBD = ($bdSrReward > 0 && $group['point_bd'] > 0)
                    ? (int)(($group['point_bd'] / $bdSrReward) * $bdSrQty)
                    : 0;

                $group['sisa_bd'] = ($group['ban_dalam'] ?? 0) - $totalBanBD;

                // Hapus list yang tidak diperlukan lagi
                unset($group['bl_sr_qty_list'], $group['bl_sr_reward_list'],
                      $group['bd_sr_qty_list'], $group['bd_sr_reward_list']);
            } else {
                // Untuk non-IRC, hitung ulang total_point dari aggregate
                $srQty = $this->sr_qty;
                $srReward = $this->sr_reward;

                $group['total_point'] = ($srQty > 0 && ($group['total_ban'] ?? 0) > 0)
                    ? (int)(floor($group['total_ban'] / $srQty) * $srReward)
                    : 0;
            }
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
        $this->sr_qty = 0;
        $this->sr_reward = 0;
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
