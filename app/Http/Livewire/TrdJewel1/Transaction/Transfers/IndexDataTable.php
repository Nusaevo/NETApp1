<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\Transfers;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class IndexDataTable extends DataTableComponent
{
    protected $model = Transfer::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    protected $listeners = [
        'transaction_transfer_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Tanggal Transfer", "transfer_date")
                ->sortable(),
            Column::make("Asal Transfer", "origin_warehouse.name")
                ->sortable(),
            Column::make("Tujuan Transfer", "destination_warehouse.name")
                ->sortable(),
            Column::make('Aksi','id')
                ->format(
                    fn($value, $row, Column $column) => view('livewire.transaction.transfers.index-data-table-action')->withRow($row)
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
