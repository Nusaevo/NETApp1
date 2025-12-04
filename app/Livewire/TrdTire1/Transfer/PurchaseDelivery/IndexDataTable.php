<?php

namespace App\Livewire\TrdTire1\Transfer\PurchaseDelivery;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking, DelivPicking, BillingHdr};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Services\TrdTire1\TransferService;
use Exception;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = DelivHdr::class;
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_code', 'desc');
    }

    public function builder(): Builder
    {
        return DelivHdr::with(['DelivPacking.DelivPickings', 'Partner'])
            ->leftJoin('billing_hdrs', function ($join) {
                $join->on('billing_hdrs.id', '=', 'deliv_hdrs.billhdr_id')
                    ->where('billing_hdrs.tr_type', 'APB');
            })
            ->where('deliv_hdrs.tr_type', 'PD')
            ->select('deliv_hdrs.*', 'billing_hdrs.tax_process_date as billing_tax_process_date')
            ->orderBy('deliv_hdrs.updated_at', 'desc')
            ->orderBy('deliv_hdrs.tr_date', 'desc')
            ->orderBy('deliv_hdrs.tr_code', 'desc');
    }
    public function columns(): array
    {
        return [
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("Tgl. Terima Barang"), "tr_date")
                ->format(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '-';
                })
                ->searchable()
                ->sortable(),
            // Column::make('currency', "curr_rate")
            //     ->hideIf(true)
            //     ->sortable(),
            Column::make($this->trans("Nomor Surat Jalan"), "tr_code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->redirectAppCode . '.Transaction.PurchaseDelivery.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey((string)$row->id)  // Ensure it's a string
                    ]) . '">' . $row->tr_code . '</a>';
                })
                ->html(),
            Column::make($this->trans("Tgl. Surat Jalan"), "reff_date")
                ->format(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '-';
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans("supplier"), "partner_id")
                ->format(function ($value, $row) {
                    return $row->Partner ?
                        '<a href="' . route($this->redirectAppCode . '.Master.Partner.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->partner_id)
                        ]) . '">' . $row->Partner->name . '</a>' :
                        '<span class="text-muted">Nama tidak tersedia</span>';
                })
                ->html(),
            Column::make($this->trans("gudang"), "warehouse")
                ->label(function ($row) {
                    // Mengambil warehouse dari DelivPicking
                    $delivPicking = DelivPicking::whereHas('DelivPacking', function($query) use ($row) {
                        $query->where('trhdr_id', $row->id)
                              ->where('tr_type', 'PD');
                    })->first();
                    return $delivPicking ? $delivPicking->wh_code : '-';
                })
                ->sortable(),
            Column::make($this->trans('Kode/Nama Barang'), 'kode_barang')
                ->label(function ($row) {
                    // Ambil semua kode barang dan nama dari DelivPicking melalui relasi DelivPacking
                    $matlData = DelivPicking::with('Material')
                        ->whereHas('DelivPacking', function($query) use ($row) {
                            $query->where('trhdr_id', $row->id);
                        })
                        ->get();

                    if ($matlData->isNotEmpty()) {
                        $formattedData = $matlData->map(function($item) {
                            $code = $item->matl_code;
                            $name = $item->Material ? $item->Material->name : '-';
                            return $code . ' - ' . $name;
                        });
                        return $formattedData->implode('<br>');
                    }
                    return '-';
                })
                ->html()
                ->sortable(),
            Column::make($this->trans('Barang'), 'total_qty')
                ->label(function ($row) {
                    // Tampilkan qty per item sesuai urutan daftar Kode/Nama Barang
                    $pickings = DelivPicking::with('DelivPacking')
                        ->whereHas('DelivPacking', function($query) use ($row) {
                            $query->where('trhdr_id', $row->id);
                        })
                        ->get();

                    if ($pickings->isNotEmpty()) {
                        $qtyList = $pickings->map(function($picking) {
                            return round($picking->qty);
                        });
                        return $qtyList->implode('<br>');
                    }

                    return '0';
                })
                ->html()
                ->sortable(),
            Column::make('Tgl Transfer', 'billing_tax_process_date')
                ->label(function ($row) {
                    // Cek dari attribute billing_tax_process_date yang sudah di-join
                    if (isset($row->billing_tax_process_date) && $row->billing_tax_process_date) {
                        return \Carbon\Carbon::parse($row->billing_tax_process_date)->format('d-m-Y');
                    }
                    // Fallback: cek melalui relasi jika ada
                    if ($row->billhdr_id) {
                        $billingHdr = BillingHdr::where('id', $row->billhdr_id)
                            ->where('tr_type', 'APB')
                            ->first();
                        if ($billingHdr && $billingHdr->tax_process_date) {
                            return \Carbon\Carbon::parse($billingHdr->tax_process_date)->format('d-m-Y');
                        }
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
                            //     'route' => route('TrdTire1..PurchaseDelivery.PrintPdf', [
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
            DateFilter::make('Tanggal Awal Terima')->filter(function (Builder $builder, string $value) {
                $builder->where('deliv_hdrs.tr_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir Terima')->filter(function (Builder $builder, string $value) {
                $builder->where('deliv_hdrs.tr_date', '<=', $value);
            }),
            DateFilter::make('Tanggal Awal Kirim')->filter(function (Builder $builder, string $value) {
                $builder->where('deliv_hdrs.reff_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir Kirim')->filter(function (Builder $builder, string $value) {
                $builder->where('deliv_hdrs.reff_date', '<=', $value);
            }),
            SelectFilter::make('Jenis', 'brand')
                ->options([
                    '' => 'Semua',
                    'IRC' => 'IRC',
                    'GT RADIAL' => 'GT RADIAL',
                    'GAJAH TUNGGAL' => 'GAJAH TUNGGAL',
                    'ZENEOS' => 'ZENEOS',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->whereExists(function ($query) use ($value) {
                            $query->select(DB::raw(1))
                                ->from('deliv_pickings')
                                ->join('deliv_packings', 'deliv_packings.id', '=', 'deliv_pickings.trpacking_id')
                                ->join('materials', 'materials.id', '=', 'deliv_pickings.matl_id')
                                ->whereRaw('deliv_packings.trhdr_id = deliv_hdrs.id')
                                ->where('materials.brand', $value);
                        });
                    }
                }),
            $this->createTextFilter('Nomor Surat Jalan', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }, true),
            $this->createTextFilter('Nomor Nota Pembelian', 'reffhdrtr_code', 'Cari Kode Referensi', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('deliv_packings')
                        ->whereRaw('deliv_packings.trhdr_id = deliv_hdrs.id')
                        ->where(DB::raw('UPPER(reffhdrtr_code)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),

            // SelectFilter::make('Tipe Kendaraan', 'vehicle_type')
            //     ->options([
            //         '' => 'Semua',
            //         'O' => 'Mobil',
            //         'I' => 'Motor',
            //     ])
            //     ->filter(function (Builder $builder, string $value) {
            //         if ($value !== '') {
            //             $builder->whereExists(function ($query) use ($value) {
            //                 $query->select(DB::raw(1))
            //                     ->from('deliv_packings')
            //                     ->join('order_hdrs', 'order_hdrs.tr_code', '=', 'deliv_packings.reffhdrtr_code')
            //                     ->whereRaw('deliv_packings.trhdr_id = deliv_hdrs.id')
            //                     ->where('order_hdrs.sales_type', $value);
            //             });
            //         }
            //     }),
            $this->createTextFilter('Supplier', 'name', 'Cari Supplier', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            SelectFilter::make('Status Transfer', 'transfer_status')
                ->options([
                    '' => 'All',
                    'transferred' => 'Sudah Transfer',
                    'not_transferred' => 'Belum Transfer',
                ])
                ->filter(function ($builder, $value) {
                    if ($value !== '') {
                        if ($value === 'transferred') {
                            // Filter untuk delivery yang sudah ditransfer (billing memiliki tax_process_date)
                            // Cek melalui billhdr_id langsung atau melalui OrderHdr yang direferensikan
                            $builder->where(function ($query) {
                                // Cek melalui billhdr_id langsung
                                $query->whereExists(function ($subQuery) {
                                    $subQuery->select(DB::raw(1))
                                        ->from('billing_hdrs')
                                        ->whereRaw('billing_hdrs.id = deliv_hdrs.billhdr_id')
                                        ->where('billing_hdrs.tr_type', 'APB')
                                        ->whereNotNull('billing_hdrs.tax_process_date');
                                })
                                // Atau cek melalui OrderHdr yang direferensikan di DelivPacking
                                ->orWhereExists(function ($subQuery) {
                                    $subQuery->select(DB::raw(1))
                                        ->from('deliv_packings')
                                        ->join('order_hdrs', function ($join) {
                                            $join->on('order_hdrs.tr_code', '=', 'deliv_packings.reffhdrtr_code')
                                                ->where('order_hdrs.tr_type', '=', 'PO');
                                        })
                                        ->join('billing_hdrs', function ($join) {
                                            $join->on('billing_hdrs.tr_code', '=', 'order_hdrs.tr_code')
                                                ->where('billing_hdrs.tr_type', '=', 'APB')
                                                ->whereNotNull('billing_hdrs.tax_process_date');
                                        })
                                        ->whereRaw('deliv_packings.trhdr_id = deliv_hdrs.id')
                                        ->where('deliv_packings.tr_type', 'PD');
                                });
                            });
                        } elseif ($value === 'not_transferred') {
                            // Filter untuk delivery yang belum ditransfer
                            $builder->where(function ($query) {
                                // Tidak ada billing dengan tax_process_date melalui billhdr_id
                                // DAN tidak ada billing dengan tax_process_date melalui OrderHdr
                                $query->whereNotExists(function ($subQuery) {
                                    $subQuery->select(DB::raw(1))
                                        ->from('billing_hdrs')
                                        ->whereRaw('billing_hdrs.id = deliv_hdrs.billhdr_id')
                                        ->where('billing_hdrs.tr_type', 'APB')
                                        ->whereNotNull('billing_hdrs.tax_process_date');
                                })
                                ->whereNotExists(function ($subQuery) {
                                    $subQuery->select(DB::raw(1))
                                        ->from('deliv_packings')
                                        ->join('order_hdrs', function ($join) {
                                            $join->on('order_hdrs.tr_code', '=', 'deliv_packings.reffhdrtr_code')
                                                ->where('order_hdrs.tr_type', '=', 'PO');
                                        })
                                        ->join('billing_hdrs', function ($join) {
                                            $join->on('billing_hdrs.tr_code', '=', 'order_hdrs.tr_code')
                                                ->where('billing_hdrs.tr_type', '=', 'APB')
                                                ->whereNotNull('billing_hdrs.tax_process_date');
                                        })
                                        ->whereRaw('deliv_packings.trhdr_id = deliv_hdrs.id')
                                        ->where('deliv_packings.tr_type', 'PD');
                                });
                            });
                        }
                    }
                }),
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

            // Transfer Delivery langsung beserta data terkait (Partner, Material, DelivPacking, DelivPicking)
            $results = $transferService->transferDeliveryToTrdTire2($this->getSelected());

            // Tampilkan hasil
            if (count($results['success']) > 0) {
                $successMessage = "Berhasil transfer " . count($results['success']) . " delivery ke CTMS (TrdTire2).";
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
