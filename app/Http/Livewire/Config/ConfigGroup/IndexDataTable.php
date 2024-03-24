<?php

namespace App\Http\Livewire\Config\ConfigGroup;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Config\ConfigGroup;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;
use Lang;
use Exception;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigGroup::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
    }

    public function builder(): Builder
    {
        return ConfigGroup::query()
            ->withTrashed()
            ->select();
    }

    public function columns(): array
    {
        return [
            Column::make("Group Code", "code")
                ->searchable()
                ->sortable(),
           Column::make("Application","id")
                ->format(function($value, $row, Column $column) {
                    return optional($row->configAppl)->code . ' - ' . optional($row->configAppl)->name;
                })
                ->searchable()
                ->sortable(),
            Column::make("Group Name", "descr")
                ->searchable()
                ->sortable(),
            // Column::make("User LoginID", "ConfigUser.code")
            //         ->searchable()
            //         ->sortable(),
            Column::make("Status", "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            Column::make('Created Date', 'created_at')
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => true,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'access' => $this->customRoute ? $this->customRoute : $this->baseRoute
                    ]);
                }),
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
