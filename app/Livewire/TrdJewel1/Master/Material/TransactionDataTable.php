<?php

namespace App\Livewire\TrdJewel1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TrdJewel1\Transaction\OrderDtl;
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;

class TransactionDataTable extends BaseDataTableComponent
{
    protected $model = OrderDtl::class;
    public int $perPage = 50;
    public $materialID;

    public function mount($materialID = null): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSearchVisibilityStatus(false);
        $this->materialID = $materialID;
    }

    public function builder(): Builder
    {
        $query = OrderDtl::query()
            ->leftJoin('order_hdrs', function ($join) {
                $join->on('order_dtls.tr_id', '=', 'order_hdrs.tr_id')
                     ->on('order_dtls.tr_type', '=', 'order_hdrs.tr_type');
            })
            ->leftJoin('partners', 'order_hdrs.partner_id', '=', 'partners.id')
            ->where('order_hdrs.status_code', Status::OPEN)
            ->orderBy('order_dtls.created_at', 'desc')
            ->select('order_dtls.*', 'order_hdrs.tr_date', 'order_hdrs.tr_id', 'order_hdrs.tr_type', 'partners.name as partner_name');

        if ($this->materialID) {
            $query->where('order_dtls.matl_id', $this->materialID);
        }

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make("Customer/Supplier", "OrderHdr.Partner.name")
                ->sortable(),
            Column::make("Date", "OrderHdr.tr_date")
                ->sortable(),
            Column::make("Transaction ID", "OrderHdr.tr_id")
                ->sortable(),
            Column::make("Transaction Type", "OrderHdr.tr_type")
                ->sortable(),
            Column::make("Harga", "price")
                ->label(function($row) {
                    $price = $row->price;
                    if ($row->OrderHdr->tr_type == 'PO') {
                        return dollar(currencyToNumeric($price));
                    } else {
                        return rupiah(currencyToNumeric($price));
                    }
                })
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            // TextFilter::make('Customer/Supplier', 'customer_name')
            //     ->config([
            //         'placeholder' => 'Cari Customer/Supplier',
            //         'maxlength' => '50',
            //     ])
            //     ->filter(function (Builder $builder, string $value) {
            //         $value = strtoupper($value);
            //         $builder->whereHas('OrderHdr.Partner', function ($query) use ($value) {
            //             $query->where(DB::raw('UPPER(name)'), 'like', '%' . $value . '%');
            //         });
            //     }),
        ];
    }
}
