<?php

namespace App\Livewire\TrdTire1\Transaction\ReceivablesSettlement;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{PaymentHdr, OrderDtl, PartnertrDtl, PaymentAdv};
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = PaymentHdr::class; // Update model
    public function mount(): void
    {
        $this->setSearchDisabled();
        // $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_code', 'desc');
    }

    public function builder(): Builder
    {
        return PaymentHdr::with(['PaymentDtl', 'Partner', 'paymentSrc']) // Update builder
            ->whereIn('payment_hdrs.tr_type', ['APP', 'ARP'])
            ->orderBy('payment_hdrs.tr_date', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('Nomor Pelunasan', 'tr_code')
                ->searchable()
                ->format(function ($value, $row) {
                    if ($row->partner_id) {
                        return '<a href="' . route($this->appCode . '.Transaction.ReceivablesSettlement.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id)
                        ]) . '">' . $row->tr_code . '</a>';
                    } else {
                        return $row->tr_code;
                    }
                })
                ->html(),
            Column::make($this->trans("Tanggal Pelunasan"), "tr_date")
                ->searchable(),
            Column::make($this->trans("customer"), "partner_id")
                ->format(function ($value, $row) {
                    if ($row->Partner && $row->Partner->name) {
                        return $row->Partner->name;
                    } else {
                        return '';
                    }
                })
                ->html(),
            Column::make($this->trans("Nomor Nota"), "tr_code")
                ->format(function ($value, $row) {
                    // Ambil nomor nota dari detail pembayaran
                    $paymentDetails = $row->PaymentDtl;
                    if ($paymentDetails && $paymentDetails->count() > 0) {
                        $notaNumbers = $paymentDetails->pluck('billhdrtr_code')->filter()->unique()->values();
                        return $notaNumbers->implode(', ');
                    }
                    return '-';
                })
                ->html(),
            Column::make($this->trans("Total Pelunasan"), "amt_dtls")
                ->format(function ($value, $row) {
                    return rupiah($row->amt_dtls ?? 0);
                }),
            Column::make($this->trans("Lebih Bayar"), "amt_advs")
                ->format(function ($value, $row) {
                    // Jika menggunakan saldo advance, maka lebih bayar = 0
                    // amt_advs berisi total advance yang digunakan, bukan lebih bayar
                    // Lebih bayar hanya muncul jika ada overpayment setelah semua advance digunakan
                    $overPayment = 0;

                    // Cari overpayment dari PaymentAdv dengan reff_id = id (menandakan overpayment)
                    $overPaymentRecord = PaymentAdv::where('trhdr_id', $row->id)
                        ->whereColumn('reff_id', '=', 'id')
                        ->first();

                    if ($overPaymentRecord) {
                        $overPayment = $overPaymentRecord->amt ?? 0;
                    }

                    return rupiah($overPayment);
                })
                ->sortable(),
            Column::make($this->trans("Adjustment"), "tr_code")
                ->format(function ($value, $row) {
                    // Hitung total adjustment dari PartnertrDtl dengan tr_type 'ARA' (credit note)
                    $adjustmentTotal = 0;

                    // Ambil semua PaymentDtl untuk payment ini
                    $paymentDetails = $row->PaymentDtl;
                    if ($paymentDetails && $paymentDetails->count() > 0) {
                        foreach ($paymentDetails as $detail) {
                            // Cari PartnertrDtl dengan tr_type 'ARA' yang terkait dengan detail ini
                            $cnData = PartnertrDtl::where('tr_type', '=', 'ARA')
                                ->where('tr_code', '=', $detail->tr_code)
                                ->where('partnerbal_id', '=', $detail->partnerbal_id)
                                ->first();

                            if ($cnData) {
                                // Pastikan nilai selalu positif (+)
                                $adjustmentTotal += abs($cnData->amt);
                            }
                        }
                    }

                    return rupiah($adjustmentTotal);
                })
                ->html(),
             // Column::make($this->trans("tr_type"), "tr_type")
            //     ->hideIf(true)
            //     ->sortable(),
            // Column::make('currency', "curr_rate")
            //     ->hideIf(true)
            //     ->sortable(),
            // Column::make($this->trans("tr_code"), "tr_code")
            //     ->format(function ($value, $row) {
            //         if ($row->partner_id) {
            //             return '<a href="' . route($this->appCode . '.Transaction.DebtSettlement.Detail', [
            //                 'action' => encryptWithSessionKey('Edit'),
            //                 'objectId' => encryptWithSessionKey($row->id)
            //             ]) . '">' . $row->tr_code . '</a>';
            //         } else {
            //             return '';
            //         }
            //     })
            //     ->html(),
            // Column::make($this->trans("supplier"), "partner_id")
            //     ->format(function ($value, $row) {
            //         if ($row->Partner && $row->Partner->name) {
            //             return '<a href="' . route($this->appCode . '.Master.Partner.Detail', [
            //                 'action' => encryptWithSessionKey('Edit'),
            //                 'objectId' => encryptWithSessionKey($row->partner_id)
            //             ]) . '">' . $row->Partner->name . '</a>';
            //         } else {
            //             return '';
            //         }
            //     })
            //     ->html(),
            // Column::make($this->trans('qty'), 'total_qty')
            //     ->label(function ($row) {
            //         return $row->total_qty;
            //     })
            //     ->sortable(),
            // Column::make($this->trans('amt'), 'total_amt')
            //     ->label(function ($row) {
            //         return rupiah($row->total_amt);
            //     })
            //     ->sortable(),
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
                $builder->where('payment_hdrs.tr_date', '=', $value);
            }),
            TextFilter::make('Nomor Pelunasan')->filter(function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(payment_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
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
}
