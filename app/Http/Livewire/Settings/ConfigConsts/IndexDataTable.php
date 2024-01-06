<?php

namespace App\Http\Livewire\Settings\ConfigConsts;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Settings\ConfigConst;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
class IndexDataTable extends DataTableComponent
{
    protected $model = ConfigConst::class;

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
    }

    public function builder(): Builder
    {
        return ConfigConst::query()
            ->withTrashed()
            ->select();
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
            Column::make("Const Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Application Code", "configAppls.name")
                ->searchable()
                ->sortable(),
            Column::make("Seq", "seq")
                ->searchable()
                ->sortable(),
            Column::make("Str1", "str1")
                ->searchable()
                ->sortable(),
            Column::make("Str2", "str2")
                ->searchable()
                ->sortable(),
           Column::make("Num1", "num1")
                 ->searchable()
                 ->sortable(),
            Column::make("Num2", "num2")
                 ->searchable()
                 ->sortable(),
            Column::make('Status', 'deleted_at')
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return is_null($row->deleted_at) ? 'Active' : 'Non-Active';
                }),
            Column::make('Actions', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.settings.config-consts.index-data-table-action')->withRow($row)
                ),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'Status')
                ->options([
                    '0' => 'Active',
                    '1' => 'Non Active'
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === '0') $builder->withoutTrashed();
                    else if ($value === '1') $builder->onlyTrashed();
                }),
        ];
    }
}
