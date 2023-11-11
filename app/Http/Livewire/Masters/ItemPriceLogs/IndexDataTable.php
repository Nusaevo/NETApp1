<?php

namespace App\Http\Livewire\Masters\ItemPriceLogs;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ItemPriceLog;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;

class IndexDataTable extends DataTableComponent
{
    protected $model = ItemPriceLog::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
    }
    protected $listeners = [
        'master_item_price_log_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Item", "item_price.itemUnit.item.name")
                ->searchable()
                ->sortable(),
            Column::make("Satuan", "item_price.itemUnit.from_unit.name")
                ->searchable()
                ->sortable(),
            Column::make("Harga lama", "old_price")
                ->format(function ($value) {
                    return rupiah($value);
                })
                ->sortable(),
            Column::make("Harga baru", "new_price")
                ->format(function ($value) {
                    return rupiah($value);
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
