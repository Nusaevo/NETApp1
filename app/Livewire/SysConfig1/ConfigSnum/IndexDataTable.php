<?php
namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\DB;
use App\Services\SysConfig1\ConfigService;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigSnum::class;
    protected $configService;
    protected $accessible_appids;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchDisabled();
        $this->configService = new ConfigService();
        $this->accessible_appids = $this->configService->getAppIds();
    }

    public function builder(): Builder
    {
        $query = ConfigSnum::query()->withTrashed();
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
            Column::make($this->trans("Code"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Last Count"), "last_cnt")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Description"), "descr")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Status"), "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return Status::getStatusString($value);
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
            $this->createTextFilter('Kode', 'code', 'Cari Kode', function (Builder $builder, string $value) {
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
