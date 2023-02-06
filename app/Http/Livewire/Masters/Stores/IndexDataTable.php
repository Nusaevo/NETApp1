<?php

namespace App\Http\Livewire\Masters\Stores;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class IndexDataTable extends DataTableComponent
{
    protected $model = Store::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    protected $listeners = [
        'master_store_idt_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Nama", "name")
                ->searchable()
                ->sortable(),
            Column::make('Aksi', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.masters.stores.index-data-table-action')->withRow($row)
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
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === '0') $builder->withoutTrashed();
                    else if ($value === '1') $builder->onlyTrashed()->select('*');
                    else if ($value === '2') $builder->withTrashed()->select('*');
                }),
        ];
    }
}
