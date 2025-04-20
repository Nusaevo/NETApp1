<?php

namespace App\Livewire\TrdTire1\Master\Partner;

use App\Livewire\Component\DetailComponent;
use Livewire\Component;
use Illuminate\Support\Facades\{DB, Session};
use Rappasoft\LaravelLivewireTables\Views\{Column, Filters\TextFilter};
use App\Enums\{Status, Constant};


class TransactionDataTable extends DetailComponent
{
    public int $perPage = 50;
    public $partnerID;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        $this->partnerID = $objectIdValue;
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    protected function onPreRender()
    {
    }

    public function getData()
    {
        $baseQuery = "
            SELECT
                order_hdrs.id as id,
                order_hdrs.tr_id as tr_id,
                order_hdrs.tr_type as tr_type,
                order_hdrs.tr_date as tr_date,
                SUM(order_dtls.amt) as total_price,
                'order' as source
            FROM order_hdrs
            LEFT JOIN order_dtls ON order_hdrs.tr_id = order_dtls.tr_id AND order_hdrs.tr_type = order_dtls.tr_type
            WHERE order_hdrs.status_code = :status
        ";

        if ($this->partnerID) {
            $baseQuery .= " AND order_hdrs.partner_id = :partnerID";
        }

        $baseQuery .= " GROUP BY order_hdrs.id,order_hdrs.tr_id, order_hdrs.tr_type, order_hdrs.tr_date";

        $unionQuery = "
            UNION ALL
            SELECT
                return_hdrs.id as id,
                return_hdrs.tr_id as tr_id,
                return_hdrs.tr_type as tr_type,
                return_hdrs.tr_date as tr_date,
                SUM(return_dtls.amt) as total_price,
                'return' as source
            FROM return_hdrs
            LEFT JOIN return_dtls ON return_hdrs.tr_id = return_dtls.tr_id AND return_hdrs.tr_type = return_dtls.tr_type
            WHERE return_hdrs.status_code = :status
        ";

        if ($this->partnerID) {
            $unionQuery .= " AND return_hdrs.partner_id = :partnerID";
        }

        $unionQuery .= " GROUP BY return_hdrs.id,return_hdrs.tr_id, return_hdrs.tr_type, return_hdrs.tr_date";

        $finalQuery = $baseQuery . $unionQuery . "
            ORDER BY tr_date DESC, tr_id DESC
            LIMIT :limit
        ";

        $bindings = [
            'status' => Status::OPEN,
            'limit' => $this->perPage,
        ];

        if ($this->partnerID) {
            $bindings['partnerID'] = $this->partnerID;
        }

        return DB::connection(Session::get('app_code'))->select($finalQuery, $bindings);
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'data' => $this->getData(),
        ]);
    }
}
