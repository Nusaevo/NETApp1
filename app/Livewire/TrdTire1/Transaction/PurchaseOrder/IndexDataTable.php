<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseOrder;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivPacking, OrderHdr, OrderDtl};
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_code', 'desc');
    }

    public function builder(): Builder
    {
        return OrderHdr::with(['OrderDtl.Material', 'Partner'])
            ->where('order_hdrs.tr_type', 'PO')
            ->orderBy('order_hdrs.tr_date', 'desc');
    }
    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->format(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '';
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make('currency', "curr_rate")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("tr_code"), "tr_code")
                ->format(function ($value, $row) {
                    if ($row->partner_id) {
                        return '<a href="' . route($this->appCode . '.Transaction.PurchaseOrder.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id)
                        ]) . '">' . $row->tr_code . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
                Column::make($this->trans("supplier"), "partner_id")
                ->format(fn($v, $r) => $r->Partner?->name
                    ? '<a href="' . route($this->appCode . '.Master.Partner.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($r->partner_id)
                    ]) . '">' . e($r->Partner->name) . '</a>'
                    : ''
                )
                ->html(),
            Column::make('Kode/Nama Barang', 'orderdtl_codes')
                ->label(function ($row) {
                    // Ambil semua kode barang dan nama dari OrderDtl
                    if ($row->OrderDtl && $row->OrderDtl->count() > 0) {
                        $formattedData = $row->OrderDtl->map(function($item) {
                            $code = $item->matl_code;
                            $name = $item->Material ? $item->Material->name : '-';
                            return $code . ' - ' . $name;
                        });
                        return $formattedData->implode('<br>');
                    }
                    return '-';
                })
                ->html(),
            Column::make($this->trans('Qty Barang'), 'total_qty')
                ->label(function ($row) {
                    return $row->total_qty;
                })
                ->sortable(),
            Column::make($this->trans('Qty Diterima'), 'total_qty')
                ->label(function ($row) {
                    $totalQty = DelivPacking::where('reffhdr_id', $row->id)->sum('qty');
                    return round($totalQty);
                })
                ->sortable(),
            Column::make($this->trans('amt'), 'total_amt')
                ->label(function ($row) {
                    return rupiah($row->total_amt , false);
                })
                ->sortable(),
            // Column::make($this->trans("Status"), "status_code")
            //     ->format(function ($value, $row) {
            //         $statusMap = [
            //             Status::OPEN => 'Open',
            //             Status::PRINT => 'Print',
            //             Status::SHIP => 'Ship',
            //             Status::CANCEL => 'Cancel',
            //             Status::ACTIVE => 'Active',
            //         ];
            //         return $statusMap[$value] ?? 'Unknown';
            //     }),
            Column::make($this->trans('action'), 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'custom_actions' => [
                            // [
                            //     'label' => 'Print',
                            //     'route' => route('TrdTire1.Procurement.PurchaseOrder.PrintPdf', [
                            //         'action' => encryptWithSessionKey('Edit'),
                            //         'objectId' => encryptWithSessionKey($row->id)
                            //     ]),
                            //     'icon' => 'bi bi-printer'
                            // ],
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
            DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '<=', $value);
            }),
            $this->createTextFilter('Nomor Nota', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(order_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }, true),
            $this->createTextFilter('Supplier', 'name', 'Cari Supplier', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            // Filter kode barang (matl_code) pada OrderDtl
            $this->createTextFilter('Kode Barang', 'matl_code', 'Cari Kode Barang', function (Builder $builder, string $value) {
                $builder->whereHas('OrderDtl', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(matl_code)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            SelectFilter::make('Tipe Kendaraan', 'sales_type')
                ->options([
                    '' => 'Semua',
                    'O' => 'Mobil',
                    'I' => 'Motor',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('order_hdrs.sales_type', $value);
                    }
                }),
            // SelectFilter::make('Status', 'status_code')
            //     ->options([
            //         '' => 'All', // Tambahkan opsi "All" dengan nilai kosong
            //         Status::ACTIVE => 'Active',
            //         Status::OPEN => 'Open',
            //         Status::PRINT => 'Print',
            //         Status::SHIP => 'Ship',
            //         Status::CANCEL => 'Cancel',
            //     ])
            //     ->filter(function ($builder, $value) {
            //         if ($value !== '') { // Jika nilai tidak kosong, filter berdasarkan status_code
            //             $builder->where('order_hdrs.status_code', $value);
            //         }
            //     }),
        ];
    }
}
