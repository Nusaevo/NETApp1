<?php

namespace App\Http\Livewire\Settings\ConfigConsts;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Settings\ConfigConst;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;

class IndexDataTable extends DataTableComponent
{
    protected $model = ConfigConst::class;

    public $object;

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
    }

    public function builder(): Builder
    {
        return ConfigConst::query()
            ->withTrashed()
            ->select();
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

    public function columns(): array
    {
        return [
            Column::make("Const Group", "const_group")
                ->searchable()
                ->sortable(),
            Column::make("Application","id")
                ->format(function($value, $row, Column $column) {
                    return optional($row->configAppl)->code . ' - ' . optional($row->configAppl)->name;
                })
                ->searchable()
                ->sortable(),
            Column::make("Seq", "seq")
                ->searchable()
                ->sortable(),
            Column::make("Str1", "str1")
                ->searchable()
                ->sortable(),
            Column::make("Str2", "str2")
                ->searchable()
                ->sortable(),
           Column::make("Num1", "num1")
                 ->searchable()
                 ->sortable(),
            Column::make("Num2", "num2")
                 ->searchable()
                 ->sortable(),
            Column::make("Note1", "note1")
                 ->searchable()
                 ->sortable(),
            Column::make('Status', 'deleted_at')
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return is_null($row->deleted_at) ? 'Active' : 'Non-Active';
                }),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'enable_this_row' => true,
                        'allow_details' => true,
                        'allow_edit' => true,
                        'allow_disable' => !$row->trashed(),
                        'allow_delete' => false,
                        'wire_click_show' => "\$emit('viewData', $row->id)",
                        'wire_click_edit' => "\$emit('editData', $row->id)",
                        'wire_click_disable' => "\$emit('selectData', $row->id)",
                        'access' => "config_consts"
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

    public function View($id)
    {
        return redirect()->route('config_consts.detail', ['action' => encryptWithSessionKey('View'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('config_consts.detail', ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function SelectObject($id)
    {
        $this->object = ConfigConst::findOrFail($id);
    }

    public function Disable()
    {
        try {
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->str1])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->str1, 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
    }
}
