<?php

namespace App\Http\Livewire\Transactions\Transfers;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TransferItem;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class ShowDataTable extends DataTableComponent
{
    protected $model = TransferItem::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    protected $listeners = [
        'transaction_transfer_show_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Nama Item", "item.name")
                ->sortable(),
            Column::make("Nama Unit", "unit.name")
                ->sortable(),
            Column::make("Qty", "qty")
                ->sortable(),
            Column::make("Qty Defect", "qty_defect")
                ->sortable(),
            Column::make("Remark", "remark")
                    ->sortable(),
            Column::make('Aksi','id')
                ->format(
                    fn($value, $row, Column $column) => view('livewire.transactions.transfers.show-data-table-action')->withRow($row)
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
