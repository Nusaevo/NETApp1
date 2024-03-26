<?php

namespace App\Http\Livewire\TrdJewel1\Master\Partner;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Partner::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->setSort('created_at', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Group", "grp")
                ->searchable()
                ->sortable(),
                Column::make("Group", "grp")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return getConstValueByStr1('PARTNERS_TYPE', $value);
                }),
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
