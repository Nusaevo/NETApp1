<?php

namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Exception;
use App\Enums\Status;

use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigSnum::class;


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
        return ConfigSnum::query()
            ->withTrashed()
            ->select();
    }

    public function columns(): array
    {
        return [
            Column::make("Application", "id")
                ->format(function ($value, $row, Column $column) {
                    return optional($row->configAppl)->code . ' - ' . optional($row->configAppl)->name;
                })
                ->searchable()
                ->sortable(),
            Column::make("Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Last Count", "last_cnt")
                ->searchable()
                ->sortable(),
            Column::make("Description", "descr")
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
                }),
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
                }),
            TextFilter::make('Kode', 'code')
                ->config([
                    'placeholder' => 'Cari Kode',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
                }),
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
