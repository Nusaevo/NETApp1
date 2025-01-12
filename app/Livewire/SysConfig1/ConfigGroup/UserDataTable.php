<?php

namespace App\Livewire\SysConfig1\ConfigGroup;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Filters\SelectFilter, Columns\LinkColumn};
use Illuminate\Database\Eloquent\Builder;
use App\Models\SysConfig1\{ConfigGroup, ConfigUser, ConfigRight};
use Illuminate\Support\Facades\Crypt;
use Exception;

class UserDataTable extends BaseDataTableComponent
{
    protected $model = ConfigUser::class;
    public $selectedRows = [];
    public $groupId;

    public function mount($groupId = null, $selectedUserIds = null): void
    {
        $this->customRoute = "SysConfig1.ConfigUser";
        $this->isComponent = true;
        $this->groupId = $groupId;
        $this->selectedRows = $selectedUserIds;
    }

    protected $listeners = [ 'renderUserTable' => 'render'];

    public function builder(): Builder
    {
        return ConfigUser::query()
            ->orderBy('code')
            ->orderBy('name');
    }

    public function performUpdateActions()
    {
        $this->dispatch('selectedUserIds', $this->selectedRows);
    }

    public function updatedSelectedRows()
    {
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
            Column::make("User LoginID", "code")
                ->searchable()
                ->sortable(),
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Email", "email")
                ->searchable()
                ->sortable(),
            // Column::make('Actions', 'id')
            //     ->format(function ($value, $row, Column $column) {
            //         return view('layout.customs.data-table-action', [
            //            'row' => $row,
            //             'custom_actions' => [],
            //             'enable_this_row' => true,
            //             'allow_details' => false,
            //             'allow_edit' => true,
            //             'allow_disable' => false,
            //             'allow_delete' => false,
            //             'permissions' => $this->permissions
            //         ]);
            //     }),
        ];
    }
}
