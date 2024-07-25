<?php

namespace App\Livewire\SysConfig1\ConfigVar;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigVar;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Support\Facades\Crypt;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Support\Facades\Lang;
use Exception;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigVar::class;

    

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setFilter('Status', 0);
    }

    public function builder(): Builder
    {
        return ConfigVar::query()
            ->withTrashed()
            ->select();
    }

    public function columns(): array
    {
        return [
            Column::make("Application", "ConfigAppl.name")
                ->searchable()
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
