<?php

namespace App\Http\Livewire\Transactions\PurchasesOrders;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Transactions\OrderHdr;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Status;
use Lang;
use Exception;
class IndexDataTable extends DataTableComponent
{
    protected $model = OrderHdr::class;
    public $object;
    public function mount(): void
    {
        $this->setSort('created_at', 'desc');
        $this->setFilter('status_code',  Status::ACTIVE);
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
            Column::make("Id", "id")
                ->sortable()
                ->searchable(),
            Column::make("Tanggal", "tr_date")
                ->searchable()
                ->sortable(),
            Column::make("Supplier", "Partner.name")
                ->searchable()
                ->sortable(),
           Column::make("Status", "status_code")
                    ->searchable()
                    ->sortable()
                    ->format(function ($value, $row, Column $column) {
                        return Status::getStatusString($value);
                    }),
            Column::make('Created Date', 'created_at')
                ->sortable(),
                Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'enable_this_row' => true,
                        'allow_details' => true,
                        'allow_edit' => in_array($row->status_code, [Status::ACTIVE]),
                        'allow_disable' => in_array($row->status_code, [Status::ACTIVE]),
                        'allow_delete' => false,
                        'wire_click_show' => "\$emit('viewData', $row->id)",
                        'wire_click_edit' => "\$emit('editData', $row->id)",
                        'wire_click_disable' => "\$emit('selectData', $row->id)",
                        'access' => "customers"
                    ]);
                }),
            LinkColumn::make('')
                ->title(function ($row) {
                    return $row->status_code === "ACT" ? 'Nota Terima Supplier' : '';
                })
                ->location(function ($row) {
                    if ($row->status_code === "ACT") {
                        return route("purchases_deliveries.detail", ["action" => encryptWithSessionKey('Create'), "objectId" => encryptWithSessionKey($row->id)]);
                    }
                    return null;
                })
                ->attributes(function ($row) {
                    if ($row->status_code === "ACT") {
                        return [
                            'class' => 'btn btn-primary btn-sm',
                            'style' => 'text-decoration: none;',
                        ];
                    }
                    return [];
                }),
            // LinkColumn::make('')
            //     ->title(fn ($row) => 'Print Nota')
            //     ->location(fn ($row) => route('purchases_orders.printpdf', ['objectId' => encryptWithSessionKey($row->id)]))
            //     ->attributes(function ($row) {
            //         return [
            //             'class' => 'btn btn-primary btn-sm',
            //             'style' => 'text-decoration: none;',
            //         ];
            //     })
        ];
    }


    public function View($id)
    {
        return redirect()->route('purchases_orders.detail', ['action' => encryptWithSessionKey('View'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('purchases_orders.detail', ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function SelectObject($id)
    {
        $this->object = OrderHdr::findOrFail($id);
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

    public function filters(): array
    {
        return [
            SelectFilter::make('Status', 'status_code')
                ->options([
                    Status::ACTIVE => 'Active',
                    Status::COMPLETED => 'Selesai',
                    '' => 'Semua',
                ])->filter(function ($builder, $value) {
                    if ($value === Status::ACTIVE) {
                        $builder->where('order_hdrs.status_code', Status::ACTIVE);
                    } else if ($value === Status::COMPLETED) {
                        $builder->where('order_hdrs.status_code', Status::COMPLETED);
                    } else if ($value === '') {
                        $builder->withTrashed();
                    }
                }),
            DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '<=', $value);
            }),

        ];
    }
}
