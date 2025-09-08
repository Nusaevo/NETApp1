<?php

namespace App\Livewire\TrdJewel1\Master\Category;

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

        // Force to use TrdJewel1 application for categories
        if (!$this->isSysConfig1) {
            // Get TrdJewel1 application ID from session
            $this->selectedAppId = Session::get('app_id');
        } else {
            // If SysConfig1, find TrdJewel1 application ID
            $jewel1App = ConfigAppl::where('code', 'TrdJewel1')->first();
            $this->selectedAppId = $jewel1App ? $jewel1App->id : 1;
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

        // Filter only for jewel1 categories
        return $modelInstance->newQuery()
            ->withTrashed()
            ->whereIn('const_group', ['MMATL_CATEGL1', 'MMATL_CATEGL2']);
    }

    public function columns(): array
    {
        return [
            //Column::make('Const Group', 'const_group')->searchable()->sortable(),
            Column::make('Urutan', 'seq')->searchable()->sortable(),
            Column::make('Value', 'str1')->searchable()->sortable(),
            Column::make('Label', 'str2')->searchable()->sortable(),
            // Column::make('Num1', 'num1')->searchable()->sortable()->collapseOnTablet(),
            // Column::make('Num2', 'num2')->searchable()->sortable()->collapseOnTablet(),
            Column::make('Catatan', 'note1')->searchable()->sortable()->collapseOnTablet(),
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
                            'route' => route('TrdJewel1.Master.Category.Detail', [
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

        // Add specific Group Filter for jewel categories only
        $filters[] = SelectFilter::make('Category Group', 'const_group')
            ->options([
                'MMATL_CATEGL1' => 'Material Category 1',
                'MMATL_CATEGL2' => 'Material Category 2'
            ])
            ->filter(function (Builder $builder, string $value) {
                $builder->where('const_group', $value);
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
