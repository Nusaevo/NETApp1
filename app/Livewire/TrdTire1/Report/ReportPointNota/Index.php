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
    public $grandTotalBanLuar = 0;
    public $grandTotalBanDalam = 0;
    public $grandTotalPoint = 0;
    public $grandTotalPointBL = 0;
    public $grandTotalPointBD = 0;
    public $grandTotalSisaBD = 0;
    public $sr_qty = 0;
    public $sr_reward = 0;

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
        // Reset hasil saat ganti program untuk menghindari memory leak
        $this->resetResult();

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
        // Reset hasil sebelumnya untuk menghindari memory leak
        $this->resetResult();

        // Increase memory limit untuk handle large dataset
        ini_set('memory_limit', '256M');

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

        // Cek apakah brand IRC
        $isIrcBrand = stripos($this->category ?? '', 'IRC') !== false;

        // Tentukan jenis JOIN berdasarkan checkbox point_flag
        $salesRewardJoin = $this->point_flag ? "LEFT OUTER JOIN" : "JOIN";

        // Tambahkan kondisi WHERE untuk memfilter data ketika tidak menggunakan LEFT OUTER JOIN
        $whereSalesReward = $this->point_flag ? "" : " AND sr.reward IS NOT NULL";

        if ($isIrcBrand) {
            // Query khusus untuk IRC - pisahkan Ban Luar dan Ban Dalam berdasarkan category di materials
            // Category bisa berisi: BAN LUAR MOTOR, BAN LUAR MOBIL, BAN DALAM MOTOR, BAN DALAM MOBIL
            $query = "
                SELECT
                    oh.tr_date AS tgl_nota,
                    oh.tr_code AS no_nota,
                    od.matl_code AS kode_brg,
                    od.matl_descr AS nama_barang,
                    CASE
                        WHEN sr.brand IS NOT NULL
                             AND sr.brand = 'IRC'
                             AND (p.partner_chars->>'IRC' = 'false' OR p.partner_chars->>'IRC' IS NULL)
                        THEN 'CUSTOMER LAIN-LAIN'
                        ELSE p.name
                    END AS nama_pelanggan,
                    CASE
                        WHEN sr.brand IS NOT NULL
                             AND sr.brand = 'IRC'
                             AND (p.partner_chars->>'IRC' = 'false' OR p.partner_chars->>'IRC' IS NULL)
                        THEN ''
                        ELSE p.city
                    END AS kota_pelanggan,
                    CASE WHEN UPPER(m.category) LIKE '%BAN LUAR%' THEN od.qty::int ELSE 0 END AS ban_luar,
                    CASE WHEN UPPER(m.category) LIKE '%BAN DALAM%' THEN od.qty::int ELSE 0 END AS ban_dalam,
                    od.qty::int AS total_ban,
                    CASE WHEN UPPER(m.category) LIKE '%BAN LUAR%' THEN
                        CASE
                            WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(od.qty / sr.qty) * COALESCE(sr.reward, 0)
                            ELSE od.qty * COALESCE(sr.reward, 0)
                        END
                        ELSE 0
                    END AS point_bl,
                    CASE WHEN UPPER(m.category) LIKE '%BAN DALAM%' THEN
                        CASE
                            WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(od.qty / sr.qty) * COALESCE(sr.reward, 0)
                            ELSE od.qty * COALESCE(sr.reward, 0)
                        END
                        ELSE 0
                    END AS point_bd,
                    CASE
                        WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(od.qty / sr.qty) * COALESCE(sr.reward, 0)
                        ELSE od.qty * COALESCE(sr.reward, 0)
                    END AS total_point,
                    sr.qty AS sr_qty,
                    sr.reward AS sr_reward,
                    CASE
                        WHEN sr.brand IS NOT NULL
                             AND sr.brand = 'IRC'
                             AND (p.partner_chars->>'IRC' = 'false' OR p.partner_chars->>'IRC' IS NULL)
                        THEN 1
                        ELSE 0
                    END AS is_lain_lain,
                    od.id AS order_dtl_id
                FROM order_dtls od
                JOIN order_hdrs oh ON oh.id = od.trhdr_id AND oh.tr_type = 'SO' AND oh.status_code != 'X'
                JOIN partners p ON p.id = oh.partner_id
                $salesRewardJoin sales_rewards sr ON sr.code = :sr_code AND sr.matl_code = od.matl_code
                JOIN materials m ON m.id = od.matl_id AND m.brand = :brand
                WHERE od.tr_type = 'SO'
                    AND od.qty = od.qty_reff
                    $whereDate
                    $whereSalesReward
                ORDER BY is_lain_lain ASC, nama_pelanggan, oh.tr_date ASC, oh.tr_code, od.id, od.matl_code
                LIMIT 10000
            ";
        } else {
            // Query untuk non-IRC (format lama)
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
                CASE
                    WHEN COALESCE(sr.qty, 0) > 0 THEN COALESCE(sr.reward, 0) / sr.qty
                    ELSE COALESCE(sr.reward, 0)
                END AS point,
                CASE
                    WHEN COALESCE(sr.qty, 0) > 0 THEN TRUNC(od.qty / sr.qty) * COALESCE(sr.reward, 0)
                    ELSE od.qty * COALESCE(sr.reward, 0)
                END AS total_point,
                sr.qty AS sr_qty,
                sr.reward AS sr_reward,
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
                AND od.qty = od.qty_reff
                $whereDate
                $whereSalesReward
                ORDER BY is_lain_lain ASC, nama_pelanggan, oh.tr_date ASC, oh.tr_code, od.matl_code
            ";
        }
        // dd($query);

        $rows = DB::connection(Session::get('app_code'))->select($query, $bindings);

        // Simpan sr_qty dan sr_reward untuk grand total (ambil dari row pertama)
        if (!empty($rows)) {
            $firstRow = $rows[0];
            $this->sr_qty = (float)($firstRow->sr_qty ?? 0);
            $this->sr_reward = (float)($firstRow->sr_reward ?? 0);
        }

        if ($isIrcBrand) {
            // Untuk IRC, perlu menggabungkan baris yang memiliki order_dtl_id yang sama
            // Karena satu order detail bisa memiliki beberapa material (ban luar dan ban dalam)
            $groupedByOrderDtl = [];
            foreach ($rows as $row) {
                // Gunakan order_dtl_id sebagai key untuk grouping
                $key = $row->order_dtl_id ?? ($row->no_nota . '_' . $row->kode_brg);

                if (!isset($groupedByOrderDtl[$key])) {
                    $groupedByOrderDtl[$key] = (object)[
                        'tgl_nota' => $row->tgl_nota,
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
                        'total_point' => 0, // Akan dihitung sebagai point_bl + point_bd setelah merge
                        'sr_qty' => (float)($row->sr_qty ?? 0),
                        'sr_reward' => (float)($row->sr_reward ?? 0),
                        'is_lain_lain' => $row->is_lain_lain ?? 0,
                    ];
                } else {
                    // Tambahkan nilai jika sudah ada (untuk menggabungkan ban luar dan ban dalam dalam satu order detail)
                    $groupedByOrderDtl[$key]->ban_luar += (int)($row->ban_luar ?? 0);
                    $groupedByOrderDtl[$key]->ban_dalam += (int)($row->ban_dalam ?? 0);
                    $groupedByOrderDtl[$key]->total_ban += (int)($row->total_ban ?? 0);
                    $groupedByOrderDtl[$key]->point_bl += (float)($row->point_bl ?? 0);
                    $groupedByOrderDtl[$key]->point_bd += (float)($row->point_bd ?? 0);
                    // total_point tidak dijumlahkan dari SQL, akan dihitung sebagai point_bl + point_bd setelah merge
                }
                // Hapus row dari memory setelah diproses
                unset($row);
            }

            // Clear original rows array
            unset($rows);

            // Hitung total_point dan sisa_bd setelah semua merge selesai
            foreach ($groupedByOrderDtl as $key => $row) {
                // Untuk IRC, total_point = point_bl + point_bd (bukan jumlah dari SQL)
                $row->total_point = $row->point_bl + $row->point_bd;

                $srQty = $row->sr_qty;
                $srReward = $row->sr_reward;

                $totalBanBD = ($srReward > 0 && $row->point_bd > 0)
                    ? (int)($row->point_bd / $srReward * $srQty)
                    : 0;

                $row->sisa_bd = $row->ban_dalam - $totalBanBD;

                // Hapus sr_qty dan sr_reward untuk menghemat memory
                unset($row->sr_qty, $row->sr_reward);
            }

            // Convert to array tanpa membuat copy baru
            $rows = [];
            foreach ($groupedByOrderDtl as $row) {
                $rows[] = $row;
            }
            unset($groupedByOrderDtl);
        }

        // Grouping per customer
        $grouped = [];
        foreach ($rows as $index => $row) {
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
                if ($isIrcBrand) {
                    $grouped[$customerKey]['ban_luar'] = 0;
                    $grouped[$customerKey]['ban_dalam'] = 0;
                    $grouped[$customerKey]['point_bl'] = 0;
                    $grouped[$customerKey]['point_bd'] = 0;
                    $grouped[$customerKey]['sisa_bd'] = 0;
                } else {
                    $grouped[$customerKey]['point'] = 0;
                }
            }
            $grouped[$customerKey]['details'][] = $row;
            $grouped[$customerKey]['total_ban'] += $row->total_ban ?? 0;
            // Jumlahkan qty untuk dihitung ulang point dari aggregate (seperti di ReportPointCust)
            if ($isIrcBrand) {
                $grouped[$customerKey]['ban_luar'] += $row->ban_luar ?? 0;
                $grouped[$customerKey]['ban_dalam'] += $row->ban_dalam ?? 0;
                // point_bl dan point_bd akan dihitung ulang dari aggregate setelah semua data di-aggregate
            } else {
                // total_point akan dihitung ulang dari aggregate setelah semua data di-aggregate
            }

            // Unset row setelah diproses untuk menghemat memory
            unset($rows[$index]);
        }

        // Clear rows array
        unset($rows);

        // Sort hasil: CUSTOMER LAIN-LAIN muncul di akhir
        uasort($grouped, function($a, $b) {
            $aIsLainLain = $a['customer'] === 'CUSTOMER LAIN-LAIN';
            $bIsLainLain = $b['customer'] === 'CUSTOMER LAIN-LAIN';

            // Jika salah satu adalah CUSTOMER LAIN-LAIN, letakkan di akhir
            if ($aIsLainLain && !$bIsLainLain) {
                return 1;
            }
            if (!$aIsLainLain && $bIsLainLain) {
                return -1;
            }
            return strcmp($a['customer'], $b['customer']);
        });

        foreach ($grouped as &$group) {
            $group['is_irc'] = $isIrcBrand;

            if ($isIrcBrand) {
                // Untuk IRC, hitung ulang point dari aggregate (seperti di ReportPointCust)
                $srQty = $this->sr_qty;
                $srReward = $this->sr_reward;

                // Point BL = TRUNC(ban_luar / sr_qty) × sr_reward
                $group['point_bl'] = ($srQty > 0 && ($group['ban_luar'] ?? 0) > 0)
                    ? (int)(floor($group['ban_luar'] / $srQty) * $srReward)
                    : 0;

                // Point BD = TRUNC(ban_dalam / sr_qty) × sr_reward
                $group['point_bd'] = ($srQty > 0 && ($group['ban_dalam'] ?? 0) > 0)
                    ? (int)(floor($group['ban_dalam'] / $srQty) * $srReward)
                    : 0;

                // Total Point = point_bl + point_bd
                $group['total_point'] = $group['point_bl'] + $group['point_bd'];
                $totalBanBD = ($srReward > 0 && $group['point_bd'] > 0)
                    ? (int)(($group['point_bd'] / $srReward) * $srQty)
                    : 0;

                $group['sisa_bd'] = ($group['ban_dalam'] ?? 0) - $totalBanBD;
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

        // Calculate grand totals menggunakan query terpisah (seperti di ReportPointCust)
        if ($isIrcBrand) {
            // Query untuk grand total IRC
            $grandTotalQuery = "
                WITH base_data AS (
                    SELECT
                        CASE WHEN UPPER(m.category) LIKE '%BAN LUAR%' THEN od.qty::int ELSE 0 END AS ban_luar,
                        CASE WHEN UPPER(m.category) LIKE '%BAN DALAM%' THEN od.qty::int ELSE 0 END AS ban_dalam,
                        od.qty::int AS total_ban,
                        sr.qty AS sr_qty,
                        sr.reward AS sr_reward,
                        od.id AS order_dtl_id
                    FROM order_dtls od
                    JOIN order_hdrs oh ON oh.id = od.trhdr_id AND oh.tr_type = 'SO' AND oh.status_code != 'X'
                    JOIN partners p ON p.id = oh.partner_id
                    $salesRewardJoin sales_rewards sr ON sr.code = :sr_code AND sr.matl_code = od.matl_code
                    JOIN materials m ON m.id = od.matl_id AND m.brand = :brand
                    WHERE od.tr_type = 'SO'
                        AND od.qty = od.qty_reff
                        $whereDate
                        $whereSalesReward
                ),
                grouped_by_order_dtl AS (
                    SELECT
                        order_dtl_id,
                        SUM(ban_luar) AS ban_luar,
                        SUM(ban_dalam) AS ban_dalam,
                        SUM(total_ban) AS total_ban,
                        MAX(sr_qty) AS sr_qty,
                        MAX(sr_reward) AS sr_reward
                    FROM base_data
                    GROUP BY order_dtl_id
                ),
                grand_total AS (
                    SELECT
                        SUM(ban_luar) AS grand_total_ban_luar,
                        SUM(ban_dalam) AS grand_total_ban_dalam,
                        SUM(total_ban) AS grand_total_ban,
                        MAX(sr_qty) AS sr_qty,
                        MAX(sr_reward) AS sr_reward
                    FROM grouped_by_order_dtl
                )
                SELECT
                    grand_total_ban_luar,
                    grand_total_ban_dalam,
                    grand_total_ban,
                    sr_qty,
                    sr_reward,
                    CASE
                        WHEN sr_qty > 0 AND grand_total_ban_luar > 0
                        THEN FLOOR(grand_total_ban_luar / sr_qty)::int * sr_reward
                        ELSE 0
                    END AS grand_total_point_bl,
                    CASE
                        WHEN sr_qty > 0 AND grand_total_ban_dalam > 0
                        THEN FLOOR(grand_total_ban_dalam / sr_qty)::int * sr_reward
                        ELSE 0
                    END AS grand_total_point_bd
                FROM grand_total
            ";

            $grandTotalResult = DB::connection(Session::get('app_code'))->selectOne($grandTotalQuery, $bindings);

            if ($grandTotalResult) {
                $this->grandTotalBan = (int)($grandTotalResult->grand_total_ban ?? 0);
                $this->grandTotalBanLuar = (int)($grandTotalResult->grand_total_ban_luar ?? 0);
                $this->grandTotalBanDalam = (int)($grandTotalResult->grand_total_ban_dalam ?? 0);
                $srQty = (float)($grandTotalResult->sr_qty ?? 0);
                $srReward = (float)($grandTotalResult->sr_reward ?? 0);

                $this->grandTotalPointBL = (int)($grandTotalResult->grand_total_point_bl ?? 0);
                $this->grandTotalPointBD = (int)($grandTotalResult->grand_total_point_bd ?? 0);
                $this->grandTotalPoint = $this->grandTotalPointBL + $this->grandTotalPointBD;

                // Hitung grand total sisa BD menggunakan rumus yang sama dengan ReportPointCust
                // total ban BD = (point BD / reward) × qtySr
                // sisa BD = ban_dalam - total ban BD
                $grandTotalBanBD = ($srReward > 0 && $this->grandTotalPointBD > 0)
                    ? (int)(($this->grandTotalPointBD / $srReward) * $srQty)
                    : 0;
                $this->grandTotalSisaBD = $this->grandTotalBanDalam - $grandTotalBanBD;
            }
        } else {
            // Query untuk grand total non-IRC
            $grandTotalQuery = "
                SELECT
                    SUM(od.qty) AS grand_total_ban,
                    MAX(sr.qty) AS sr_qty,
                    MAX(sr.reward) AS sr_reward
                FROM order_dtls od
                JOIN order_hdrs oh ON oh.id = od.trhdr_id AND oh.tr_type = 'SO' AND oh.status_code != 'X'
                JOIN partners p ON p.id = oh.partner_id
                $salesRewardJoin sales_rewards sr ON sr.code = :sr_code AND sr.matl_code = od.matl_code
                JOIN materials m ON m.id = od.matl_id AND m.brand = :brand
                WHERE od.tr_type = 'SO'
                    AND od.qty = od.qty_reff
                    $whereDate
                    $whereSalesReward
            ";

            $grandTotalResult = DB::connection(Session::get('app_code'))->selectOne($grandTotalQuery, $bindings);

            if ($grandTotalResult) {
                $this->grandTotalBan = (int)($grandTotalResult->grand_total_ban ?? 0);
                $srQty = (float)($grandTotalResult->sr_qty ?? 0);
                $srReward = (float)($grandTotalResult->sr_reward ?? 0);

                $this->grandTotalPoint = ($srQty > 0 && $this->grandTotalBan > 0)
                    ? (int)(floor($this->grandTotalBan / $srQty) * $srReward)
                    : 0;
            }
        }

        // sr_qty dan sr_reward sudah disimpan sebelumnya saat menghitung sisa untuk IRC

        // Force garbage collection untuk membersihkan memory
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    public function resetFilters()
    {
        $this->category = '';
        $this->startCode = '';
        $this->endCode = '';
        $this->point_flag = false;
        $this->results = [];
        $this->grandTotalBan = 0;
        $this->grandTotalBanLuar = 0;
        $this->grandTotalBanDalam = 0;
        $this->grandTotalPoint = 0;
        $this->grandTotalPointBL = 0;
        $this->grandTotalPointBD = 0;
        $this->grandTotalSisaBD = 0;
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
        $this->grandTotalBan = 0;
        $this->grandTotalBanLuar = 0;
        $this->grandTotalBanDalam = 0;
        $this->grandTotalPoint = 0;
        $this->grandTotalPointBL = 0;
        $this->grandTotalPointBD = 0;
        $this->grandTotalSisaBD = 0;

        // Force garbage collection untuk membersihkan memory
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
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
