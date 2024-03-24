<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\Sales\WarehouseOrders;

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
        $this->setSort('created_at', 'desc');
        $this->setFilter('is_finished', '1');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable()
                ->searchable(),
            Column::make("Tgl Order Gudang", "wo_date")
                ->sortable(),
            Column::make("Pelanggan", "customer_name")
                ->sortable()
                ->searchable(),
            BooleanColumn::make('Status', 'is_finished')
                ->sortable(),
            Column::make('Aksi', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.transaction.sales.warehouseorders.index-data-table-action')->withValue($row)
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
                $builder->where('sales_orders.wo_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('sales_orders.wo_date', '<=', $value);
            }),
        ];
    }
}
