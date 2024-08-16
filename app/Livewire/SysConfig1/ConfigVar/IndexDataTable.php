<?php

namespace App\Livewire\SysConfig1\ConfigVar;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigVar;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\DB;
use App\Services\SysConfig1\ConfigService;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigVar::class;
    protected $configService ;
    protected $accessible_appids ;


    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setFilter('Status', 0);
        $this->setSearchVisibilityStatus(false);
        $this->configService = new ConfigService();
        $this->accessible_appids = $this->configService->getAppIds();
    }

    public function builder(): Builder
    {
        return ConfigVar::query()
            ->withTrashed()
            ->whereIn('app_id', $this->accessible_appids)
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
            Column::make("Var Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Var Group", "var_group")
                ->searchable()
                ->sortable(),
            Column::make("Seq", "seq")
                ->searchable()
                ->sortable(),
            Column::make("Default Value", "default_value")
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
            TextFilter::make('Aplikasi', 'application')
                ->config([
                    'placeholder' => 'Cari Kode/Nama Aplikasi',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas('configAppl', function ($query) use ($value) {
                        $query->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%')
                            ->orWhere(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                    });
                })->setWireLive(),
            TextFilter::make('Var Code', 'code')
                ->config([
                    'placeholder' => 'Cari Var code',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
                })->setWireLive(),
            TextFilter::make('Var Group', 'var_group')
                ->config([
                    'placeholder' => 'Cari Var Group',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(var_group)'), 'like', '%' . strtoupper($value) . '%');
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
