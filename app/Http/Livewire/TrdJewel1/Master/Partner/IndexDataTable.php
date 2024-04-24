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
            Column::make($this->trans('code'), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans('group'), "grp")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return getConstValueByStr1('PARTNERS_TYPE', $value);
                }),
            Column::make($this->trans('name'), "name")
                ->searchable()
                ->sortable(),
            Column::make($this->trans('address'), "address")
                ->searchable()
                ->sortable(),
            Column::make($this->trans('status'), "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            Column::make($this->trans('created_date'), 'created_at')
                ->sortable(),
            Column::make($this->trans('actions'), 'id')
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
