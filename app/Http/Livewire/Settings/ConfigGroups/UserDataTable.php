<?php
namespace App\Http\Livewire\Settings\ConfigGroups;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Settings\ConfigGroup;
use App\Models\Settings\ConfigUser;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
class UserDataTable extends DataTableComponent
{
    protected $model = ConfigUser::class;
    public int $perPage = 50;
    public $selectedRows = [];
    public $groupId;
    public $object;


    public function mount($groupId = null, $selectedUserIds = null): void
    {
        $this->groupId = $groupId;
        $this->selectedRows = $selectedUserIds;
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
        $this->setTdAttributes(function(Column $column, $row, $columnIndex, $rowIndex) {
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
    ];


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

    public function View($id)
    {
        return redirect()->route('config_users.detail', ['action' => encryptWithSessionKey('View'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('config_users.detail', ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function SelectObject($id)
    {
        $this->object = ConfigGroup::findOrFail($id);
    }

    public function Disable()
    {
        try {
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.disable', ['object' => "object"])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => "object", 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
    }
}
