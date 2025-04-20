<?php

namespace App\Livewire\TrdRetail1\Master\Partner;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter, Filters\TextFilter};
use App\Models\TrdRetail1\Master\Partner;
use App\Services\SysConfig1\ConfigService;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Partner::class;

    public function mount(): void
    {
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
            Column::make($this->trans('code'), 'code')
                ->format(function ($value, $row) {
                    return '<a href="' .
                        route($this->appCode . '.Master.Partner.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id),
                        ]) .
                        '">' .
                        $row->code .
                        '</a>';
                })
                ->html(),
            Column::make($this->trans('group'), 'grp')
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    $configService = new ConfigService();
                    return $configService->getConstValueByStr1('PARTNERS_TYPE', $value) ?? '';
                }),
            Column::make($this->trans('name'), 'name')->searchable()->sortable(),
            Column::make($this->trans('address'), 'address')->searchable()->sortable(),
            Column::make($this->trans('phone'), 'phone')->searchable()->sortable(),
            Column::make($this->trans('email'), 'email')->searchable()->sortable(),

            BooleanColumn::make($this->trans('Status'), 'deleted_at')->setCallback(function ($value) {
                return $value === null;
            }),
            Column::make($this->trans('created_date'), 'created_at')->sortable(),
            Column::make($this->trans('actions'), 'id')->format(function ($value, $row, Column $column) {
                return view('layout.customs.data-table-action', [
                    'row' => $row,
                    'custom_actions' => [],
                    'enable_this_row' => true,
                    'allow_details' => false,
                    'allow_edit' => true,
                    'allow_disable' => false,
                    'allow_delete' => false,
                    'permissions' => $this->permissions,
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
                    'C' => 'Pelanggan',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('grp', $value);
                }),
            SelectFilter::make('Stock', 'stock_filter')
                ->options([
                    'all' => 'All',
                    'above_0' => 'Available',
                    'below_0' => 'Out of Stock',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'above_0') {
                        $builder->whereHas('IvtBal', function ($query) {
                            $query->where('qty_oh', '>', 0);
                        });
                    } elseif ($value === 'below_0') {
                        $builder->where(function ($query) {
                            $query->whereDoesntHave('IvtBal')->orWhereHas('IvtBal', function ($query) {
                                $query->where('qty_oh', '<=', 0);
                            });
                        });
                    }
                }),
            SelectFilter::make('Status', 'status_filter')
                ->options([
                    'active' => 'Active',
                    'deleted' => 'Non Active',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'active') {
                        $builder->whereNull('deleted_at');
                    } elseif ($value === 'deleted') {
                        $builder->withTrashed()->whereNotNull('deleted_at');
                    }
                }),
        ];
    }
}
