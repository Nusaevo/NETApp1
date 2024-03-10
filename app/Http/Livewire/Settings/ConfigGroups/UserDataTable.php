<?php
namespace App\Http\Livewire\Settings\ConfigGroups;

use App\Http\Livewire\Components\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\ConfigGroup;
use App\Models\Settings\ConfigUser;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
class UserDataTable extends BaseDataTableComponent
{
    protected $model = ConfigUser::class;
    public int $perPage = 50;
    public $selectedRows = [];
    public $groupId;

    public function mount($groupId = null, $selectedUserIds = null): void
    {
        $this->groupId = $groupId;
        $this->selectedRows = $selectedUserIds;
    }

    public function performUpdateActions()
    {
        $this->emit('selectedUserIds', $this->selectedRows);
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
            //             'enable_this_row' => true,
            //             'allow_details' => true,
            //             'allow_edit' => false,
            //             'allow_disable' => false,
            //             'allow_delete' => false,
            //             'wire_click_show' => "\$emit('viewData', $row->id)",
            //             'wire_click_edit' => "\$emit('editData', $row->id)",
            //             'wire_click_disable' => "\$emit('selectData', $row->id)",
            //         ]);
            //     }),
        ];
    }
}
