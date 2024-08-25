<?php
namespace App\Livewire\SysConfig1\ConfigUser;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SysConfig1\ConfigUser;
use Illuminate\Support\Facades\DB;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigUser::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchVisibilityStatus(false);
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("LoginID"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Name"), "name")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Email"), "email")
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

    public function filters(): array
    {
        return [
            $this->createTextFilter('LoginID', 'code', 'Cari User LoginID', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Email', 'email', 'Cari Email', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(email)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Nama', 'name', 'Cari Nama User', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
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
