<?php

namespace App\Livewire\TrdJewel1\Master\Partner;

use Livewire\Component;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;
use App\Enums\Constant;

class TransactionDataTable extends Component
{
    public int $perPage = 50;
    public $partnerID;

    public function mount($partnerID = null): void
    {
        $this->partnerID = $partnerID;
    }

    public function getData()
    {
        $baseQuery = "
            SELECT
                order_hdrs.tr_id as tr_id,
                order_hdrs.tr_type as tr_type,
                order_hdrs.tr_date as tr_date,
                partners.name as partner_name,
                SUM(order_dtls.amt) as total_price,
                'order' as source
            FROM order_hdrs
            LEFT JOIN order_dtls ON order_hdrs.tr_id = order_dtls.tr_id AND order_hdrs.tr_type = order_dtls.tr_type
            LEFT JOIN partners ON order_hdrs.partner_id = partners.id
            WHERE order_hdrs.status_code = :status
        ";

        if ($this->partnerID) {
            $baseQuery .= " AND order_hdrs.partner_id = :partnerID";
        }

        $baseQuery .= " GROUP BY order_hdrs.tr_id, order_hdrs.tr_type, order_hdrs.tr_date, partners.name";

        $unionQuery = "
            UNION ALL
            SELECT
                return_hdrs.tr_id as tr_id,
                return_hdrs.tr_type as tr_type,
                return_hdrs.tr_date as tr_date,
                partners.name as partner_name,
                SUM(return_dtls.amt) as total_price,
                'return' as source
            FROM return_hdrs
            LEFT JOIN return_dtls ON return_hdrs.tr_id = return_dtls.tr_id AND return_hdrs.tr_type = return_dtls.tr_type
            LEFT JOIN partners ON return_hdrs.partner_id = partners.id
            WHERE return_hdrs.status_code = :status
        ";

        if ($this->partnerID) {
            $unionQuery .= " AND return_hdrs.partner_id = :partnerID";
        }

        $unionQuery .= " GROUP BY return_hdrs.tr_id, return_hdrs.tr_type, return_hdrs.tr_date, partners.name";

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

        return DB::connection(Constant::Trdjewel1_ConnectionString())->select($finalQuery, $bindings);
    }

    public function render()
    {
        return view('livewire.trd-jewel1.master.material.transaction-data-table', [
            'data' => $this->getData(),
        ]);
    }
}
