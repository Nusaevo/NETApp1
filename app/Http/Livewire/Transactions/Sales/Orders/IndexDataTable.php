<?php

namespace App\Http\Livewire\Transactions\Sales\Orders;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SalesOrder;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;

class IndexDataTable extends DataTableComponent
{
    protected $model = SalesOrder::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function mount(): void
    {
        //$this->setFilter('is_finished', '0');
        $this->setSort('id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable()
                ->searchable(),
            Column::make("Tgl Transaksi", "transaction_date")
                ->sortable(),
            Column::make("Total", "total_amount")
                ->format(function ($value) {
                    return rupiah($value);
                })
                ->sortable(),
            Column::make("Pelanggan", "customer_name")
                ->sortable()
                ->searchable(),
            BooleanColumn::make('Status', 'is_finished')
                ->sortable(),
            Column::make("Pembayaran", "payment.name")
                ->sortable(),
            Column::make('Aksi', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.transactions.sales.orders.index-data-table-action')->withValue($row)
                )
        ];
    }
    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'is_finished')
                ->options([
                    '0' => 'In Progress',
                    '1' => 'Selesai',
                    '' => 'Semua',
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === '0') $builder->IsFinished(0);
                    else if ($value === '1') $builder->IsFinished(1);
                    else if ($value === '2') $builder->withoutTrashed();
                }),
            DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
                $builder->where('sales_orders.transaction_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('sales_orders.transaction_date', '<=', $value);
            }),
        ];
    }
}
