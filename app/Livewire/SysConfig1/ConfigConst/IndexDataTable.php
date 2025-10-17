<?php

namespace App\Livewire\SysConfig1\ConfigConst;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter};
use App\Models\SysConfig1\{ConfigConst, ConfigAppl};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\{Config, Session, DB};

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigConst::class;
    public $selectedAppId; // Selected application ID
    public $connectionName; // Current database connection name
    public $isSysConfig1; // Boolean to determine if the session app_code is 'SysConfig1'

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchDisabled();

        $this->isSysConfig1 = Session::get('app_code') === 'SysConfig1';
        // Set selectedAppId based on session or default to SysConfig1
        if (!$this->isSysConfig1) {
            $this->selectedAppId = Session::get('app_id');
        } else {
            $this->selectedAppId = 1; // Default application ID for SysConfig1
        }

        $this->updateDatabaseConnection($this->selectedAppId);
    }

    protected $listeners = ['renderConst' => 'render'];

    public function builder(): Builder
    {
        if (!$this->connectionName) {
            throw new \Exception("Connection name is not set. Ensure 'updateDatabaseConnection' is called first.");
        }

        $modelInstance = new ConfigConst();
        $modelInstance->setConnection($this->connectionName);

        return $modelInstance->newQuery()->withTrashed();
    }

    public function columns(): array
    {
        return [
            Column::make('Const Group', 'const_group')->searchable()->sortable(),
            Column::make('Seq', 'seq')->searchable()->sortable(),
            Column::make('Str1', 'str1')->searchable()->sortable(),
            Column::make('Str2', 'str2')->searchable()->sortable(),
            Column::make('Num1', 'num1')->searchable()->sortable()->collapseOnTablet(),
            Column::make('Num2', 'num2')->searchable()->sortable()->collapseOnTablet(),
            Column::make('Note1', 'note1')->searchable()->sortable()->collapseOnTablet(),
            Column::make('Group', 'group_code')
                ->searchable()
                ->sortable()
                ->collapseOnTablet()
                ->format(function ($value) {
                    return $value ?: '-';
                }),
            Column::make('User', 'user_code')
                ->searchable()
                ->sortable()
                ->collapseOnTablet()
                ->format(function ($value) {
                    return $value ?: '-';
                }),
            BooleanColumn::make($this->trans('Status'), 'deleted_at')->setCallback(function ($value) {
                return $value === null;
            }),
            Column::make('Created Date', 'created_at')->sortable()->collapseOnTablet(),
            Column::make('Actions', 'id')->format(function ($value, $row, Column $column) {
                return view('layout.customs.data-table-action', [
                    'row' => $row,
                    'custom_actions' => [
                        [
                            'label' => 'Edit',
                            'route' => route('SysConfig1.ConfigConst.Detail', [
                                'action' => encryptWithSessionKey('Edit'),
                                'objectId' => encryptWithSessionKey($row->id),
                                'additionalParam' => $this->selectedAppId,
                            ]),
                            'icon' => 'bi bi-pencil',
                        ],
                    ],
                    'enable_this_row' => true,
                    'allow_details' => false,
                    'allow_edit' => false,
                    'allow_disable' => false,
                    'allow_delete' => false,
                    'permissions' => $this->permissions,
                ]);
            }),
        ];
    }

    public function filters(): array
    {
        $filters = [];

        if ($this->isSysConfig1) {
            // Add Config Application Filter only for SysConfig1
            $configApplOptions = ConfigAppl::whereNotNull('db_name')->where('db_name', '!=', '')->orderBy('seq')->pluck('name', 'id')->toArray();
            if (!empty($configApplOptions)) {
                $filters[] = SelectFilter::make('Config Appl', 'app_id')
                    ->options($configApplOptions)
                    ->filter(function (Builder $builder, $value) {
                        $this->selectedAppId = $value;
                        $this->updateDatabaseConnection($this->selectedAppId);
                    });
            }
        }

        // Add Group Filter
        $filters[] = $this->createTextFilter('Group', 'const_group', 'Search Group', function (Builder $builder, string $value) {
            $builder->where(DB::raw('UPPER(const_group)'), 'like', '%' . strtoupper($value) . '%');
        });

        // Add Status Filter
        $filters[] = SelectFilter::make('Status', 'status_filter')
            ->options([
                'active' => 'Active',
                'deleted' => 'Non Active',
            ])
            ->filter(function (Builder $builder, string $value) {
                if ($value === 'active') {
                    $builder->whereNull('deleted_at');
                } elseif ($value === 'deleted') {
                    $builder->withTrashed()->whereNotNull('deleted_at');
                }
            });
        return $filters;
    }

    protected function updateDatabaseConnection($appId)
    {
        $configAppl = ConfigAppl::find($appId);

        if ($configAppl && $configAppl->code) {
            // Generate a unique connection name
            $newConnectionName = "{$configAppl->code}";

            // If the connection name has not changed, skip rendering
            if ($this->connectionName === $newConnectionName) {
                return; // Exit early if no change
            }

            // Update the connection name for this instance
            $this->connectionName = $newConnectionName;

            // Dispatch the refresh event only if the connection name changed
            $this->dispatch('renderConst');
        }
    }
}
