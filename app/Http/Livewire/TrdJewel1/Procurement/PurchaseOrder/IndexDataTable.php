<?php

namespace App\Http\Livewire\TrdJewel1\Procurement\PurchaseOrder;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Status;
use Lang;
use Exception;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->setSort('tr_date', 'desc');
        $this->setFilter('status_code',  Status::OPEN);
    }

    public function builder(): Builder
    {
        return OrderHdr::with('OrderDtl')->where('tr_type', 'PO');
    }
    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("tr_id"), "tr_id")
                ->sortable()
                ->searchable(),
            Column::make($this->trans("supplier"), "Partner.name")
                ->searchable()
                ->sortable(),
                Column::make("Total Quantity", "total_qty")
                ->label(function($row) {
                    return  currencyToNumeric($row->total_qty);
                })
                ->sortable(),
            Column::make("Total Amount", "total_amt")
                ->label(function($row) {
                    return globalCurrency(currencyToNumeric($row->total_amt));
                })
                ->sortable(),
            // Column::make($this->trans('status'), "status_code")
            //     ->searchable()
            //     ->sortable()
            //     ->format(function ($value, $row, Column $column) {
            //         return Status::getStatusString($value);
            //     }),
            // Column::make($this->trans("created_date"), "created_at")
            //     ->searchable()
            //     ->sortable(),
            Column::make($this->trans('action'), 'id')
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
                    // Tombol pertama (Nota Terima Supplier atau Print, tergantung pada status)
                    // if ($row->status_code === Status::ACTIVE) {
                    //     $firstButton = '<a href="' . route("TrdJewel1.Procurement.PurchaseOrder.Detail", ["action" => encryptWithSessionKey('Create'), "objectId" => encryptWithSessionKey($row->id)]) . '" class="btn btn-primary btn-sm" style="text-decoration: none;">Nota Terima Supplier</a>';
                    // } else {
                    //     $firstButton = '';
                    // }
                    $secondButton = '<a href="' . route('TrdJewel1.Procurement.PurchaseOrder.PrintPdf', ["action" => encryptWithSessionKey('Edit'),'objectId' => encryptWithSessionKey($row->id)]) . '" class="btn btn-primary btn-sm" style="margin-left: 5px; text-decoration: none;">Print</a>';

                    return "<div class='text-center'>". $secondButton."</div>";
                })->html(),

        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status_code')
                ->options([
                    Status::OPEN => 'Open',
                    Status::COMPLETED => 'Selesai',
                    '' => 'Semua',
                ])->filter(function ($builder, $value) {
                    if ($value === Status::ACTIVE) {
                        $builder->where('order_hdrs.status_code', Status::ACTIVE);
                    } else if ($value === Status::COMPLETED) {
                        $builder->where('order_hdrs.status_code', Status::COMPLETED);
                    } else if ($value === '') {
                        $builder->withTrashed();
                    }
                }),
            DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '<=', $value);
            }),

        ];
    }
}
