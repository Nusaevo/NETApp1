<?php

namespace App\Livewire\TrdTire1\Report\ReportReservation;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;

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
        SELECT p.id, p.code, p.name
        FROM partners p
        WHERE p.grp = 'C'
        AND p.status_code = 'A'
        AND p.deleted_at IS NULL
    ";
    protected $masterService;

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->customers = $this->masterService->getCustomers();
        $this->resetFilters();
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
                oh.tr_date,
                oh.tr_code,
                p.name as customer_name,
                od.qty
            FROM order_dtls od
            JOIN order_hdrs oh ON od.tr_code = oh.tr_code
            LEFT JOIN partners p ON oh.partner_id = p.id
            LEFT JOIN materials m ON od.matl_id = m.id
            WHERE od.tr_type = 'SO'
            AND od.qty_reff = 0
            AND oh.status_code != 'X'
        ";

        // Add customer filter if selected
        if ($customerCode) {
            $sql .= " AND p.code = '{$customerCode}'";
        }

        // Add material filter if selected
        if ($matlCode) {
            $sql .= " AND od.matl_code = '{$matlCode}'";
        }

        // Always order by material code, customer, and transaction code
        $sql .= " ORDER BY oh.tr_date, oh.tr_code";

        $this->results = DB::connection(Session::get('app_code'))->select($sql);
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
