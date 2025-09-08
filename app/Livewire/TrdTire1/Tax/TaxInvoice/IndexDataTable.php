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
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class IndexDataTable extends BaseDataTableComponent
{
    public $print_date;
    public $selectedItems = [];
    public $filters = [];

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
            ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP])
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
            Column::make($this->trans('amt'), 'amt')
                ->label(function ($row) {
                    return rupiah($row->amt);
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
            Column::make($this->trans("No Faktur"), "tax_doc_num")
                ->format(function ($value, $row) {
                    // Tampilkan nomor faktur hanya jika tidak 0 (tidak dihapus)
                    return $row->tax_doc_num && $row->tax_doc_num != 0 ? $row->tax_doc_num : '';
                })
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
            Column::make($this->trans('action'), 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'row' => $row,
                        'custom_actions' => [
                            [
                                'label' => 'Print',
                                'route' => route('TrdTire1.Tax.TaxInvoice.PrintPdf', [
                                    'action' => encryptWithSessionKey('Edit'),
                                    'objectId' => encryptWithSessionKey($row->id)
                                ]),
                                'icon' => 'bi bi-printer'
                            ],
                        ],
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => false,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'permissions' => $this->permissions
                    ]);
                }),

        ];
    }

    public function filters(): array
    {
        $configDetails = $this->getConfigDetails();
        $printDates = OrderHdr::select('print_date')
            ->distinct()
            ->whereNotNull('print_date')
            ->pluck('print_date', 'print_date')
            ->toArray();

        // Add "Not Selected" option for print_date
        $printDates = ['' => 'Not Selected'] + $printDates;

        $masaOptions = OrderHdr::selectRaw("TO_CHAR(tr_date, 'YYYY-MM') as filter_value, TO_CHAR(tr_date, 'FMMonth-YYYY') as display_value") // Updated for PostgreSQL
            ->distinct()
            ->orderByRaw("TO_CHAR(tr_date, 'YYYY-MM') DESC") // Sort by year-month descending (latest first)
            ->get()
            ->pluck('display_value', 'filter_value')
            ->toArray();

        // Add "Not Selected" option for masa
        $masaOptions = ['' => 'Not Selected'] + $masaOptions;

        return [
            SelectFilter::make('Tanggal Proses')
                ->options($printDates)
                ->filter(function (Builder $builder, string $value) {
                    if ($value) { // Only apply filter if a value is selected
                        $this->filters['print_date'] = $value;
                        $builder->where('print_date', $value);
                    }
                }),
            SelectFilter::make('Masa')
                ->options($masaOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value) { // Only apply filter if a value is selected
                        $this->filters['masa'] = $value; // Ensure the filter value is set
                        $builder->whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$value]); // Filter using YYYY-MM
                    }
                }),
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
        ];
    }
    public function bulkActions(): array
    {
        return [
            'setProsesDate' => 'Proses Nota Baru',
            'nomorFaktur' => 'Set Nomor Faktur',
            'deleteNomorFaktur' => 'Hapus Nomor Faktur',
            'changeNomorFaktur' => 'Ubah Nomor Faktur',
            'cetakProsesDate' => 'Cetak Proses Faktur Pajak',
            'cetakLaporanPenjualan' => 'Cetak Laporan Penjualan',
        ];
    }

    public function setProsesDate()
    {
        $newDataCount = OrderHdr::whereNull('print_date')->count();

        if ($newDataCount === 0) {
            $this->dispatch('error', 'Tidak ada data baru yang bisa diproses.');
            return;
        }

        // Update semua print_date yang null menjadi tanggal sekarang
        OrderHdr::whereNull('print_date')
            ->update(['print_date' => now()]);

        $this->dispatch('success', 'Tanggal proses berhasil disimpan');
    }

    public function nomorFaktur()
    {
        if (count($this->getSelected()) === 0) {
            $this->dispatch('error', 'Tidak ada item yang dipilih.');
            return;
        }

        $selectedItems = OrderHdr::whereIn('id', $this->getSelected())
            ->with('Partner')
            ->get(['id', 'tr_code', 'partner_id', 'amt'])
            ->map(function ($order) {
                return [
                    'nomor_nota' => $order->tr_code,
                    'nama' => $order->Partner ? $order->Partner->name : '',
                    'total_amt' => rupiah($order->amt),
                ];
            })
            ->toArray();

        $this->dispatch('openNomorFakturModal', orderIds: $this->getSelected(), selectedItems: $selectedItems, actionType: 'set');
    }


    public function deleteNomorFaktur()
    {
        if (count($this->getSelected()) === 0) {
            $this->dispatch('error', 'Tidak ada item yang dipilih.');
            return;
        }

        $selectedItems = OrderHdr::whereIn('id', $this->getSelected())
            ->with('Partner')
            ->get(['id', 'tr_code', 'partner_id', 'amt'])
            ->map(function ($order) {
                return [
                    'nomor_nota' => $order->tr_code,
                    'nama' => $order->Partner ? $order->Partner->name : '',
                    'total_amt' => rupiah($order->amt),
                ];
            })
            ->toArray();

        $this->dispatch('openNomorFakturModal', orderIds: $this->getSelected(), selectedItems: $selectedItems, actionType: 'delete');
    }

    public function changeNomorFaktur()
    {
        if (count($this->getSelected()) === 0) {
            $this->dispatch('error', 'Tidak ada item yang dipilih.');
            return;
        }

        $selectedItems = OrderHdr::whereIn('id', $this->getSelected())
            ->with('Partner')
            ->get(['id', 'tr_code', 'partner_id', 'amt'])
            ->map(function ($order) {
                return [
                    'nomor_nota' => $order->tr_code,
                    'nama' => $order->Partner ? $order->Partner->name : '',
                    'total_amt' => rupiah($order->amt),
                ];
            })
            ->toArray();

        $this->dispatch('openNomorFakturModal', orderIds: $this->getSelected(), selectedItems: $selectedItems, actionType: 'change');
    }


    public function getConfigDetails()
    {
        // Method ini sudah tidak diperlukan karena nomor faktur bebas
        return [
            'last_cnt' => 'N/A',
            'wrap_high' => 'N/A',
        ];
    }

    public function cetakProsesDate()
    {
        $selectedPrintDate = $this->filters['print_date'] ?? null;
        if ($selectedPrintDate) {
            // Check if there are any orders for the selected print date
            $orderCount = OrderHdr::where('print_date', $selectedPrintDate)
                ->where('tr_type', 'SO')
                ->whereNull('deleted_at')
                ->count();

            if ($orderCount === 0) {
                $this->dispatch('error', 'Tidak ada data untuk tanggal proses yang dipilih.');
                return;
            }

            // Use array structure with JSON encoding
            $paramArray = [
                'selectedPrintDate' => $selectedPrintDate,
                'type' => 'cetakProsesDate'
            ];
            return redirect()->route('TrdTire1.Tax.TaxInvoice.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey(''),
                'additionalParam' => encryptWithSessionKey(json_encode($paramArray)),
            ]);
        }
        $this->dispatch('error', 'Tanggal proses belum dipilih.');
    }

    public function cetakLaporanPenjualan()
    {
        $selectedMasa = $this->filters['masa'] ?? null;
        if ($selectedMasa) {
            // Check if there are any orders for the selected masa
            $orderCount = OrderHdr::whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$selectedMasa])
                ->where('tr_type', 'SO')
                ->whereNull('deleted_at')
                ->count();

            if ($orderCount === 0) {
                $this->dispatch('error', 'Tidak ada data untuk masa yang dipilih.');
                return;
            }

            // Use array structure with JSON encoding
            $paramArray = [
                'selectedMasa' => $selectedMasa,
                'type' => 'cetakLaporanPenjualan'
            ];

            return redirect()->route('TrdTire1.Transaction.PurchaseDelivery.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey(''),
                'additionalParam' => encryptWithSessionKey(json_encode($paramArray)),
            ]);
        }
        $this->dispatch('error', 'Masa belum dipilih.');
    }

    #[On('refreshDatatable')]
    public function refreshDatatable()
    {
        $this->clearSelected();
    }
}
