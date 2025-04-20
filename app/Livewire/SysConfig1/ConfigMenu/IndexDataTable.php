<?php

namespace App\Livewire\SysConfig1\ConfigMenu;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter, Filters\TextFilter};
use App\Models\SysConfig1\ConfigMenu;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigMenu::class;

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchDisabled();
    }

    public function builder(): Builder
    {
        return ConfigMenu::query()
            ->withTrashed()
            ->select();
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("Application"), "id")
                ->format(function ($value, $row) {
                    return $this->formatApplicationLink($row);
                })
                ->html()
                ->sortable(),
            Column::make($this->trans("Menu Code"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Menu Header"), "menu_header")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Menu Caption"), "menu_caption")
                ->searchable()
                ->sortable(),
            BooleanColumn::make($this->trans("Status"), "deleted_at")
                ->setCallback(function ($value) {
                    return $value === null;
                }),
            Column::make($this->trans('Created Date'), 'created_at')
                ->sortable()->collapseOnTablet(),
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

    protected function formatApplicationLink($row)
    {
        if ($row->app_id) {
            return '<a href="' . route('SysConfig1.ConfigApplication.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($row->app_id)
            ]) . '">' . optional($row->configAppl)->code . ' - ' . optional($row->configAppl)->name . '</a>';
        }
        return '';
    }

    public function filters(): array
    {
        return [
            $this->createTextFilter('Aplikasi', 'application', 'Cari Kode/Nama Aplikasi', function (Builder $builder, string $value) {
                $builder->whereHas('configAppl', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%')
                          ->orWhere(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            $this->createTextFilter('Kode', 'code', 'Cari Kode', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Header', 'menu_header', 'Cari Menu Header', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(menu_header)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Caption', 'menu_caption', 'Cari Menu Caption', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(menu_caption)'), 'like', '%' . strtoupper($value) . '%');
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
            })
        ];
    }
}
