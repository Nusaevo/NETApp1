<?php

namespace App\Livewire\TrdJewel1\Report\PurchaseOrder;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
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
        $this->materialCategories1 = $this->masterService->getMatlCategory1Data();
        $this->resetFilters();
    }


    public function search()
    {
        initDatabaseConnection();
        if (isNullOrEmptyNumber($this->category)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Category"]));
            $this->addError('category', "Mohon lengkapi");
            return;
        }

        if (isNullOrEmptyNumber($this->startCode)) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Kode Awal"]));
            $this->addError('startCode',  "Mohon lengkapi");
            return;
        }

        $this->resetErrorBag();

        $query = "
        SELECT
            materials.jwl_category1 AS category,
            materials.jwl_category2 AS category2,
            materials.code AS material_code,
            materials.jwl_wgt_gold AS material_gold,
            materials.jwl_carat AS material_carat,
            materials.descr AS material_descr,
            materials.jwl_buying_price_usd AS price,
            CAST(REGEXP_REPLACE(materials.code, '\\D', '', 'g') AS INTEGER) AS code_number,
            so_hdr.tr_date AS tr_date,
            so_hdr.tr_id AS no_nota,
            so_hdr.partner_id AS partner_id,
            so_dtl.price AS selling_price,
            partners.name AS partner_name,
            (SELECT path
             FROM attachments
             WHERE attached_objecttype = 'Material'
               AND attached_objectid = materials.id
               AND path IS NOT NULL
               AND deleted_at IS NULL
             ORDER BY created_at
             LIMIT 1) AS file_url
        FROM materials
        LEFT outer JOIN order_dtls AS so_dtl ON materials.id = so_dtl.matl_id AND so_dtl.tr_type = 'SO' AND so_dtl.deleted_at IS NULL
        LEFT outer JOIN order_hdrs AS so_hdr ON so_hdr.id = so_dtl.trhdr_id AND so_hdr.tr_type = 'SO' AND so_hdr.deleted_at IS NULL
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

        $this->results = DB::connection(Session::get('app_code'))->select($query, $bindings);
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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function resetResult()
    {
        $this->results = [];
    }
}
