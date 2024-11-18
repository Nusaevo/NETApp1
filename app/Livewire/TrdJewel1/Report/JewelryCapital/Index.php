<?php

namespace App\Livewire\TrdJewel1\Report\JewelryCapital;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\DB;
use App\Services\TrdJewel1\Master\MasterService;
use App\Enums\Constant;
use Illuminate\Support\Facades\Session;

class Index extends BaseComponent
{
    public $results = [];

    protected function onPreRender()
    {
        $this->search();
    }

    public function search()
    {
        initDatabaseConnection();
        $query = "
        SELECT
            SUBSTRING(materials.code FROM 1 FOR 2) AS category,
            SUM(po_dtl.qty) AS total_quantity,
            SUM(po_dtl.amt) AS total_buying_price
        FROM materials
        LEFT JOIN order_dtls AS po_dtl ON materials.id = po_dtl.matl_id AND po_dtl.tr_type = 'PO' AND po_dtl.deleted_at IS NULL
        LEFT JOIN order_hdrs AS po_hdr ON po_hdr.id = po_dtl.trhdr_id AND po_hdr.deleted_at IS NULL
        WHERE materials.deleted_at IS NULL
        GROUP BY category
        ";

        $this->results = DB::connection(Session::get('app_code'))->select($query);
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
