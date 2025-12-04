<?php

namespace App\Livewire\TrdTire1\Transfer\SalesOrder;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivPacking, OrderHdr, OrderDtl, BillingHdr};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Services\TrdTire1\TransferService;
use Exception;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        // $this->setDefaultSort('tr_code', 'desc');
        // $this->setDefaultSort('updated_at', 'desc');
    }

    public function configure(): void
    {
        parent::configure();

        $this->setConfigurableAreas([
            'after-toolbar' => 'livewire.trd-tire1.transfer.sales-order.custom-filters',
        ]);
    }

    public function builder(): Builder
    {
        return OrderHdr::with(['OrderDtl', 'Partner', 'DelivHdr', 'BillingHdr'])
            ->leftJoin('billing_hdrs', function ($join) {
                $join->on('billing_hdrs.tr_code', '=', 'order_hdrs.tr_code')
                    ->where('billing_hdrs.tr_type', 'ARB');
            })
            ->where('order_hdrs.tr_type', 'SO')
            ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN, Status::SHIP, Status::BILL])
            ->where('order_hdrs.tax_doc_flag', 1)
            ->whereHas('DelivHdr', function ($query) {
                $query->where('tr_type', 'SD');
            })
            ->select('order_hdrs.*', 'billing_hdrs.tax_process_date as billing_tax_process_date');
            // ->orderBy('order_hdrs.updated_at', 'desc');
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
                        return '<a href="' . route($this->redirectAppCode . '.Transaction.SalesOrder.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id)
                        ]) . '">' . $row->tr_code . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
            Column::make($this->trans("Customer"), "partner_id")
                ->format(function ($value, $row) {
                    if ($row->Partner && $row->Partner->name) {
                        return '<a href="' . route($this->redirectAppCode . '.Master.Partner.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->partner_id)
                        ]) . '">' . $row->Partner->name . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
            Column::make('Kode Barang', 'orderdtl_codes')
                ->label(function ($row) {
                    // Ambil semua kode barang dari OrderDtl, pisahkan dengan koma
                    if ($row->OrderDtl && $row->OrderDtl->count() > 0) {
                        return $row->OrderDtl->pluck('matl_code')->implode(', ');
                    }
                    return '-';
                }),
            Column::make($this->trans('Qty Barang'), 'total_qty')
                ->label(function ($row) {
                    return $row->total_qty;
                })
                ->sortable(),
            Column::make($this->trans('amt'), 'total_amt')
                ->label(function ($row) {
                    return rupiah($row->total_amt, false);
                })
                ->sortable(),
            Column::make($this->trans("Status"), "status_code")
                ->format(function ($value, $row) {
                    $statusMap = [
                        Status::OPEN => 'Belum Cetak',
                        Status::PRINT => 'Tercetak',
                        Status::SHIP => 'Terkirim',
                        Status::CANCEL => 'Batal',
                        Status::BILL => 'Lunas',
                    ];
                    return $statusMap[$value] ?? 'Unknown';
                }),
            Column::make('Tgl Transfer', 'billing_tax_process_date')
                ->label(function ($row) {
                    // Cek dari relasi BillingHdr terlebih dahulu, jika tidak ada cek dari attribute billing_tax_process_date
                    if ($row->BillingHdr && $row->BillingHdr->tax_process_date) {
                        return \Carbon\Carbon::parse($row->BillingHdr->tax_process_date)->format('d-m-Y');
                    }
                    if (isset($row->billing_tax_process_date) && $row->billing_tax_process_date) {
                        return \Carbon\Carbon::parse($row->billing_tax_process_date)->format('d-m-Y');
                    }
                    return '-';
                })
                ->sortable(),
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
                $builder->where('order_hdrs.tr_date', '>=', $value)
                    ->reorder()
                    ->orderBy('order_hdrs.tr_date', 'asc')
                    ->orderBy('order_hdrs.tr_code', 'asc');
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '<=', $value)
                    ->reorder()
                    ->orderBy('order_hdrs.tr_date', 'asc')
                    ->orderBy('order_hdrs.tr_code', 'asc');
            }),
            $this->createTextFilter('Nomor Nota', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(order_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }, true),
            $this->createTextFilter('Customer', 'name', 'Cari Customer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            // Tambahkan filter kode barang (matl_code)
            $this->createTextFilter('Kode Barang', 'matl_code', 'Cari Kode Barang', function (Builder $builder, string $value) {
                $builder->whereHas('OrderDtl', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(matl_code)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            // SelectFilter::make('Tipe Penjualan', 'sales_type')
            //     ->options([
            //         '' => 'All',
            //         'O' => 'Mobil',
            //         'I' => 'Motor',
            //     ])
            //     ->filter(function ($builder, $value) {
            //         if ($value !== '') {
            //             $builder->where('order_hdrs.sales_type', $value);
            //         }
            //     }),
            // SelectFilter::make('Status', 'status_code')
            //     ->options([
            //         '' => 'All', // Tambahkan opsi "All" dengan nilai kosong
            //         Status::OPEN => 'Belum Cetak',
            //         Status::PRINT => 'Tercetak',
            //         'belum_kirim' => 'Belum Kirim', // Gabungan Status::PRINT dan Status::OPEN
            //         Status::SHIP => 'Terkirim',
            //         Status::CANCEL => 'Batal Nota',
            //         Status::BILL => 'Lunas',
            //     ])
            //     ->filter(function ($builder, $value) {
            //         if ($value !== '') { // Jika nilai tidak kosong, filter berdasarkan status_code
            //             if ($value === 'belum_kirim') {
            //                 // Filter untuk status PRINT atau OPEN (belum kirim)
            //                 $builder->whereIn('order_hdrs.status_code', [Status::PRINT, Status::OPEN]);
            //             } else {
            //                 $builder->where('order_hdrs.status_code', $value);
            //             }
            //         }
            //     }),
            SelectFilter::make('Status Transfer', 'transfer_status')
                ->options([
                    '' => 'All',
                    'transferred' => 'Sudah Transfer',
                    'not_transferred' => 'Belum Transfer',
                ])
                ->filter(function ($builder, $value) {
                    if ($value !== '') {
                        if ($value === 'transferred') {
                            // Filter untuk order yang sudah ditransfer (billing memiliki tax_process_date)
                            $builder->whereHas('BillingHdr', function ($query) {
                                $query->where('tr_type', 'ARB')
                                    ->whereNotNull('tax_process_date');
                            });
                        } elseif ($value === 'not_transferred') {
                            // Filter untuk order yang belum ditransfer (billing tidak memiliki tax_process_date atau tidak ada billing)
                            $builder->where(function ($query) {
                                $query->whereDoesntHave('BillingHdr', function ($q) {
                                    $q->where('tr_type', 'ARB');
                                })->orWhereHas('BillingHdr', function ($q) {
                                    $q->where('tr_type', 'ARB')
                                        ->whereNull('tax_process_date');
                                });
                            });
                        }
                    }
                }),
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
            'transferKeCTMS' => 'Transfer ke CTMS',
        ];
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
}
