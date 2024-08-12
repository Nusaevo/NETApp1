<?php

namespace App\Livewire\SysConfig1\ConfigConst;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Models\SysConfig1\ConfigRight;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigConst::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchVisibilityStatus(false);
    }

    public function builder(): Builder
    {
        return ConfigConst::query()
            ->withTrashed()
            ->select();
    }

    public function columns(): array
    {
        return [
            Column::make("Application", "id")
            ->format(function ($value, $row) {
                if ($row->app_id) {
                    return '<a href="' . route('SysConfig1.ConfigApplication.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($row->app_id)
                    ]) . '">' . optional($row->configAppl)->code . ' - ' . optional($row->configAppl)->name . '</a>';
                } else {
                    return '';
                }
            })
            ->html()
            ->sortable(),
            Column::make("Const Group", "const_group")
                ->searchable()
                ->sortable(),
            Column::make("Seq", "seq")
                ->searchable()
                ->sortable(),
            Column::make("Str1", "str1")
                ->searchable()
                ->sortable(),
            Column::make("Str2", "str2")
                ->searchable()
                ->sortable(),
            Column::make("Num1", "num1")
                ->searchable()
                ->sortable(),
            Column::make("Num2", "num2")
                ->searchable()
                ->sortable(),
            Column::make("Note1", "note1")
                ->searchable()
                ->sortable(),
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
            TextFilter::make('Kode Aplikasi', 'appl_code')
                ->config([
                    'placeholder' => 'Cari Kode Aplikasi',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = strtoupper($value);
                    $builder->whereHas('configAppl', function ($query) use ($value) {
                        $query->where(DB::raw('UPPER(code)'), 'like', '%' . $value . '%');
                    });
                })->setWireLive(),
            TextFilter::make('Nama Aplikasi', 'appl_name')
                ->config([
                    'placeholder' => 'Cari Nama Aplikasi',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = strtoupper($value);
                    $builder->whereHas('configAppl', function ($query) use ($value) {
                        $query->where(DB::raw('UPPER(name)'), 'like', '%' . $value . '%');
                    });
                })->setWireLive(),
            TextFilter::make('Group', 'const_group')
                ->config([
                    'placeholder' => 'Cari Group',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('const_group', 'like', '%' . $value . '%');
                })->setWireLive(),
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
