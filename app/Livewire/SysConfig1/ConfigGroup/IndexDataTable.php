<?php
namespace App\Livewire\SysConfig1\ConfigGroup;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\SysConfig1\ConfigGroup;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigGroup::class;

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
        return ConfigGroup::query()
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
            Column::make($this->trans("Group Code"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Group Name"), "descr")
                ->searchable()
                ->sortable(),
           BooleanColumn::make($this->trans("Status"), "status_code")
                ->setCallback(function ($value) {
                    return $value === Status::ACTIVE;
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
            $this->createTextFilter('Kode', 'code', 'Cari Kode Group', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Nama', 'descr', 'Cari Nama Group', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(descr)'), 'like', '%' . strtoupper($value) . '%');
            }),
            SelectFilter::make('Status', 'status_filter')
                ->options([
                    'active' => 'Active',
                    'non_active' => 'Non Active',
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === 'active') {
                        $builder->withoutTrashed()
                                ->where('status_code', Status::ACTIVE);
                    } elseif ($value === 'non_active') {
                        $builder->onlyTrashed()
                                ->where('status_code', '!=', Status::ACTIVE);
                    }
                }),
        ];
    }
}
