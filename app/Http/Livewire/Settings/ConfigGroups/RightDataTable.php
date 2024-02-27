<?php

namespace App\Http\Livewire\Settings\ConfigGroups;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\ConfigMenu;
use App\Models\Settings\ConfigRight;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;

class RightDataTable extends DataTableComponent
{
    protected $model = ConfigMenu::class;
    public int $perPage = 50;
    public $selectedRows = [];
    public $enableActionCheckboxes = false;
    public $groupId;
    public $appId;

    public function mount($groupId = null, $appId = null): void // Adjust the mount method
    {
        $this->groupId = $groupId;
        $this->appId = $appId;
        $this->populateConfigRights();
    }

    public function builder(): Builder
    {
        return ConfigMenu::query()
            ->where('app_id', $this->appId)
            ->orderBy('menu_header')
            ->orderBy('seq')
            ->select();
    }

    public function applicationChanged($appId)
    {
        $this->appId = $appId;
        $this->selectedRows = [];
        $this->render();
        $this->populateConfigRights();
    }


    public function populateConfigRights()
    {
        if (!is_null($this->groupId)) {
            $configRights = ConfigRight::where('group_id', $this->groupId)->get();

            foreach ($configRights as $configRight) {
                $this->selectedRows[$configRight->menu_id] = [
                    'selected' => true,
                    'create' => strpos($configRight->trustee, 'C') !== false,
                    'read' => strpos($configRight->trustee, 'R') !== false,
                    'update' => strpos($configRight->trustee, 'U') !== false,
                    'delete' => strpos($configRight->trustee, 'D') !== false,
                ];
            }
        }
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setTableAttributes([
            'class' => 'data-table',
        ]);

        $this->setTheadAttributes([
            'class' => 'data-table-header',
        ]);

        $this->setTbodyAttributes([
            'class' => 'data-table-body',
        ]);
        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            if ($column->isField('deleted_at')) {
                return [
                    'class' => 'text-center',
                ];
            }
            return [];
        });
    }

    protected $listeners = [
        'refreshData' => 'render',
        'viewData'  => 'View',
        'editData'  => 'Edit',
        'deleteData'  => 'Delete',
        'disableData'  => 'Disable',
        'selectData'  => 'SelectObject',
        'applicationChanged'  => 'applicationChanged',
    ];

    public function performUpdateActions()
    {
        $this->emit('selectedMenus', $this->selectedRows);
    }

    public function updatedSelectedRows($value, $key)
    {
        list($rowId, $field) = explode('.', $key);

        // Ensure $rowId is treated as an integer if your IDs are numerical
        $rowId = (int) $rowId;

        // Case when the first checkbox (selected) is toggled
        if ($field == 'selected') {
            $isSelected = $value;

            // If unselected, disable the other checkboxes and remove from array
            if (!$isSelected) {
                // You could either remove the row from the array
                unset($this->selectedRows[$rowId]);
                // Or mark it as unselected and disable other actions
                // $this->selectedRows[$rowId] = [
                //     'selected' => false,
                //     'create' => false,
                //     'read' => false,
                //     'update' => false,
                //     'delete' => false,
                // ];
            } else {
                // If selected, just mark as selected without enabling actions by default
                $this->selectedRows[$rowId]['selected'] = true;
                // Optionally initialize other actions to false if not already set
                $this->selectedRows[$rowId] += [
                    'create' => true,
                    'read' => true,
                    'update' => true,
                    'delete' => true,
                ];
            }
        } else {
            // For other fields (create, read, update, delete), update their specific value
            // Ensure the row is already marked as selected before updating these fields
            if(isset($this->selectedRows[$rowId]) && $this->selectedRows[$rowId]['selected']) {
                $this->selectedRows[$rowId][$field] = $value;
            }
        }

        $this->performUpdateActions();
    }



    public function columns(): array
    {
        return [
            Column::make("", "id")
                ->format(function ($value, $row, Column $column) {
                    return "<input class='form-check-input' type='checkbox' wire:model.lazy='selectedRows." . $row->id . ".selected'>";
                })
                ->html(),
            Column::make("Application Code", "ConfigAppl.name")
                ->searchable()
                ->sortable(),
            Column::make("Menu Header", "menu_header")
                ->searchable()
                ->sortable(),
            Column::make("Menu Caption", "menu_caption")
                ->searchable()
                ->sortable(),
            Column::make("Create", "id")
                ->format(function ($value, $row, Column $column) {
                    $isChecked = isset($this->selectedRows[$row->id]['create']) ? 'checked' : '';
                    $disabled = isset($this->selectedRows[$row->id]) ? '' : 'disabled';

                    return "<input class='form-check-input' type='checkbox' wire:model='selectedRows.{$row->id}.create' $isChecked $disabled>";
                })
                ->html(),
            Column::make("Read", "id")
                ->format(function ($value, $row, Column $column) {
                    $isChecked = isset($this->selectedRows[$row->id]['read']) ? 'checked' : '';
                    $disabled = isset($this->selectedRows[$row->id]) ? '' : 'disabled';

                    return "<input class='form-check-input' type='checkbox' wire:model='selectedRows.{$row->id}.read' $isChecked $disabled>";
                })
                ->html(),
            Column::make("Update", "id")
                ->format(function ($value, $row, Column $column) {
                    $isChecked = isset($this->selectedRows[$row->id]['update']) ? 'checked' : '';
                    $disabled = isset($this->selectedRows[$row->id]) ? '' : 'disabled';

                    return "<input class='form-check-input' type='checkbox' wire:model='selectedRows.{$row->id}.update' $isChecked $disabled>";
                })
                ->html(),
            Column::make("Delete", "id")
                ->format(function ($value, $row, Column $column) {
                    $isChecked = isset($this->selectedRows[$row->id]['delete']) ? 'checked' : '';
                    $disabled = isset($this->selectedRows[$row->id]) ? '' : 'disabled';

                    return "<input class='form-check-input' type='checkbox' wire:model='selectedRows.{$row->id}.delete' $isChecked $disabled>";
                })
                ->html(),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'enable_this_row' => true,
                        'allow_details' => true,
                        'allow_edit' => false,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'wire_click_show' => "\$emit('viewData', $row->id)",
                        'wire_click_edit' => "\$emit('editData', $row->id)",
                        'wire_click_disable' => "\$emit('selectData', $row->id)",
                        'access' => "config_menus"
                    ]);
                }),
        ];
    }

    public function View($id)
    {
        return redirect()->route('config_menus.detail', ['action' => encryptWithSessionKey('View'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('config_menus.detail', ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($id)]);
    }
}
