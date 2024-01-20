<?php

namespace App\Http\Livewire\Settings\ConfigMenus;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Settings\ConfigMenu;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
class IndexDataTable extends DataTableComponent
{
    protected $model = ConfigMenu::class;

    public function mount(): void
    {
        $this->setSort('menu_header', 'asc');
        $this->setSort('seq', 'asc');
        $this->setFilter('Status', 0);
    }

    public function builder(): Builder
    {
        return ConfigMenu::query()
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
            Column::make("Menu Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Application Code", "ConfigAppl.name")
                ->searchable()
                ->sortable(),
            Column::make("Menu Header", "menu_header")
                ->searchable()
                ->sortable(),
            Column::make("Sub Menu", "sub_menu")
                ->searchable()
                ->sortable(),
            Column::make("Menu Seq", "seq")
                    ->searchable()
                    ->sortable(),
            Column::make("Menu Caption", "menu_caption")
                ->searchable()
                ->sortable(),
            Column::make('Status', 'deleted_at')
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return is_null($row->deleted_at) ? 'Active' : 'Non-Active';
                }),
            Column::make('Actions', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.settings.config-menus.index-data-table-action')->withRow($row)
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
