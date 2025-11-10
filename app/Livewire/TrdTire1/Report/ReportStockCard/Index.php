<?php

namespace App\Livewire\TrdTire1\Report\ReportStockCard;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session, Log};
use App\Services\TrdTire1\Master\MasterService;
use App\Models\SysConfig1\{ConfigMenu, ConfigRight};

class Index extends BaseComponent
{
    public $wh_code;
    public $matl_code;
    public $matl_id;
    public $start_date;
    public $end_date;

    public $results = [];
    public $warehouses = [];
    public $material_name = '';
    public $warehouse_name = '';
    public $materialQuery = "
        SELECT m.id, m.code, m.name
        FROM materials m
        WHERE m.status_code = 'A'
        AND m.deleted_at IS NULL
    ";
    protected $masterService;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        // Get additionalParam from query string if not provided
        if (empty($additionalParam) && request()->has('additionalParam')) {
            $additionalParam = request()->query('additionalParam');
        }

        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);
    }

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();

        // Handle additionalParam if exists
        if (!empty($this->additionalParam)) {
            try {
                // First decrypt the additionalParam
                $decryptedParam = decryptWithSessionKey($this->additionalParam);

                // Parse JSON array format
                $decodedParam = json_decode($decryptedParam, true);
                if (is_array($decodedParam) && json_last_error() === JSON_ERROR_NONE) {
                    // Handle JSON array structure
                    if (isset($decodedParam['type']) && $decodedParam['type'] === 'fromStockMaterial') {
                        // Set warehouse code
                        if (isset($decodedParam['wh_code'])) {
                            $this->wh_code = $decodedParam['wh_code'];
                            $this->onWarehouseChanged();
                        }

                        // Set material code
                        if (isset($decodedParam['matl_code'])) {
                            $this->matl_code = $decodedParam['matl_code'];

                            // Find material ID from code
                            $material = DB::connection(Session::get('app_code'))
                                ->table('materials')
                                ->select('id', 'code', 'name')
                                ->where('code', $this->matl_code)
                                ->first();

                            if ($material) {
                                $this->matl_id = $material->id;
                                $this->material_name = $material->name;
                            }
                        }

                        // Set date filters
                        if (isset($decodedParam['start_date'])) {
                            $this->start_date = $decodedParam['start_date'];
                        }
                        if (isset($decodedParam['end_date'])) {
                            $this->end_date = $decodedParam['end_date'];
                        }

                        // Auto-load data when all required parameters are set
                        if ($this->wh_code && $this->matl_code && $this->start_date && $this->end_date) {
                            $this->search();
                        }
                    }
                }
            } catch (\Exception $e) {
                // If parsing fails, silently continue without setting filters
                // Log error for debugging if needed
                Log::warning('ReportStockCard: Failed to parse additionalParam', [
                    'error' => $e->getMessage(),
                    'additionalParam' => $this->additionalParam
                ]);
            }
        } else {
            $this->resetFilters();
        }
    }

    public function resetFilters()
    {
        $this->wh_code = '';
        $this->matl_code = '';
        $this->matl_id = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->results = [];
        $this->material_name = '';
        $this->warehouse_name = '';
    }

    public function onMaterialChanged()
    {
        // Get material code and name from selected material ID
        if ($this->matl_id) {
            $material = DB::connection(Session::get('app_code'))
                ->table('materials')
                ->select('code', 'name')
                ->where('id', $this->matl_id)
                ->first();

            if ($material) {
                $this->matl_code = $material->code;
                $this->material_name = $material->name;
            }
        } else {
            $this->matl_code = '';
            $this->material_name = '';
        }
    }

    public function onWarehouseChanged()
    {
        // Get warehouse name from selected warehouse code
        if ($this->wh_code) {
            $warehouse = DB::connection(Session::get('app_code'))
                ->table('config_consts')
                ->select('str2')
                ->where('const_group', 'TRX_WAREHOUSE')
                ->where('str1', $this->wh_code)
                ->whereNull('deleted_at')
                ->first();

            if ($warehouse) {
                $this->warehouse_name = $warehouse->str2;
            }
        } else {
            $this->warehouse_name = '';
        }
    }

    public function resetResult()
    {
        $this->results = [];
    }

    public function search()
    {
        if (!$this->wh_code || !$this->matl_code || !$this->start_date || !$this->end_date) {
            $this->dispatch('warning', 'Mohon lengkapi filter Gudang, Kode Barang, dan Periode.');
            return;
        }

        try {
            $whCode = addslashes($this->wh_code);
            $matlCode = addslashes($this->matl_code);
            $startDate = addslashes($this->start_date);
            $endDate = addslashes($this->end_date);

            // Calculate opening balance from transactions before start_date
            $sql = "
                WITH
                params(start_date, end_date, wh_code, matl_code) AS (
                    VALUES (DATE '{$startDate}', DATE '{$endDate}', '{$whCode}', '{$matlCode}')
                ),
                -- Calculate opening balance from all transactions before start_date
                opening_tx AS (
                    SELECT COALESCE(SUM(il.qty), 0)::numeric AS opening_qty
                    FROM ivt_logs il
                    JOIN params p ON TRUE
                    WHERE il.wh_code = p.wh_code
                      AND il.matl_code = p.matl_code
                      AND il.tr_type IN ('PD','SD','TW','IA')
                      AND il.tr_date < p.start_date
                ),
                -- Period transactions
                tx AS (
                    SELECT
                        il.tr_date, il.tr_code, il.tr_seq, il.tr_seq2, il.tr_type,
                        CASE
                            WHEN il.tr_type IN ('SD', 'PD') THEN
                                COALESCE(pt.name, il.tr_desc) ||
                                CASE WHEN pt.city IS NOT NULL AND pt.city != '' THEN '. ' || pt.city ELSE '' END
                            ELSE il.tr_desc
                        END AS tr_desc,
                        GREATEST(il.qty, 0) AS masuk,
                        GREATEST(-il.qty, 0) AS keluar,
                        il.qty AS net_qty
                    FROM ivt_logs il
                    JOIN params p ON TRUE
                    LEFT JOIN deliv_hdrs dh ON il.tr_code = dh.tr_code AND il.tr_type IN ('SD', 'PD')
                    LEFT JOIN partners pt ON dh.partner_code = pt.code
                    WHERE il.wh_code = p.wh_code
                      AND il.matl_code = p.matl_code
                      AND il.tr_type IN ('PD','SD','TW','IA')
                      AND il.tr_date BETWEEN p.start_date AND p.end_date
                )
                SELECT
                    0 AS urut, NULL::date AS tr_date, 'Sisa Stok s/d ' || TO_CHAR(p.start_date - INTERVAL '1 day', 'DD-Mon-YYYY') AS tr_desc, NULL AS tr_code, NULL AS tr_seq, NULL AS tr_seq2, NULL AS tr_type,
                    0 AS masuk, 0 AS keluar, (SELECT opening_qty FROM opening_tx) AS sisa
                FROM params p
                UNION ALL
                SELECT
                    1 AS urut, t.tr_date, t.tr_desc, t.tr_code, t.tr_seq, t.tr_seq2, t.tr_type,
                    t.masuk, t.keluar, NULL AS sisa
                FROM tx t
                WHERE t.masuk > 0 OR t.keluar > 0
                UNION ALL
                SELECT
                    2 AS urut, NULL::date AS tr_date, 'Sisa Stok' AS tr_desc, NULL AS tr_code, NULL AS tr_seq, NULL AS tr_seq2, NULL AS tr_type,
                    0 AS masuk, 0 AS keluar,
                    (SELECT opening_qty FROM opening_tx) + COALESCE((SELECT SUM(net_qty) FROM tx), 0) AS sisa
                ORDER BY urut, tr_date, tr_code, tr_seq, tr_seq2
            ";

            $this->results = DB::connection(Session::get('app_code'))->select($sql);
        } catch (\Exception $e) {
            Log::error('ReportStockCard: Error in search()', [
                'error' => $e->getMessage(),
                'wh_code' => $this->wh_code,
                'matl_code' => $this->matl_code,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date
            ]);
            $this->dispatch('error', 'Error loading data: ' . $e->getMessage());
            $this->results = [];
        }
    }

    public function getRoute()
    {
        // Call parent getRoute first
        parent::getRoute();

        // Filter additionalParam from query string for permission check
        // This prevents 403 error when additionalParam is in the URL
        $queryString = request()->getQueryString();
        if ($queryString && str_contains($queryString, 'additionalParam')) {
            // Parse query string and remove additionalParam
            parse_str($queryString, $params);
            unset($params['additionalParam']);

            // Rebuild query string without additionalParam
            $filteredQuery = !empty($params) ? http_build_query($params) : '';

            // Rebuild fullUrl without additionalParam for permission check
            $path = str_replace('.', '/', $this->baseRoute);
            $fullUrl = $filteredQuery ? $path . '?' . $filteredQuery : $path;

            // Recalculate menu_link and permissions without additionalParam
            $menu_link = ConfigMenu::getFullPathLink($fullUrl, $this->actionValue, $this->additionalParam);
            $this->menuName = ConfigMenu::getMenuNameByLink($menu_link);

            // Recalculate permissions with filtered menu_link
            $this->permissions = ConfigRight::getPermissionsByMenu($menu_link);
            Session::put($this->permissionSessionKey, $this->permissions);
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}


