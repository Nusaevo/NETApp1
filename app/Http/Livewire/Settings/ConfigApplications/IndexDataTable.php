<?php

namespace App\Http\Livewire\Settings\ConfigApplications;

use App\Http\Livewire\Components\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Settings\ConfigAppl;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigAppl::class;

    public function mount(): void
    {
        $this->route = 'ConfigApplications.Detail';
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
    }

    public function builder(): Builder
    {
        return ConfigAppl::query()
            ->withTrashed()
            ->select();
    }

    public function columns(): array
    {
        return [
            Column::make("Application Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Version", "version")
                ->searchable()
                ->sortable(),
            Column::make('Status', 'deleted_at')
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return is_null($row->deleted_at) ? 'Active' : 'Non-Active';
                }),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'enable_this_row' => true,
                        'allow_details' => true,
                        'allow_edit' => true,
                        'allow_disable' => !$row->trashed(),
                        'allow_delete' => false,
                        'wire_click_show' => "\$emit('viewData', $row->id)",
                        'wire_click_edit' => "\$emit('editData', $row->id)",
                        'wire_click_disable' => "\$emit('selectData', $row->id)",
                        'access' => "ConfigApplications"
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
