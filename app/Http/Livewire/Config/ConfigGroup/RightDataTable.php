<?php

namespace App\Http\Livewire\Config\ConfigGroup;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Config\ConfigMenu;

class RightDataTable extends BaseDataTableComponent
{
    protected $model = ConfigMenu::class;
    public int $perPage = 50;
    public $selectedRows = [];
    public $enableActionCheckboxes = false;
    public $groupId;
    public $appId;

    public function mount($groupId = null, $appId = null, $selectedMenus = null): void
    {
        $this->groupId = $groupId;
        $this->appId = $appId;
        $this->selectedRows = $selectedMenus ?: [];
    }

    public function builder(): Builder
    {
        return ConfigMenu::query()
            ->where('app_id', $this->appId)
            ->orderBy('menu_header');
    }

    public function applicationChanged($appId, $selectedMenus)
    {
        $this->appId = $appId;
        $this->selectedRows = $selectedMenus ?: [];
    }

    protected $listeners = [
        'applicationChanged' => 'applicationChanged',
    ];

    public function performUpdateActions()
    {
        $this->emit('selectedMenus', $this->selectedRows);
    }

    public function updatedSelectedRows($value, $key)
    {
        list($rowId, $field) = explode('.', $key);
        $rowId = (int) $rowId;

        // Ensuring the row exists in the array when updated.
        if (!isset($this->selectedRows[$rowId])) {
            $this->selectedRows[$rowId] = ['menu_seq' => 1]; // Default sequence to 1
        }

        if ($field === 'selected') {
            if (!$value) {
                // Unsetting the row only if 'selected' is explicitly set to false.
                unset($this->selectedRows[$rowId]);
            } else {
                // Marking as selected and defaulting the values if not already set.
                $this->selectedRows[$rowId]['selected'] = true;
                $this->selectedRows[$rowId] += [
                    'menu_seq' => $this->selectedRows[$rowId]['menu_seq'] ?? 1,
                    'create' => $this->selectedRows[$rowId]['create'] ?? true,
                    'read' => $this->selectedRows[$rowId]['read'] ?? true,
                    'update' => $this->selectedRows[$rowId]['update'] ?? true,
                    'delete' => $this->selectedRows[$rowId]['delete'] ?? true,
                ];
            }
        } else {
            // Updating the specific field for the row.
            $this->selectedRows[$rowId][$field] = $value;
        }

        // Emitting the update after making changes.
        $this->performUpdateActions();
    }

    public function columns(): array
    {
        return [
            Column::make("", "id")
                ->format(function ($value, $row) {
                    return "<input class='form-check-input' type='checkbox' wire:model='selectedRows.{$row->id}.selected'>";
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
            Column::make("Sequence", "id")
                ->format(function ($value, $row) {
                    $disabled = isset($this->selectedRows[$row->id]['selected']) && $this->selectedRows[$row->id]['selected'] ? '' : 'disabled';
                    $sequenceValue = $this->selectedRows[$row->id]['menu_seq'] ?? 1;
                    return "<input class='form-control' type='number' wire:model='selectedRows.{$row->id}.menu_seq' value='{$sequenceValue}' {$disabled}>";
                })
                ->html(),
            // Adjusting the remaining columns to disable inputs if not selected
            $this->actionColumn('Create', 'create'),
            $this->actionColumn('Read', 'read'),
            $this->actionColumn('Update', 'update'),
            $this->actionColumn('Delete', 'delete'),
        ];
    }

    // Helper function to create action columns with disabled logic
    protected function actionColumn($label, $actionField)
    {
        return Column::make($label, "id")
            ->format(function ($value, $row) use ($actionField) {
                $isChecked = isset($this->selectedRows[$row->id][$actionField]) ? 'checked' : '';
                $disabled = isset($this->selectedRows[$row->id]['selected']) && $this->selectedRows[$row->id]['selected'] ? '' : 'disabled';
                return "<input class='form-check-input' type='checkbox' wire:model='selectedRows.{$row->id}.{$actionField}' $isChecked $disabled>";
            })
            ->html();
    }

}
