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
use App\Services\TrdTire1\TransferService;
use Exception;

class IndexDataTable extends BaseDataTableComponent
{
    public $tax_process_date;
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
            Column::make($this->trans("Nomor Nota"), "tr_code")
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
            Column::make($this->trans('dpp'), 'amt_beforetax')
                ->label(function ($row) {
                    $orderDetails = OrderDtl::where('trhdr_id', $row->id)->get();
                    $dpp = $orderDetails->sum('amt_beforetax');
                    return rupiah($dpp);
                })
                ->sortable(),
            Column::make($this->trans('ppn'), 'amt_tax')
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
            Column::make($this->trans("Tgl Proses"), "tax_process_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans('NPWP CODE'), 'npwp_code')
                ->label(function ($row) {
                    return $row->npwp_code;
                })
                ->sortable(),
            Column::make($this->trans('NAMA WP'), 'npwp_name')
                ->label(function ($row) {
                    return $row->npwp_name;
                })
                ->sortable(),
            Column::make($this->trans('ALAMAT WP'), 'npwp_addr')
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
        $printDates = OrderHdr::select('tax_process_date')
            ->where('order_hdrs.tr_type', 'SO')
            ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP])
            ->where('order_hdrs.tax_doc_flag', 1)
            ->distinct()
            ->whereNotNull('tax_process_date')
            ->orderBy('tax_process_date', 'desc')
            ->pluck('tax_process_date', 'tax_process_date')
            ->toArray();

        // Add "Not Selected" option for tax_process_date
        $printDates = ['' => 'Not Selected'] + $printDates;

        $masaOptions = OrderHdr::selectRaw("TO_CHAR(tr_date, 'YYYY-MM') as filter_value, TO_CHAR(tr_date, 'FMMonth-YYYY') as display_value") // Updated for PostgreSQL
            ->where('order_hdrs.tr_type', 'SO')
            ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP])
            ->where('order_hdrs.tax_doc_flag', 1)
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
                        $this->filters['tax_process_date'] = $value;
                        $builder->where('tax_process_date', $value);
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
            SelectFilter::make('Nomor Faktur')
                ->options([
                    '' => 'Semua',
                    'with' => 'Ada Nomor Faktur',
                    'without' => 'Tanpa Nomor Faktur',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'with') {
                        // Ada nomor faktur: tax_doc_num tidak null, tidak kosong, dan tidak 0
                        $builder->whereNotNull('order_hdrs.tax_doc_num')
                            ->where('order_hdrs.tax_doc_num', '!=', '')
                            ->where('order_hdrs.tax_doc_num', '!=', 0);
                    } elseif ($value === 'without') {
                        // Tanpa nomor faktur: tax_doc_num null, kosong, atau 0
                        $builder->where(function ($q) {
                            $q->whereNull('order_hdrs.tax_doc_num')
                                ->orWhere('order_hdrs.tax_doc_num', '=', '')
                                ->orWhere('order_hdrs.tax_doc_num', '=', 0);
                        });
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
            'transferKeCTMS' => 'Transfer ke CTMS',
        ];
    }

    public function setProsesDate()
    {
        $newDataCount = OrderHdr::whereNull('tax_process_date')->count();

        if ($newDataCount === 0) {
            $this->dispatch('error', 'Tidak ada data baru yang bisa diproses.');
            return;
        }

        // Update semua tax_process_date yang null menjadi tanggal sekarang
        OrderHdr::whereNull('tax_process_date')
            ->update(['tax_process_date' => now()]);

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
        $selectedPrintDate = $this->filters['tax_process_date'] ?? null;
        if ($selectedPrintDate) {
            // Check if there are any orders for the selected print date
            $orderCount = OrderHdr::where('tax_process_date', $selectedPrintDate)
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

    public function transferKeCTMS()
    {
        if (count($this->getSelected()) == 0) {
            $this->dispatch('error', 'Pilih minimal satu data untuk ditransfer.');
            return;
        }

        try {
            $transferService = new TransferService();

            // Validasi apakah TrdTire2 tersedia
            if (!$transferService->isTrdTire2Available()) {
                $this->dispatch('error', 'Aplikasi TrdTire2 tidak tersedia atau tidak aktif.');
                return;
            }

            // Lakukan transfer
            $results = $transferService->transferOrderToTrdTire2($this->getSelected());

            // Tampilkan hasil
            if (count($results['success']) > 0) {
                $successMessage = "Berhasil transfer " . count($results['success']) . " order ke CTMS (TrdTire2).";
                if (count($results['errors']) > 0) {
                    $successMessage .= " Terdapat " . count($results['errors']) . " error.";
                }
                $this->dispatch('success', $successMessage);

                // Refresh page setelah transfer berhasil
                $this->dispatch('refreshPage');
            } else {
                // Jika tidak ada success dan tidak ada error, kemungkinan ada masalah
                if (count($results['errors']) == 0) {
                    $this->dispatch('error', 'Transfer tidak menghasilkan data. Periksa log untuk detail lebih lanjut.');
                }
            }

            if (count($results['errors']) > 0) {
                $errorMessage = "Terjadi error pada transfer:\n" . implode("\n", $results['errors']);
                $this->dispatch('error', $errorMessage);
            }

            // Refresh table
            $this->dispatch('refreshTable');

        } catch (Exception $e) {
            $this->dispatch('error', 'Terjadi kesalahan saat transfer: ' . $e->getMessage());
        }
    }

    #[On('refreshDatatable')]
    public function refreshDatatable()
    {
        $this->clearSelected();
    }
}
