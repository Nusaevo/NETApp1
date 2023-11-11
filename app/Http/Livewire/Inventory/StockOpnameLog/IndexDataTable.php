<?php

namespace App\Http\Livewire\Inventory\StockOpnameLog;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\StockOpname;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;

class IndexDataTable extends DataTableComponent
{
    protected $model = StockOpname::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
    }
    protected $listeners = [
        'master_stock_opname_log_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Item", "item_warehouse.item_unit.item.name")
                ->searchable()
                ->sortable(),
            Column::make("Satuan", "item_warehouse.item_unit.from_unit.name")
                ->searchable()
                ->sortable(),
            Column::make("Gudang", "item_warehouse.warehouse.name")
                ->searchable()
                ->sortable(),
            Column::make("Qty lama", "old_qty")
                ->format(function ($value) {
                    return qty($value);
                })
                ->sortable(),
            Column::make("Qty baru", "new_qty")
                ->format(function ($value) {
                    return qty($value);
                })
                ->sortable(),
            Column::make("Qty defect lama", "old_qty_defect")
                ->format(function ($value) {
                    return qty($value);
                })
                ->sortable(),
            Column::make("Qty defect baru", "new_qty_defect")
                ->format(function ($value) {
                    return qty($value);
                })
                ->sortable(),
            Column::make("Tanggal diupdate", "created_at")
                ->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
                $builder->where('created_at', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('created_at', '<=', $value);
            }),
        ];
    }
}
