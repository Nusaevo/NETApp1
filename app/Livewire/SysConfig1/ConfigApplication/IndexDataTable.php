<?php

namespace App\Livewire\SysConfig1\ConfigApplication;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigAppl::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchDisabled();
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
            Column::make($this->trans("Application Code"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Name"), "name")
                ->searchable()
                ->sortable(),
            Column::make("Seq", "seq")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Latest Version"), "latest_version")
                ->searchable()
                ->sortable(),
            BooleanColumn::make($this->trans("Status"), "deleted_at")
                ->setCallback(function ($value) {
                    return $value === null;
                }),
            Column::make($this->trans('Created Date'), 'created_at')
                ->sortable(),
            Column::make($this->trans('Actions'), 'id')
                ->format(function ($value, $row) {
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
            $this->createTextFilter('Kode', 'code', 'Cari Kode Aplikasi', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Nama', 'name', 'Cari Nama Aplikasi', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
            }),
            SelectFilter::make('Status', 'status_filter')
                ->options([
                    'active' => 'Active',
                    'deleted' => 'Non Active',
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === 'active') {
                        $builder->whereNull('deleted_at');
                    } elseif ($value === 'deleted') {
                        $builder->whereNotNull('deleted_at');
                    }
                }),
        ];
    }
}
