<?php

namespace App\Livewire\TrdRetail1\Report\ReportStock;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdRetail1\Master\MasterService;

class Index extends BaseComponent
{
    public $results = [];
    public $expandedRows = [];
    public $rowDetails = [];

    // Filter properties
    public $filterCategory;
    public $filterBrand;
    public $filterType;
    public $startQty;
    public $endQty;
    public $filterCode;
    public $filterName;

    protected $masterService;

    /**
     * Dipanggil sebelum render pertama kali.
     */
    protected function onPreRender()
    {
        $this->masterService = new MasterService();
    }

    /**
     * Toggle row details for material UOMs
     */
    public function toggleRowDetails($materialId)
    {
        $rowKey = $materialId;

        if (in_array($rowKey, $this->expandedRows)) {
            // Remove from expanded rows
            $this->expandedRows = array_filter($this->expandedRows, fn($key) => $key !== $rowKey);
            unset($this->rowDetails[$rowKey]);
        } else {
            // Add to expanded rows and fetch details
            $this->expandedRows[] = $rowKey;
            $this->loadRowDetails($materialId);
        }
    }

    /**
     * Load UOM details for a specific material
     */
    private function loadRowDetails($materialId)
    {
        $query = "
            SELECT
                matl_uoms.matl_uom,
                matl_uoms.qty_oh AS qty,
                matl_uoms.selling_price AS price,
                matl_uoms.buying_price AS cost,
                matl_uoms.created_at
            FROM matl_uoms
            LEFT JOIN materials ON materials.id = matl_uoms.matl_id
            WHERE matl_uoms.matl_id = ?
              AND matl_uoms.deleted_at IS NULL
              AND materials.deleted_at IS NULL
            ORDER BY matl_uoms.matl_uom
        ";

        $details = DB::connection(Session::get('app_code'))->select($query, [$materialId]);
        $this->rowDetails[$materialId] = $details;
    }

    /**
     * Event: Search
     */
    public function search()
    {
        $params = [];
        $where = "WHERE materials.deleted_at IS NULL";

        // Filter Category
        if ($this->filterCategory) {
            $where .= " AND materials.category = ?";
            $params[] = $this->filterCategory;
        }

        // Filter Brand
        if ($this->filterBrand) {
            $where .= " AND materials.brand = ?";
            $params[] = $this->filterBrand;
        }

        // Filter Type
        if ($this->filterType) {
            $where .= " AND materials.type_code = ?";
            $params[] = $this->filterType;
        }
        // Filter Material Code
        if ($this->filterCode) {
            $where .= " AND materials.code ILIKE ?";
            $params[] = '%' . $this->filterCode . '%';
        }
        // Filter Material Name
        if ($this->filterName) {
            $where .= " AND materials.name ILIKE ?";
            $params[] = '%' . $this->filterName . '%';
        }

        // Query to list each material UOM without aggregation
        $query = "
            SELECT
                materials.id AS material_id,
                materials.category,
                materials.brand,
                materials.type_code,
                materials.specs->>'color_code' AS color_code,
                materials.specs->>'color_name' AS color_name,
                materials.code AS material_code,
                materials.name AS material_name,
                matl_uoms.matl_uom,
                matl_uoms.qty_oh AS qty,
                matl_uoms.selling_price AS price,
                matl_uoms.buying_price AS cost
            FROM materials
            LEFT JOIN matl_uoms ON matl_uoms.matl_id = materials.id
                AND matl_uoms.deleted_at IS NULL
            $where
        ";

        // Filter Qty range - apply after grouping
        if ($this->startQty !== null && $this->endQty !== null) {
            $query = "
                SELECT * FROM ($query) AS grouped_materials
                WHERE total_qty BETWEEN ? AND ?
            ";
            $params[] = $this->startQty;
            $params[] = $this->endQty;
        }

        $query .= " ORDER BY materials.category, materials.brand, materials.type_code LIMIT 100";

        // Execute query
        $this->results = DB::connection(Session::get('app_code'))->select($query, $params);
    }

    /**
     * Event: Reset Filter
     */
    public function resetFilters()
    {
        $this->filterCategory = null;
        $this->filterBrand = null;
        $this->filterType = null;
        $this->startQty = null;
        $this->endQty = null;
        $this->filterCode = null;
        $this->filterName = null;

        // Reset expanded rows
        $this->expandedRows = [];
        $this->rowDetails = [];

        // Clear results
        $this->results = [];

        // Dispatch event to reset dropdowns
        $this->dispatch('resetSelect2Dropdowns');
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
