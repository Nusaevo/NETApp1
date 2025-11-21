<?php

namespace App\Livewire\TrdRetail1\Report\ReportSalesOrder;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\DB;
use App\Services\TrdJewel1\Master\MasterService;
use App\Enums\Constant;
use Illuminate\Support\Facades\Session;

class Index extends BaseComponent
{
    public $results = [];
    public $startDate;
    public $endDate;
    public $transactionType = ''; // SO, SR, SOR
    public $transactionNumber;
    public $customer;
    public $customerOptions = [];
    public $filterCategory = '';
    public $filterBrand = '';
    public $filterType = '';
    public $resetKey = 0; // Key to force dropdown re-rendering
    public $selectedMaterials = [];
    public $expandedRows = []; // Track which rows are expanded
    public $rowDetails = []; // Store detail data for expanded rows
    protected $masterService;
    public $groupBy = 'Tanggal';
    protected $listeners = [
        'DropdownSelected' => 'DropdownSelected'
    ];
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);

        // Set default dates to current month (first day to today)
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->customerOptions = $this->masterService->getCustomers();
    }

    public function search()
    {
        // Validation: require at least start and end dates
        if (empty($this->startDate) || empty($this->endDate)) {
            $this->dispatch('error', 'Tanggal Awal dan Tanggal Akhir harus diisi terlebih dahulu.');
            return;
        }

        $params = [];
        $unionQueries = [];

        // Base date filter
        $dateFilter = "";
        if ($this->startDate && $this->endDate) {
            $dateFilter = " AND hdr.tr_date BETWEEN ? AND ?";
        }

        // Transaction number filter
        $trIdFilter = "";
        if ($this->transactionNumber) {
            $trIdFilter = " AND hdr.tr_id::text ILIKE ?";
        }

        // Customer filter
        $customerFilter = "";
        if ($this->customer) {
            $customerFilter = " AND partners.name ILIKE ?";
        }

        // Brand filter
        $brandFilter = "";
        if ($this->filterBrand) {
            $brandFilter = " AND materials.brand = ?";
        }

        // Category filter
        $categoryFilter = "";
        if ($this->filterCategory) {
            $categoryFilter = " AND materials.category = ?";
        }

        // Type filter
        $typeFilter = "";
        if ($this->filterType) {
            $typeFilter = " AND materials.class_code = ?";
        }

        // Material filter
        $materialFilter = "";
        if (!empty($this->selectedMaterials)) {
            $materialIds = array_column($this->selectedMaterials, 'id');
            $placeholders = str_repeat('?,', count($materialIds) - 1) . '?';
            $materialFilter = " AND materials.id IN ($placeholders)";
        }

        // Sales Order (SO) Query - Group by header
        if (empty($this->transactionType) || $this->transactionType === 'SO') {
            $soQuery = "
                SELECT
                    hdr.tr_date,
                    hdr.tr_id,
                    'SO' as tr_type,
                    partners.name AS customer_name,
                    STRING_AGG(DISTINCT materials.code, ', ') AS material_codes,
                    SUM(dtl.qty) AS total_qty,
                    SUM(dtl.qty * dtl.price) AS total_amount
                FROM order_dtls dtl
                LEFT JOIN materials ON materials.id = dtl.matl_id
                LEFT JOIN order_hdrs hdr ON hdr.id = dtl.trhdr_id
                LEFT JOIN partners ON partners.id = hdr.partner_id
                WHERE dtl.tr_type = 'SO' AND dtl.deleted_at IS NULL AND hdr.deleted_at IS NULL AND materials.deleted_at IS NULL
                $dateFilter $trIdFilter $customerFilter $brandFilter $categoryFilter $typeFilter $materialFilter
                GROUP BY hdr.tr_date, hdr.tr_id, hdr.id, partners.name
            ";
            $unionQueries[] = $soQuery;
        }

        // Sales Return (SR) Query - Group by header
        if (empty($this->transactionType) || $this->transactionType === 'SR') {
            $srQuery = "
                SELECT
                    hdr.tr_date,
                    hdr.tr_id,
                    'SR' as tr_type,
                    partners.name AS customer_name,
                    STRING_AGG(DISTINCT materials.code, ', ') AS material_codes,
                    SUM(dtl.qty) AS total_qty,
                    SUM(dtl.qty * dtl.price) AS total_amount
                FROM return_dtls dtl
                LEFT JOIN materials ON materials.id = dtl.matl_id
                LEFT JOIN return_hdrs hdr ON hdr.id = dtl.trhdr_id
                LEFT JOIN partners ON partners.id = hdr.partner_id
                WHERE dtl.tr_type = 'SR' AND dtl.deleted_at IS NULL AND hdr.deleted_at IS NULL AND materials.deleted_at IS NULL
                $dateFilter $trIdFilter $customerFilter $brandFilter $categoryFilter $typeFilter $materialFilter
                GROUP BY hdr.tr_date, hdr.tr_id, hdr.id, partners.name
            ";
            $unionQueries[] = $srQuery;
        }

        // Sales Order Return/Exchange (SOR) Query - Group by header
        if (empty($this->transactionType) || $this->transactionType === 'SOR') {
            $sorQuery = "
                SELECT
                    hdr.tr_date,
                    hdr.tr_id,
                    'SOR' as tr_type,
                    partners.name AS customer_name,
                    STRING_AGG(DISTINCT materials.code, ', ') AS material_codes,
                    SUM(dtl.qty) AS total_qty,
                    SUM(dtl.qty * dtl.price) AS total_amount
                FROM order_dtls dtl
                LEFT JOIN materials ON materials.id = dtl.matl_id
                LEFT JOIN order_hdrs hdr ON hdr.id = dtl.trhdr_id
                LEFT JOIN partners ON partners.id = hdr.partner_id
                WHERE dtl.tr_type = 'SOR' AND dtl.deleted_at IS NULL AND hdr.deleted_at IS NULL AND materials.deleted_at IS NULL
                $dateFilter $trIdFilter $customerFilter $brandFilter $categoryFilter $typeFilter $materialFilter
                GROUP BY hdr.tr_date, hdr.tr_id, hdr.id, partners.name
            ";
            $unionQueries[] = $sorQuery;
        }

        // Build final query with UNION ALL
        if (empty($unionQueries)) {
            $this->results = [];
            return;
        }

        $finalQuery = implode(' UNION ALL ', $unionQueries) . " ORDER BY tr_date DESC, tr_id";

        // Build parameters array
        $paramCount = count($unionQueries);
        for ($i = 0; $i < $paramCount; $i++) {
            if ($this->startDate && $this->endDate) {
                $params[] = $this->startDate;
                $params[] = $this->endDate;
            }
            if ($this->transactionNumber) {
                $params[] = '%' . $this->transactionNumber . '%';
            }
            if ($this->customer) {
                $params[] = '%' . $this->customer . '%';
            }
            if ($this->filterBrand) {
                $params[] = $this->filterBrand;
            }
            if ($this->filterCategory) {
                $params[] = $this->filterCategory;
            }
            if ($this->filterType) {
                $params[] = $this->filterType;
            }
            if (!empty($this->selectedMaterials)) {
                foreach ($this->selectedMaterials as $material) {
                    $params[] = $material['id'];
                }
            }
        }

        $this->results = DB::connection(Session::get('app_code'))->select($finalQuery, $params);
    }

    public function resetFilters()
    {
        // Reset default dates to current month
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->transactionType = '';
        $this->transactionNumber = null;
        $this->customer = null;
        $this->filterCategory =  null;
        $this->filterBrand =  null;
        $this->filterType =  null;
        $this->selectedMaterials = [];
        $this->results = [];

        // Increment reset key to force dropdown re-rendering
        $this->resetKey++;

        // Dispatch browser event to reset Select2 dropdowns
        $this->dispatch('resetSelect2Dropdowns');
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

    public function toggleRowDetails($trId, $trType)
    {
        $key = $trId . '_' . $trType;

        if (in_array($key, $this->expandedRows)) {
            // Remove from expanded rows
            $this->expandedRows = array_filter($this->expandedRows, function($item) use ($key) {
                return $item !== $key;
            });
            unset($this->rowDetails[$key]);
        } else {
            // Add to expanded rows and fetch details
            $this->expandedRows[] = $key;
            $this->rowDetails[$key] = $this->getRowDetails($trId, $trType);
        }
    }

    private function getRowDetails($trId, $trType)
    {
        $query = "";
        $params = [];

        if ($trType === 'SO' || $trType === 'SOR') {
            $query = "
                SELECT
                    materials.code AS material_code,
                    materials.name AS material_name,
                    materials.category,
                    materials.brand,
                    materials.class_code AS type_code,
                    materials.specs->>'color_code' AS color_code,
                    materials.specs->>'color_name' AS color_name,
                    dtl.qty,
                    dtl.price,
                    (dtl.qty * dtl.price) AS total
                FROM order_dtls dtl
                LEFT JOIN materials ON materials.id = dtl.matl_id
                WHERE dtl.tr_id = ? AND dtl.tr_type = ? AND dtl.deleted_at IS NULL
                ORDER BY dtl.id
            ";
        } else { // SR
            $query = "
                SELECT
                    materials.code AS material_code,
                    materials.name AS material_name,
                    materials.category,
                    materials.brand,
                    materials.class_code AS type_code,
                    materials.specs->>'color_code' AS color_code,
                    materials.specs->>'color_name' AS color_name,
                    dtl.qty,
                    dtl.price,
                    (dtl.qty * dtl.price) AS total
                FROM return_dtls dtl
                LEFT JOIN materials ON materials.id = dtl.matl_id
                WHERE dtl.tr_id = ? AND dtl.tr_type = ? AND dtl.deleted_at IS NULL
                ORDER BY dtl.id
            ";
        }

        $params = [$trId, $trType];

        return DB::connection(Session::get('app_code'))->select($query, $params);
    }

    public function expandAllRowsForPrint()
    {
        // Expand all rows for printing
        foreach ($this->results as $result) {
            $key = $result->tr_id . '_' . $result->tr_type;
            if (!in_array($key, $this->expandedRows)) {
                $this->expandedRows[] = $key;
                $this->rowDetails[$key] = $this->getRowDetails($result->tr_id, $result->tr_type);
            }
        }
    }
}
