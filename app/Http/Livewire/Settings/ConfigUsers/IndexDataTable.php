<?php
namespace App\Http\Livewire\Settings\ConfigUsers;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

use Illuminate\Database\Eloquent\Builder;
use App\Models\ConfigUser;
class IndexDataTable extends DataTableComponent
{
    protected $model = User::class;

    public function builder(): Builder
    {
        return ConfigUser::query()
            ->withTrashed()
            ->select();
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setThAttributes(function(Column $column) {
            if ($column->isField('name')) {
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
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Email", "email")
                ->searchable()
                ->sortable(),
            Column::make('Status', 'deleted_at')
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return is_null($row->deleted_at) ? 'Active' : 'Non-Active';
                }),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('livewire.settings.config-users.index-data-table-action')->withRow($row);
                }),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Filter')
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
