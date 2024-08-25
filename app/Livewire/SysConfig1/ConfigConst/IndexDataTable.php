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
use App\Services\SysConfig1\ConfigService;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigConst::class;
    protected $configService;
    protected $accessible_appids;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchVisibilityStatus(false);
        $this->configService = new ConfigService();
        $this->accessible_appids = $this->configService->getAppIds();
    }

    public function builder(): Builder
    {
        $query = ConfigConst::query()->withTrashed();

        if (!empty($this->accessible_appids)) {
            $query->whereIn('app_id', $this->accessible_appids);
        }

        return $query->select();
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
            $this->createTextFilter('Aplikasi', 'application', 'Cari Kode/Nama Aplikasi', function (Builder $builder, string $value) {
                $builder->whereHas('configAppl', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%')
                          ->orWhere(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            $this->createTextFilter('Group', 'const_group', 'Cari Group', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(const_group)'), 'like', '%' . strtoupper($value) . '%');
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
