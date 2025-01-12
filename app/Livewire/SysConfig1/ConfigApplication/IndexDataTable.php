<?php

namespace App\Livewire\SysConfig1\ConfigApplication;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter, Filters\TextFilter};
use App\Models\SysConfig1\ConfigAppl;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;


class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigAppl::class;

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchDisabled();
    }
    // public $column = [
    //     'name' => '',
    //     'code' => '',
    // ];

    public function columns(): array
    {
        return [
            Column::make($this->trans('Application Code'), 'code')
                ->searchable()
                ->sortable()
                // ->secondaryHeader(function () {
                //     return view('tables.cells.input-search', [
                //         'field' => 'code',
                //         'columnSearch' => $this->column,
                //     ]);
                // })
                ->html(),
            Column::make($this->trans('Name'), 'name')
                ->searchable()
                ->sortable()
                // ->secondaryHeader(function () {
                //     return view('tables.cells.input-search', [
                //         'field' => 'name',
                //         'column' => $this->column,
                //     ]);
                // })
                ->html(),
            Column::make('Seq', 'seq')->searchable()->sortable(),
            Column::make($this->trans('Latest Version'), 'latest_version')->searchable()->sortable(),
            BooleanColumn::make($this->trans('Status'), 'deleted_at')->setCallback(function ($value) {
                return $value === null;
            }),
            Column::make($this->trans('Created Date'), 'created_at')->sortable(),
            Column::make($this->trans('Actions'), 'id')->format(function ($value, $row) {
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
            $this->createTextFilter('Kode', 'code', 'Cari Kode Aplikasi', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Nama', 'name', 'Cari Nama Aplikasi', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
            }),
            SelectFilter::make('Status')
                ->setFilterPillTitle('Status')
                ->setFilterPillValues([
                    'active' => 'Active',
                    'deleted' => 'Non Active',
                ])
                ->options([
                    'active' => 'Active',
                    'deleted' => 'Non Active',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'active') {
                        $builder->whereNull('deleted_at');
                    } elseif ($value === 'deleted') {
                        $builder->whereNotNull('deleted_at');
                    }
                }),
        ];
    }
}
