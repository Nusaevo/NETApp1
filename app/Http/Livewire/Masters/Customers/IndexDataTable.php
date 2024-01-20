<?php

namespace App\Http\Livewire\Masters\Customers;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Masters\Partner;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
class IndexDataTable extends DataTableComponent
{
    protected $model = Partner::class;

    public $object;

    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return Partner::query()->where('grp', 'CUST')->withTrashed();
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
            Column::make("Customer Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Address", "address")
                ->searchable()
                ->sortable(),
            Column::make('Status', 'deleted_at')
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return is_null($row->deleted_at) ? 'Active' : 'Non-Active';
                }),
            Column::make('Created Date', 'created_at')
                    ->sortable(),
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
        return redirect()->route('customers.detail', ['action' => Crypt::encryptString('View'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('customers.detail', ['action' => Crypt::encryptString('Edit'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function SelectObject($id)
    {
        $this->object = Partner::findOrFail($id);
    }

    public function Disable()
    {
        try {
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.disable', ['object' => $this->object->name])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => $this->object->name, 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
    }
}
