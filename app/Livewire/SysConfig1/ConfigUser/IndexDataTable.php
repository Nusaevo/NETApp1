<?php

namespace App\Livewire\SysConfig1\ConfigUser;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Support\Facades\Crypt;
use Exception;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

use Illuminate\Support\Facades\DB;

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
            Column::make("LoginID", "code")
                ->searchable()
                ->sortable(),
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Email", "email")
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
            TextFilter::make('LoginID', 'loginID')
                ->config([
                    'placeholder' => 'Cari User LoginID',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(loginID)'), 'like', '%' . strtoupper($value) . '%');
                }),
            TextFilter::make('Email', 'email')
                ->config([
                    'placeholder' => 'Cari Email',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(email)'), 'like', '%' . strtoupper($value) . '%');
                }),
            TextFilter::make('Nama', 'name')
                ->config([
                    'placeholder' => 'Cari Nama',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
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
