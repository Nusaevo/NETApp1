<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\SalesOrder;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;

    public function mount(): void
    {
        $this->setSearchVisibilityStatus(false);
        $this->customRoute = "";
    }

    public function builder(): Builder
    {
        return OrderHdr::with('OrderDtl', 'Partner')
            ->where('order_hdrs.tr_type', 'SO')
            ->where('order_hdrs.status_code', Status::OPEN)
            ->orderBy('order_hdrs.created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->sortable(),
            Column::make($this->trans("tr_id"), "tr_id")
                ->sortable(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("customer"), "Partner.name")
                ->sortable(),
            Column::make("Total Quantity", "total_qty")
                ->label(function($row) {
                    return currencyToNumeric($row->total_qty);
                })
                ->sortable(),
            Column::make("Barang", "matl_codes")
                ->label(function($row) {
                    return $row->matl_codes;
                })
                ->sortable(),
            Column::make("Total Amount", "total_amt")
                ->label(function($row) {
                    return rupiah(currencyToNumeric($row->total_amt));
                })
                ->sortable(),
            Column::make($this->trans('status'), "status_code")
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => true,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'access' => $this->customRoute ? $this->customRoute : $this->baseRoute
                    ]);
                }),
            Column::make('', 'id')
                ->format(function ($value, $row, Column $column) {
                    $secondButton = '<a href="' . route('TrdJewel1.Transaction.SalesOrder.PrintPdf', ["action" => encryptWithSessionKey('Edit'),'objectId' => encryptWithSessionKey($row->id)]) . '" class="btn btn-primary btn-sm" style="margin-left: 5px; text-decoration: none;">Print</a>';

                    return "<div class='text-center'>". $secondButton."</div>";
                })->html(),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Customer', 'customer_name')
                ->config([
                    'placeholder' => 'Search Customer Name',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = strtoupper($value);
                    $builder->whereHas('Partner', function ($query) use ($value) {
                        $query->where(DB::raw('UPPER(name)'), 'like', '%' . $value . '%');
                    });
                }),
                TextFilter::make('Material Code', 'matl_code')
                ->config([
                    'placeholder' => 'Search Material Code',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = strtoupper($value);
                    $builder->whereExists(function ($query) use ($value) {
                        $query->select(DB::raw(1))
                            ->from('order_dtls')
                            ->whereRaw('order_dtls.tr_id = order_hdrs.tr_id')
                            ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . $value . '%')
                            ->where('order_dtls.tr_type', 'SO');
                    });
                }),
            // SelectFilter::make('Status', 'status_code')
            //     ->options([
            //         Status::OPEN => 'Open',
            //         Status::COMPLETED => 'Selesai',
            //         '' => 'Semua',
            //     ])->filter(function ($builder, $value) {
            //         if ($value === Status::ACTIVE) {
            //             $builder->where('order_hdrs.status_code', Status::ACTIVE);
            //         } else if ($value === Status::COMPLETED) {
            //             $builder->where('order_hdrs.status_code', Status::COMPLETED);
            //         } else if ($value === '') {
            //             $builder->withTrashed();
            //         }
            //     }),
            // DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
            //     $builder->where('order_hdrs.tr_date', '>=', $value);
            // }),
            // DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
            //     $builder->where('order_hdrs.tr_date', '<=', $value);
            // }),
        ];
    }
}
