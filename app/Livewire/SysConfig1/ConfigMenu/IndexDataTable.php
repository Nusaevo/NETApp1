<?php
namespace App\Livewire\SysConfig1\ConfigMenu;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigMenu;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigMenu::class;

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
            $this->createTextFilter('Header', 'menu_header', 'Cari Menu Header', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(menu_header)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Caption', 'menu_caption', 'Cari Menu Caption', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(menu_caption)'), 'like', '%' . strtoupper($value) . '%');
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
