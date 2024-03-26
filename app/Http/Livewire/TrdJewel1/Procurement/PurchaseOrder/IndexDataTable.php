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
        $this->setSort('created_at', 'desc');
        $this->setFilter('status_code',  Status::ACTIVE);
    }
    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable()
                ->searchable(),
            Column::make("Tanggal", "tr_date")
                ->searchable()
                ->sortable(),
            Column::make("Supplier", "Partner.name")
                ->searchable()
                ->sortable(),
           Column::make("Status", "status_code")
                    ->searchable()
                    ->sortable()
                    ->format(function ($value, $row, Column $column) {
                        return Status::getStatusString($value);
                    }),
            Column::make('Created Date', 'created_at')
                ->sortable(),
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
            // LinkColumn::make('')
            //     ->title(function ($row) {
            //         return $row->status_code === "ACT" ? 'Nota Terima Supplier' : '';
            //     })
            //     ->location(function ($row) {
            //         if ($row->status_code === "ACT") {
            //             return route("PurchasesDeliveries.detail", ["action" => encryptWithSessionKey('Create'), "objectId" => encryptWithSessionKey($row->id)]);
            //         }
            //         return null;
            //     })
            //     ->attributes(function ($row) {
            //         if ($row->status_code === "ACT") {
            //             return [
            //                 'class' => 'btn btn-primary btn-sm',
            //                 'style' => 'text-decoration: none;',
            //             ];
            //         }
            //         return [];
            //     }),
            // LinkColumn::make('')
            //     ->title(fn ($row) => 'Print Nota')
            //     ->location(fn ($row) => route('purchases_orders.printpdf', ['objectId' => encryptWithSessionKey($row->id)]))
            //     ->attributes(function ($row) {
            //         return [
            //             'class' => 'btn btn-primary btn-sm',
            //             'style' => 'text-decoration: none;',
            //         ];
            //     })
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status_code')
                ->options([
                    Status::ACTIVE => 'Active',
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
