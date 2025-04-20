<?php

namespace App\Livewire\TrdRetail1\Report\ReportStock;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdRetail1\Master\MasterService;

class Index extends BaseComponent
{
    public $results = [];
    public $merk;
    public $jenis;
    public $customer;
    public $startQty;
    public $endQty;

    public $merkOptions = [];
    public $jenisOptions = [];
    public $customerOptions = [];

    protected $masterService;

    /**
     * Dipanggil sebelum render pertama kali.
     */
    protected function onPreRender()
    {
        // Siapkan service kalau perlu
        $this->masterService = new MasterService();

        // Ambil data customer
        $this->customerOptions = $this->masterService->getCustomers();

        // Ambil data Merk
        $this->merkOptions = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->whereNull('deleted_at')
            ->whereNotNull('brand')
            ->selectRaw('DISTINCT brand AS label, brand AS value')
            ->get()
            ->map(fn($item) => [
                'label' => $item->label,
                'value' => $item->value,
            ])
            ->toArray();

        // Ambil data Jenis
        $this->jenisOptions = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->whereNull('deleted_at')
            ->whereNotNull('type_code')
            ->selectRaw('DISTINCT type_code AS label, type_code AS value')
            ->get()
            ->map(fn($item) => [
                'label' => $item->label,
                'value' => $item->value,
            ])
            ->toArray();
    }

    /**
     * Event: Search
     */
    public function search()
    {
        $params = [];
        $where = "WHERE matl_uoms.deleted_at IS NULL AND materials.deleted_at IS NULL";

        // Filter Merk
        if ($this->merk) {
            $where .= " AND materials.brand ILIKE ?";
            $params[] = '%' . $this->merk . '%';
        }

        // Filter Jenis
        if ($this->jenis) {
            $where .= " AND materials.type_code ILIKE ?";
            $params[] = '%' . $this->jenis . '%';
        }

        // Filter Customer (opsional, kalau memang materials ada partner)
        if ($this->customer) {
            $where .= " AND partners.name ILIKE ?";
            $params[] = '%' . $this->customer . '%';
        }

        // Filter Qty range
        if ($this->startQty && $this->endQty) {
            $where .= " AND matl_uoms.qty_oh BETWEEN ? AND ?";
            $params[] = $this->startQty;
            $params[] = $this->endQty;
        }

        // Query utamanya
        $query = "
            SELECT
                materials.id AS material_id,
                materials.category,
                materials.brand,
                materials.type_code,
                materials.specs->>'color_code' AS color_code,
                materials.specs->>'color_name' AS color_name,
                materials.code AS material_code,
                matl_uoms.matl_uom,
                matl_uoms.qty_oh AS qty,
                matl_uoms.selling_price AS price,
                matl_uoms.buying_price AS cost
            FROM matl_uoms
            LEFT JOIN materials ON materials.id = matl_uoms.matl_id
            $where
        ";

        // Jalankan query
        $this->results = DB::connection(Session::get('app_code'))->select($query, $params);
    }

    /**
     * Event: Reset Filter
     */
    public function resetFilters()
    {
        $this->merk      = null;
        $this->jenis     = null;
        $this->customer  = null;
        $this->startQty  = null;
        $this->endQty    = null;

        // kosongkan results
        $this->results = [];
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
