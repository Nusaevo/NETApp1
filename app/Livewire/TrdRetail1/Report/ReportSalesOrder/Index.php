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
    public $startQty;
    public $endQty;
    public $merk;
    public $jenis;
    public $merkOptions;
    public $jenisOptions;
    public $customer;
    public $customerOptions = [];
    protected $masterService;
    public $groupBy = 'Tanggal';
    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->customerOptions = $this->masterService->getCustomers();
        $this->merkOptions = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->whereNull('deleted_at')
            ->whereNotNull('brand')
            ->selectRaw('DISTINCT brand AS label, brand AS value')
            ->get()
            ->map(
                fn($item) => [
                    'label' => $item->label,
                    'value' => $item->value,
                ],
            )
            ->toArray();

        // JENIS OPTIONS
        $this->jenisOptions = DB::connection(Session::get('app_code'))
            ->table('materials')
            ->whereNull('deleted_at')
            ->whereNotNull('type_code') // atau 'class_code' kalau itu nama field-nya
            ->selectRaw('DISTINCT type_code AS label, type_code AS value')
            ->get()
            ->map(
                fn($item) => [
                    'label' => $item->label,
                    'value' => $item->value,
                ],
            )
            ->toArray();
    }

    public function search()
    {
        $params = [];
        $where = "WHERE po_dtl.tr_type = 'PO' AND po_dtl.deleted_at IS NULL AND po_hdr.deleted_at IS NULL AND materials.deleted_at IS NULL";

        if ($this->startDate && $this->endDate) {
            $where .= " AND po_hdr.tr_date BETWEEN ? AND ?";
            $params[] = $this->startDate;
            $params[] = $this->endDate;
        }

        if ($this->merk) {
            $where .= " AND materials.brand ILIKE ?";
            $params[] = '%' . $this->merk . '%';
        }

        if ($this->jenis) {
            $where .= " AND materials.type_code ILIKE ?";
            $params[] = '%' . $this->jenis . '%';
        }

        if ($this->customer) {
            $where .= " AND partners.name ILIKE ?";
            $params[] = '%' . $this->customer . '%';
        }

        $query = "
            SELECT
                po_hdr.tr_date,
                materials.code AS material_code,
                materials.category,
                materials.brand,
                materials.type_code,
                materials.specs->>'color_code' AS color_code,
                materials.specs->>'color_name' AS color_name,
                po_dtl.qty,
                po_dtl.price,
                (po_dtl.qty * po_dtl.price) AS total,
                partners.name AS partner_name
            FROM order_dtls po_dtl
            LEFT JOIN materials ON materials.id = po_dtl.matl_id
            LEFT JOIN order_hdrs po_hdr ON po_hdr.id = po_dtl.trhdr_id
            LEFT JOIN partners ON partners.id = po_hdr.partner_id
            $where
            ORDER BY po_hdr.tr_date ASC
        ";


        $this->results = DB::connection(Session::get('app_code'))->select($query, $params);
    }

    public function resetFilters()
    {
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
