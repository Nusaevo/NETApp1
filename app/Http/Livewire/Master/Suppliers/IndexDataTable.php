<?php

namespace App\Http\Livewire\Master\Suppliers;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Master\Partner;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Partner::class;

    public function mount(): void
    {
        $this->route = 'Suppliers.Detail';
        $this->setSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return Partner::query()->where('grp', 'SUPP')->withTrashed();
    }

    public function columns(): array
    {
        return [
            Column::make("Customer Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Address", "address")
                ->searchable()
                ->sortable(),
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
                            'enable_this_row' => true,
                            'allow_details' => true,
                            'allow_edit' => true,
                            'allow_disable' => !$row->trashed(),
                            'allow_delete' => false,
                            'wire_click_show' => "\$emit('viewData', $row->id)",
                            'wire_click_edit' => "\$emit('editData', $row->id)",
                            'wire_click_disable' => "\$emit('selectData', $row->id)",
                            'access' => "Suppliers"
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
