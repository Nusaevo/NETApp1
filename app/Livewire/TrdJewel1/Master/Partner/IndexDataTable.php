<?php

namespace App\Livewire\TrdJewel1\Master\Partner;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use App\Services\SysConfig1\ConfigService;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Partner::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSearchDisabled();
        $this->setDefaultSort('created_at', 'desc');

    }

    public function builder(): Builder
    {
        return Partner::query();
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
                    $configService = new ConfigService();
                    return  $configService->getConstValueByStr1('PARTNERS_TYPE', $value) ?? '';
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
            $this->createTextFilter('Partner', 'code', 'Cari Kode Partner', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Nama', 'name', 'Cari Nama', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
            }),
            SelectFilter::make('Group', 'grp')
                ->options([
                    '' => 'All', // Opsi untuk semua grup
                    'V' => 'Supplier',
                    'C' => 'Customer'
                ])->filter(function (Builder $builder, string $value) {
                    $builder->where('grp', $value);
                }),
            SelectFilter::make('Status', 'Status')
                ->options([
                    '0' => 'Active',
                    '1' => 'Non Active'
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === '0') {
                        $builder->withoutTrashed();
                    } else if ($value === '1') {
                        $builder->onlyTrashed();
                    }
                }),
        ];
    }
}
