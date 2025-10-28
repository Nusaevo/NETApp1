<?php

namespace App\Livewire\TrdTire1\Report\ReportReceivables;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;

class Index extends BaseComponent
{
    public $customer_id;
    public $customer_code;
    public $start_date;
    public $end_date;
    public $results = [];
    public $customers = [];
    public $customer_name = '';
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
        // $this->resetFilters();
    }

    public function resetFilters()
    {
        $this->customer_id = '';
        $this->customer_code = '';
        $this->start_date = '';
        $this->end_date = '';
        $this->results = [];
        $this->customer_name = '';
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
        $startDate = $this->start_date ? addslashes($this->start_date) : null;
        $endDate = $this->end_date ? addslashes($this->end_date) : null;

        // Build base query
        $sql = "
            SELECT
                bh.tr_date,
                bh.tr_code,
                p.name as customer_name,
                bh.amt,
                bh.amt_reff,
                bh.print_date
            FROM billing_hdrs bh
            LEFT JOIN partners p ON bh.partner_id = p.id
            WHERE (bh.amt - bh.amt_reff) > 0
            AND bh.status_code != 'X'
            AND bh.deleted_at IS NULL
        ";

        // Add date filters only if dates are provided
        if ($startDate) {
            $sql .= " AND bh.tr_date >= '{$startDate}'";
        }

        if ($endDate) {
            $sql .= " AND bh.tr_date <= '{$endDate}'";
        }

        // Add customer filter only if customer is selected
        if ($customerCode) {
            $sql .= " AND bh.partner_code = '{$customerCode}'";
        }

        $sql .= " ORDER BY bh.tr_date asc";

        $this->results = DB::connection(Session::get('app_code'))->select($sql);
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
