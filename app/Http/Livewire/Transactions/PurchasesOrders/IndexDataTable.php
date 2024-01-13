<?php

namespace App\Http\Livewire\Transactions\PurchasesOrders;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\OrderHdr;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
class IndexDataTable extends DataTableComponent
{
    protected $model = OrderHdr::class;

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
    }

     public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setTableAttributes([
            'class' => 'data-table',
        ]);

        $this->setTheadAttributes([
            'class' => 'data-table-header',
        ]);

        $this->setTbodyAttributes([
            'class' => 'data-table-body',
        ]);
        $this->setTdAttributes(function(Column $column, $row, $columnIndex, $rowIndex) {
            if ($column->isField('deleted_at')) {
              return [
                'class' => 'text-center',
              ];
            }
            return [];
        });
    }

    protected $listeners = [
        'refreshData' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable()
                ->searchable(),
            Column::make("Tanggal", "tr_date")
                ->searchable()
                ->sortable(),
            Column::make("Supplier", "partners.name")
                ->searchable()
                ->sortable(),
            Column::make("Status", "status_code")
                ->searchable()
                ->sortable(),
            Column::make('Created Date', 'created_at')
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.transactions.purchases-orders.index-data-table-action')->withRow($row)
                ),
            Column::make('', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.transactions.purchases-orders.order-action')->withRow($row)
                ),
        ];
    }

    public function filters(): array
    {
        return [
            // SelectFilter::make('Status', 'Status')
            //     ->options([
            //         '0' => 'Active',
            //         '1' => 'Non Active'
            //     ])->filter(function (Builder $builder, string $value) {
            //         if ($value === '0') $builder->withoutTrashed();
            //         else if ($value === '1') $builder->onlyTrashed();
            //     }),
        ];
    }
}
