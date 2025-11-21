<?php

namespace App\Livewire\TrdTire1\Report\ReportReservation;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session, Log};
use App\Services\TrdTire1\Master\MasterService;
use App\Models\SysConfig1\{ConfigMenu, ConfigRight};

class Index extends BaseComponent
{
    public $customer_id;
    public $customer_code;
    public $matl_code;
    public $matl_id;

    public $results = [];
    public $customers = [];
    public $material_name = '';
    public $customer_name = '';
    public $materialQuery = "
        SELECT m.id, m.code, m.name
        FROM materials m
        WHERE m.status_code = 'A'
        AND m.deleted_at IS NULL
    ";
    public $customerQuery = "
        SELECT p.id, p.code, p.name, p.address, p.city
        FROM partners p
        WHERE p.grp = 'C'
        AND p.status_code = 'A'
        AND p.deleted_at IS NULL
    ";
    protected $masterService;
       protected $listeners = [
        'DropdownSelected' => 'DropdownSelected'
    ];
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
        $this->customers = $this->masterService->getCustomers();

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

                                // Auto-load data when material is set from additionalParam
                                $this->search();
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // If parsing fails, silently continue without setting filters
                // Log error for debugging if needed
                Log::warning('ReportReservation: Failed to parse additionalParam', [
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
        $this->customer_id = '';
        $this->customer_code = '';
        $this->matl_code = '';
        $this->matl_id = '';
        $this->results = [];
        $this->material_name = '';
        $this->customer_name = '';
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

    public function onCustomerChanged()
    {
        // Get customer code and name from selected customer ID
        if ($this->customer_id) {
            $customer = DB::connection(Session::get('app_code'))
                ->table('partners')
                ->select('code', 'name')
                ->where('id', $this->customer_id)
                ->first();

            if ($customer) {
                $this->customer_code = $customer->code;
                $this->customer_name = $customer->name;
            }
        } else {
            $this->customer_code = '';
            $this->customer_name = '';
        }
    }

    public function resetResult()
    {
        $this->results = [];
    }

    public function search()
    {
        $customerCode = $this->customer_code ? addslashes($this->customer_code) : null;
        $matlCode = $this->matl_code ? addslashes($this->matl_code) : null;

        $sql = "
            SELECT
                od.matl_code,
                m.name as matl_name,
                COALESCE(stock.stock_qty, 0) as stock_qty,
                oh.tr_date,
                oh.tr_code,
                CONCAT_WS(' - ', p.name, p.address, p.city) as customer_name,
                od.qty
            FROM order_dtls od
            JOIN order_hdrs oh ON od.tr_code = oh.tr_code
            LEFT JOIN partners p ON oh.partner_id = p.id
            LEFT JOIN materials m ON od.matl_id = m.id
            LEFT JOIN (
                SELECT
                    matl_code,
                    SUM(qty_oh) as stock_qty
                FROM ivt_bals
                GROUP BY matl_code
            ) stock ON od.matl_code = stock.matl_code
            WHERE od.tr_type = 'SO'
            AND od.qty_reff = 0
            AND oh.status_code != 'X'
            AND (m.category IS NULL OR UPPER(TRIM(COALESCE(m.category, ''))) <> 'JASA')
        ";

        // Add customer filter if selected
        if ($customerCode) {
            $sql .= " AND p.code = '{$customerCode}'";
        }

        // Add material filter if selected
        if ($matlCode) {
            $sql .= " AND od.matl_code = '{$matlCode}'";
        }

        // Order by material code first to group by material, then by date and transaction code
        $sql .= " ORDER BY od.matl_code, oh.tr_date, oh.tr_code";

        $this->results = DB::connection(Session::get('app_code'))->select($sql);
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
