<?php

namespace App\Livewire\TrdJewel1\Report\PurchaseOrder;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\DB;
use App\Services\TrdJewel1\Master\MasterService;
use App\Enums\Constant;

class Index extends BaseComponent
{
    public $materialCategories1;
    public $category;
    public $startCode;
    public $endCode;
    protected $masterService;

    public $results = [];

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->materialCategories1 = $this->masterService->getMatlCategory1Data($this->appCode);
        $this->resetFilters();
    }


    public function search()
    {
        if (isNullOrEmptyNumber($this->category)) {
            $this->notify('warning', __('generic.error.field_required', ['field' => "Category"]));
            $this->addError('category', "Mohon lengkapi");
            return;
        }

        if (isNullOrEmptyNumber($this->startCode)) {
            $this->notify('warning', __('generic.error.field_required', ['field' => "Kode Awal"]));
            $this->addError('startCode',  "Mohon lengkapi");
            return;
        }

        $this->resetErrorBag();

        $query = "
        SELECT
            order_hdrs.id AS order_id,
            order_hdrs.tr_id AS tr_id,
            order_hdrs.tr_type AS tr_type,
            order_hdrs.tr_date AS tr_date,
            materials.jwl_category1 AS category,
            materials.code AS material_code,
            materials.jwl_wgt_gold AS material_gold,
            materials.jwl_carat AS material_carat,
            materials.descr AS material_descr,
            order_dtls.price AS price,
            CAST(REGEXP_REPLACE(materials.code, '\\D', '', 'g') AS INTEGER) AS code_number,
            so_hdr.tr_id AS no_nota,
            so_hdr.partner_id AS partner_id,
            partners.name AS partner_name,
            -- Subquery to get the first attachment for each material
            (SELECT path
             FROM attachments
             WHERE attached_objecttype = 'Material'
               AND attached_objectid = materials.id
               AND path IS NOT NULL
               AND deleted_at IS NULL
             ORDER BY created_at
             LIMIT 1) AS file_url
        FROM materials
        LEFT JOIN order_dtls ON order_dtls.matl_id = materials.id AND order_dtls.deleted_at IS NULL
        LEFT JOIN order_hdrs ON order_hdrs.tr_id = order_dtls.tr_id AND order_hdrs.tr_type = 'PO' AND order_hdrs.deleted_at IS NULL
        LEFT JOIN order_hdrs AS so_hdr ON so_hdr.tr_id = order_dtls.tr_id AND so_hdr.tr_type = 'SO' AND so_hdr.deleted_at IS NULL
        LEFT JOIN partners ON partners.id = so_hdr.partner_id
        WHERE materials.deleted_at IS NULL
    ";


        if ($this->category) {
            $query .= " AND materials.code LIKE :category";
            $bindings['category'] = '%' . $this->category . '%';
        }

        if ($this->startCode) {
            $query .= " AND CAST(REGEXP_REPLACE(materials.code, '\\D', '', 'g') AS INTEGER) >= :startCode";
            $bindings['startCode'] = $this->startCode;
        }

        if ($this->endCode) {
            $query .= " AND CAST(REGEXP_REPLACE(materials.code, '\\D', '', 'g') AS INTEGER) <= :endCode";
            $bindings['endCode'] = $this->endCode;
        }

        $query .= " ORDER BY CAST(REGEXP_REPLACE(materials.code, '\\D', '', 'g') AS INTEGER) ASC";

        $this->results = DB::connection(Constant::Trdjewel1_ConnectionString())->select($query, $bindings);
    }

    public function resetFilters()
    {
        $this->category = '';
        $this->startCode = 1;
        $this->endCode = '';
        $this->results = [];
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    public function resetResult()
    {
        $this->results = [];
    }
}
