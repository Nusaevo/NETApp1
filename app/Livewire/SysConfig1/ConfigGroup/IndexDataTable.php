<?php

namespace App\Livewire\SysConfig1\ConfigGroup;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigGroup;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;
use Exception;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigGroup::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
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
           Column::make("Application","id")
                ->format(function($value, $row, Column $column) {
                    return optional($row->configAppl)->code . ' - ' . optional($row->configAppl)->name;
                })
                ->searchable()
                ->sortable(),
            Column::make("Group Code", "code")
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
                        'custom_actions' => [],
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => true,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'permissions' => $this->permissions
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
