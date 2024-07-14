<?php

namespace App\Http\Livewire\TrdJewel1\Master\Partner;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;

class TransactionDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;
    public int $perPage = 50;
    public $partnerID;

    public function mount($partnerID = null): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSearchVisibilityStatus(false);
        $this->partnerID = $partnerID;
    }

    public function builder(): Builder
    {
        $query = OrderHdr::with('OrderDtl', 'Partner')
            ->where('order_hdrs.status_code', Status::OPEN)
            ->orderBy('order_hdrs.created_at', 'desc');
        if ($this->partnerID) {
            $query->where('order_hdrs.partner_id', $this->partnerID);
        }

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make("Customer", "Partner.name")
                ->sortable(),
            Column::make("Date", "tr_date")
                ->sortable(),
            Column::make("Transaction ID", "tr_id")
                ->sortable(),
            Column::make("Transaction Type", "tr_type")
                ->sortable(),
            Column::make("Barang", "matl_codes")
                    ->label(function($row) {
                        return $row->matl_codes;
                    })
                    ->sortable(),
            Column::make("Total Quantity", "total_qty")
                ->label(function($row) {
                    return currencyToNumeric($row->total_qty);
                })
                ->sortable(),
            Column::make("Total Amount", "total_amt")
                ->label(function($row) {
                    return rupiah(currencyToNumeric($row->total_amt));
                })
                ->sortable(),
            // Column::make("Status", "status_code")
            //     ->sortable()
            //     ->format(function ($value, $row, Column $column) {
            //         return Status::getStatusString($value);
            //     }),
        ];
    }

    public function filters(): array
    {
        return [
            // TextFilter::make('Customer', 'customer_name')
            //     ->config([
            //         'placeholder' => 'Cari Customer',
            //         'maxlength' => '50',
            //     ])
            //     ->filter(function (Builder $builder, string $value) {
            //         $value = strtoupper($value);
            //         $builder->whereHas('Partner', function ($query) use ($value) {
            //             $query->where(DB::raw('UPPER(name)'), 'like', '%' . $value . '%');
            //         });
            //     }),
            TextFilter::make('Kode Barang', 'matl_code')
                ->config([
                    'placeholder' => 'Cari Kode Barang',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = strtoupper($value);
                    $builder->whereExists(function ($query) use ($value) {
                        $query->select(DB::raw(1))
                            ->from('order_dtls')
                            ->whereRaw('order_dtls.tr_id = order_hdrs.tr_id')
                            ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . $value . '%');
                    });
                }),
        ];
    }
}
