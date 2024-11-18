<?php

namespace App\Livewire\SysConfig1\ConfigSnum;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\SysConfig1\ConfigAppl;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigSnum::class;
    public $selectedAppId; // Selected application ID
    public $connectionName; // Current database connection name
    public $isSysConfig1; // Boolean to determine if the session app_code is 'SysConfig1'

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
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

    protected $listeners = ['renderSnum' => 'render'];

    public function builder(): Builder
    {
        if (!$this->connectionName) {
            throw new \Exception("Connection name is not set. Ensure 'updateDatabaseConnection' is called first.");
        }

        $modelInstance = new ConfigSnum();
        $modelInstance->setConnection($this->connectionName);

        return $modelInstance->newQuery()->withTrashed();
    }

    public function columns(): array
    {
        return [
            Column::make("Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Last Count", "last_cnt")
                ->searchable()
                ->sortable(),
            Column::make("Description", "descr")
                ->searchable()
                ->sortable(),
            Column::make('Created Date', 'created_at')
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(function ($value, $row) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'custom_actions' => [
                            [
                                'label' => 'Edit',
                                'route' => route('SysConfig1.ConfigSnum.Detail', [
                                    'action' => encryptWithSessionKey('Edit'),
                                    'objectId' => encryptWithSessionKey($row->id),
                                    'additionalParam' => $this->selectedAppId
                                ]),
                                'icon' => 'bi bi-pencil'
                            ],
                        ],
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => false,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'permissions' => $this->permissions
                    ]);
                }),
        ];
    }

    public function filters(): array
    {
        $filters = [];

        if ($this->isSysConfig1) {
            // Add Config Application Filter only for SysConfig1
            $configApplOptions = ConfigAppl::orderBy('seq')->pluck('name', 'id')->toArray();
            if (!empty($configApplOptions)) {
                $filters[] = SelectFilter::make('Config Appl', 'app_id')
                    ->options($configApplOptions)
                    ->filter(function (Builder $builder, $value) {
                        $this->selectedAppId = $value;
                        $this->updateDatabaseConnection($this->selectedAppId);
                    });
            }
        }

        // Add Code Filter
        $filters[] = $this->createTextFilter(
            'Code',
            'code',
            'Search Code',
            function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }
        );

        // Add Status Filter
        $filters[] = SelectFilter::make('Status', 'status')
            ->options([
                '0' => 'Active',
                '1' => 'Non Active'
            ])
            ->filter(function (Builder $builder, string $value) {
                if ($value === '0') {
                    $builder->withoutTrashed();
                } elseif ($value === '1') {
                    $builder->onlyTrashed();
                }
            });

        return $filters;
    }

    protected function updateDatabaseConnection($appId)
    {
        $configAppl = ConfigAppl::find($appId);

        if ($configAppl && $configAppl->db_name) {
            // Generate a unique connection name
            $newConnectionName = "{$configAppl->db_name}";

            // If the connection name has not changed, skip rendering
            if ($this->connectionName === $newConnectionName) {
                return; // Exit early if no change
            }

            // Check if the connection is already configured
            if (!Config::has("database.connections.{$newConnectionName}")) {
                $baseConnection = Config::get("database.connections.main");

                // Set the new connection dynamically
                Config::set("database.connections.{$newConnectionName}", array_merge($baseConnection, [
                    'database' => $configAppl->db_name,
                ]));
            }

            // Update the connection name for this instance
            $this->connectionName = $newConnectionName;

            // Dispatch the refresh event only if the connection name changed
            $this->dispatch('renderSnum');
        }
    }
}
