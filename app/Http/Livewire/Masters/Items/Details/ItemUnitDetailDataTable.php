<?php

namespace App\Http\Livewire\Masters\Items\Details;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ItemUnit;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use DB;
class ItemUnitDetailDataTable extends DataTableComponent
{
    protected $model = ItemUnit::class;
   // public Item $item;
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }
    protected $listeners = [
        'master_item_unit_detail_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("From", "from_unit.name")
                ->sortable(),
            Column::make("Multiplier", "multiplier")
                ->sortable(),
            Column::make("To", "to_unit.name")
                ->sortable(),
            Column::make('Aksi','id')
                ->format(
                    fn($value, $row, Column $column) => view('livewire.masters.items.details.itemunitdetail-data-table-action')->withRow($row)
                ),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Tampilkan Terhapus')
                ->options([
                    '0' => 'Tidak',
                    '1' => 'Saja',
                    '2' => 'Semua',
                ])->filter(function(Builder $builder, string $value) {
                    if ($value === '0') $builder->withoutTrashed();
                    else if ($value === '1') $builder->onlyTrashed()->select('*');
                    else if ($value === '2') $builder->withTrashed()->select('*');
                }),
        ];
    }

}
