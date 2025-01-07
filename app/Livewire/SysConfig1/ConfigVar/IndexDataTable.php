<?php
namespace App\Livewire\SysConfig1\ConfigVar;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\SysConfig1\ConfigVar;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\DB;
use App\Services\SysConfig1\ConfigService;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigVar::class;
    protected $configService;
    protected $accessible_appids;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setFilter('Status', 0);
        $this->setSearchDisabled();
        $this->configService = new ConfigService();
        $this->accessible_appids = $this->configService->getAppIds();
    }

    public function builder(): Builder
    {
        $query = ConfigVar::query()->withTrashed();
        if (!empty($this->accessible_appids)) {
            $query->whereIn('app_id', $this->accessible_appids);
        }
        return $query->select();
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
            Column::make($this->trans("Var Code"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Var Group"), "var_group")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Seq"), "seq")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Default Value"), "default_value")
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
            $this->createTextFilter('Var Code', 'code', 'Cari Var Code', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Var Group', 'var_group', 'Cari Var Group', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(var_group)'), 'like', '%' . strtoupper($value) . '%');
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
