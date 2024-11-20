<?php
namespace App\Livewire\SysConfig1\ConfigGroup;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SysConfig1\ConfigMenu;

class RightDataTable extends BaseDataTableComponent
{
    protected $model = ConfigMenu::class;
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
            ->orderBy('menu_header')
            ->orderBy('menu_caption');
    }

    public function applicationChanged($appId, $selectedMenus)
    {
        // Clear previous data to ensure fresh render
        $this->selectedRows = [];
        $this->appId = $appId;
        $this->selectedRows = $selectedMenus ?: [];
        $this->dispatch('renderRightTable');
    }


    protected $listeners = [
        'applicationChanged' => 'applicationChanged',
        'renderRightTable' => 'render'];


    public function performUpdateActions()
    {
        $this->dispatch('selectedMenus', $this->selectedRows);
    }

    public function updatedSelectedRows($value, $key)
    {
        // Parse the key to get rowId and field
        $parts = explode('.', $key);
        $rowId = (int) $parts[0];
        $field = $parts[1] ?? 'selected';

        if (!isset($this->selectedRows[$rowId])) {
            $this->selectedRows[$rowId] = ['menu_seq' => 1];
        }

        if ($field === 'selected') {
            if (!$value) {
                unset($this->selectedRows[$rowId]);
            } else {
                $this->selectedRows[$rowId]['selected'] = true;
                $this->selectedRows[$rowId] += [
                    'menu_seq' => $this->selectedRows[$rowId]['menu_seq'] ?? 1,
                    'create' => true,
                    'read' => true,
                    'update' => true,
                    'delete' => true,
                ];
            }
        } else {
            $this->selectedRows[$rowId][$field] = $value;
        }

        // Log for debugging
        logger()->info('updatedSelectedRows', [
            'rowId' => $rowId,
            'field' => $field,
            'value' => $value,
            'selectedRows' => $this->selectedRows,
        ]);

        $this->performUpdateActions();
    }


    public function columns(): array
    {
        return [
            Column::make("", "id")
            ->format(function ($value, $row) {
                return "<input class='form-check-input' type='checkbox' wire:model.lazy='selectedRows.{$row->id}.selected' wire:key='row-{$row->id}'>";
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
                    return "<input class='form-control' type='number' wire:model.lazy='selectedRows.{$row->id}.menu_seq' value='{$sequenceValue}' {$disabled}>";
                })
                ->html(),
            $this->actionColumn('Create', 'create'),
            $this->actionColumn('Read', 'read'),
            $this->actionColumn('Update', 'update'),
            $this->actionColumn('Delete', 'delete'),
        ];
    }

    protected function actionColumn($label, $actionField)
    {
        return Column::make($label, "id")
            ->format(function ($value, $row) use ($actionField) {
                $isChecked = isset($this->selectedRows[$row->id][$actionField]) && $this->selectedRows[$row->id][$actionField] ? 'checked' : '';
                $disabled = isset($this->selectedRows[$row->id]['selected']) && $this->selectedRows[$row->id]['selected'] ? '' : 'disabled';
                return "<input class='form-check-input' type='checkbox' wire:model.lazy='selectedRows.{$row->id}.{$actionField}' $isChecked $disabled>";
            })
            ->html();
    }

}
