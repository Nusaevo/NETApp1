<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        return OrderHdr::with(['OrderDtl', 'Partner'])
            ->where('order_hdrs.tr_type', 'SO')
            ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN])
            ->where('order_hdrs.tax_doc_flag', 1);
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
            Column::make('currency', "curr_rate")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("tr_code"), "tr_code")
                ->format(function ($value, $row) {
                    if ($row->partner_id) {
                        return '<a href="' . route($this->appCode . '.Transaction.SalesOrder.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id)
                        ]) . '">' . $row->tr_code . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
            Column::make($this->trans("supplier"), "partner_id")
                ->format(function ($value, $row) {
                    if ($row->Partner && $row->Partner->name) {
                        return '<a href="' . route($this->appCode . '.Master.Partner.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->partner_id)
                        ]) . '">' . $row->Partner->name . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
            Column::make($this->trans('amt'), 'total_amt')
                ->label(function ($row) {
                    return rupiah($row->total_amt);
                })
                ->sortable(),
            Column::make($this->trans('dpp'), 'dpp')
                ->label(function ($row) {
                    $orderDetails = OrderDtl::where('trhdr_id', $row->id)->get();
                    $dpp = $orderDetails->sum('dpp');
                    return rupiah($dpp);
                })
                ->sortable(),
            Column::make($this->trans('amt_tax'), 'amt_tax')
                ->label(function ($row) {
                    $orderDetails = OrderDtl::where('trhdr_id', $row->id)->get();
                    $amtTax = $orderDetails->sum('amt_tax');
                    return rupiah($amtTax);
                })
                ->sortable(),
            Column::make($this->trans("No Faktur"), "print_remarks")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Tgl Proses"), "print_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans('npwp_code'), 'npwp_code')
                ->label(function ($row) {
                    return $row->npwp_code;
                })
                ->sortable(),
            Column::make($this->trans('npwp_name'), 'npwp_name')
                ->label(function ($row) {
                    return $row->npwp_name;
                })
                ->sortable(),
            Column::make($this->trans('npwp_address'), 'npwp_addr')
                ->label(function ($row) {
                    return $row->npwp_addr;
                })
                ->sortable(),
            Column::make($this->trans("npwp_code21"), "npwp_code")
                ->format(function ($value, $row) {
                    if ($row->PartnerDetail && $row->PartnerDetail->npwp_code) {
                        return $row->PartnerDetail->npwp_code;
                    } else {
                        return '';
                    }
                })
                ->hideIf(true)
                ->html(),
            Column::make($this->trans("npwp_name21"), "npwp_name")
                ->format(function ($value, $row) {
                    if ($row->PartnerDetail && $row->PartnerDetail->npwp_name) {
                        return $row->PartnerDetail->npwp_name;
                    } else {
                        return '';
                    }
                })
                ->hideIf(true)
                ->html(),
            Column::make($this->trans("npwp_addr21"), "npwp_addr")
                ->format(function ($value, $row) {
                    if ($row->PartnerDetail && $row->PartnerDetail->npwp_addr) {
                        return $row->PartnerDetail->npwp_addr;
                    } else {
                        return '';
                    }
                })
                ->hideIf(true)
                ->html(),
            // Column::make($this->trans("amt"), "total_amt_in_idr")
            //     ->label(function ($row) {
            //         $totalAmt = 0;

            //         $orderDetails = OrderDtl::where('trhdr_id', $row->id)->get();

            //         if ($orderDetails->isEmpty()) {
            //             return 'N/A';
            //         }
            //     })
            //     ->sortable(),

            // Column::make($this->trans('status'), "status_code")
            //     ->sortable()
            //     ->format(function ($value, $row, Column $column) {
            //         return Status::getStatusString($value);
            //     }),
            // Column::make($this->trans("created_date"), "created_at")
            //     ->searchable()
            //     ->sortable(),
            Column::make($this->trans('action'), 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
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
            // $this->createTextFilter('Material', 'matl_code', 'Cari Kode Material', function (Builder $builder, string $value) {
            //     $builder->whereExists(function ($query) use ($value) {
            //         $query->select(DB::raw(1))
            //             ->from('order_dtls')
            //             ->whereRaw('order_dtls.tr_code = order_hdrs.tr_code')
            //             ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
            //             ->where('order_dtls.tr_type', 'PO');
            //     });
            // }),
            // $this->createTextFilter('Supplier', 'name', 'Cari Supplier', function (Builder $builder, string $value) {
            //     $builder->whereHas('Partner', function ($query) use ($value) {
            //         $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
            //     });
            // }),
            // New filters
            DateFilter::make('Tanggal Nota')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '=', $value);
            }),
            TextFilter::make('Nomor Nota')->filter(function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(order_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            TextFilter::make('Custommer')->filter(function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
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
    public function bulkActions(): array
    {
        return [
            'setProsesDate' => 'Set Tanggal Proses',
        ];
    }

    public function setProsesDate()
    {
        if (count($this->getSelected()) > 0) {
            $selectedItems = OrderHdr::whereIn('id', $this->getSelected())
                ->get(['tr_code as nomor_nota', 'partner_id'])
                ->map(function ($order) {
                    return [
                        'nomor_nota' => $order->tr_code,
                        'nama' => $order->Partner->name,
                        'kota' => $order->Partner->city,
                        'tr_date' => $order->tr_date,
                    ];
                })
                ->toArray();

            $this->dispatch('openProsesDateModal', orderIds: $this->getSelected(), selectedItems: $selectedItems);
            $this->dispatch('submitProsesDate');
        }
    }

    public function submitProsesDate()
    {
        $selectedOrderIds = $this->getSelected();
        if (count($selectedOrderIds) > 0) {
            OrderHdr::whereIn('id', $selectedOrderIds)->update(['print_date' => $this->print_date]);

            $this->clearSelected();
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => 'Tanggal proses berhasil diatur'
            ]);
        }
    }
}
