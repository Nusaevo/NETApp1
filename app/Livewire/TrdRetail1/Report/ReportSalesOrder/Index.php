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
        $where = "WHERE so_dtl.tr_type = 'SO' AND so_dtl.deleted_at IS NULL AND so_hdr.deleted_at IS NULL AND materials.deleted_at IS NULL";

        if ($this->startDate && $this->endDate) {
            $where .= " AND so_hdr.tr_date BETWEEN ? AND ?";
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

        if ($this->startQty && $this->endQty) {
            $where .= " AND so_dtl.qty BETWEEN ? AND ?";
            $params[] = $this->startQty;
            $params[] = $this->endQty;
        }


        $query = "
            SELECT
                so_hdr.tr_date,
                materials.id AS material_id,
                materials.code AS material_code,
                materials.category,
                materials.brand,
                materials.type_code,
                materials.specs->>'color_code' AS color_code,
                materials.specs->>'color_name' AS color_name,
                so_dtl.qty,
                so_dtl.price,
                (so_dtl.qty * so_dtl.price) AS total
            FROM order_dtls so_dtl
            LEFT JOIN materials ON materials.id = so_dtl.matl_id
            LEFT JOIN order_hdrs so_hdr ON so_hdr.id = so_dtl.trhdr_id
            LEFT JOIN partners ON partners.id = so_hdr.partner_id
            $where
        ";

        $this->results = DB::connection(Session::get('app_code'))->select($query, $params);
    }

    public function resetFilters()
    {
        $this->startDate = null;
        $this->endDate = null;
        $this->merk = null;
        $this->jenis = null;
        $this->customer = null;
        $this->startQty = null;
        $this->endQty = null;
        $this->groupBy = 'Tanggal';
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
