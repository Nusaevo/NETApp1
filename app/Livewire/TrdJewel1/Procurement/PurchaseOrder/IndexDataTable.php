<?php

namespace App\Livewire\TrdJewel1\Procurement\PurchaseOrder;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Illuminate\Support\Facades\Lang;
use Exception;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;

    public function mount(): void
    {
        $this->setSearchVisibilityStatus(false);
        $this->customRoute = "";
        $this->getPermission($this->customRoute);

        $this->setDefaultSort('tr_date', 'desc');
    }

    public function builder(): Builder
    {
        return OrderHdr::with('OrderDtl')
        ->where('tr_type', 'PO')
        ->where('order_hdrs.status_code', Status::OPEN)
        ->orderBy('order_hdrs.created_at', 'desc');
    }
    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("tr_id"), "tr_id")
                ->sortable()
                ->searchable(),
            Column::make($this->trans("supplier"), "Partner.name")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("matl_code"), "matl_codes")
                ->label(function($row) {
                    return $row->matl_codes;
                })
                ->sortable(),
            Column::make($this->trans("qty"), "total_qty")
                ->label(function($row) {
                    return currencyToNumeric($row->total_qty);
                })
                ->sortable(),
            Column::make($this->trans("amt"), "total_amt")
                ->label(function($row) {
                    return rupiah(currencyToNumeric($row->total_amt));
                })
                ->sortable(),
            Column::make($this->trans('status'), "status_code")
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            // Column::make($this->trans("created_date"), "created_at")
            //     ->searchable()
            //     ->sortable(),
            Column::make($this->trans('action'), 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'row' => $row,
                        'custom_actions' => [
                            [
                                'label' => 'Print',
                                'route' => route('TrdJewel1.Procurement.PurchaseOrder.PrintPdf', [
                                    'action' => encryptWithSessionKey('Edit'),
                                    'objectId' => encryptWithSessionKey($row->id)
                                ]),
                                'icon' => 'bi bi-printer'
                            ],
                        ],
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => true,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'permissions' => $this->permissions
                    ]);
                }),

        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Supplier', 'supplier_name')
                ->config([
                    'placeholder' => 'Cari Supplier',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = strtoupper($value);
                    $builder->whereHas('Partner', function ($query) use ($value) {
                        $query->where(DB::raw('UPPER(name)'), 'like', '%' . $value . '%');
                    });
                }),
                TextFilter::make('Kode Barang', 'matl_code')
                ->config([
                    'placeholder' => 'Search Material Code',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $value = strtoupper($value);
                    $builder->whereExists(function ($query) use ($value) {
                        $query->select(DB::raw(1))
                            ->from('order_dtls')
                            ->whereRaw('order_dtls.tr_id = order_hdrs.tr_id')
                            ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . $value . '%')
                            ->where('order_dtls.tr_type', 'PO');
                    });
                }),
            // SelectFilter::make('Status', 'status_code')
            //     ->options([
            //         Status::OPEN => 'Open',
            //         Status::COMPLETED => 'Selesai',
            //         '' => 'Semua',
            //     ])->filter(function ($builder, $value) {
            //         if ($value === Status::ACTIVE) {
            //             $builder->where('order_hdrs.status_code', Status::ACTIVE);
            //         } else if ($value === Status::COMPLETED) {
            //             $builder->where('order_hdrs.status_code', Status::COMPLETED);
            //         } else if ($value === '') {
            //             $builder->withTrashed();
            //         }
            //     }),
            // DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
            //     $builder->where('order_hdrs.tr_date', '>=', $value);
            // }),
            // DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
            //     $builder->where('order_hdrs.tr_date', '<=', $value);
            // }),
        ];
    }
}
