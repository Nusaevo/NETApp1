<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\SysConfig1\Configsnum; // Add this line

class IndexDataTable extends BaseDataTableComponent
{
    public $print_date; // Add this line
    public $selectedItems = []; // Add this line
    public $deletedRemarks = []; // Add this line
    public $filters = []; // Add this line

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
        $configDetails = $this->getConfigDetails();
        $printDates = OrderHdr::select('print_date')
            ->distinct()
            ->whereNotNull('print_date')
            ->pluck('print_date', 'print_date')
            ->toArray();

        return [
            SelectFilter::make('Nomor Faktur Pajak Terakhir')
                ->options([$configDetails['last_cnt'] => $configDetails['last_cnt']])
                ->filter(function (Builder $builder, string $value) {}),
            SelectFilter::make('Batas Nomor Faktur')
                ->options([$configDetails['wrap_high'] => $configDetails['wrap_high']])
                ->filter(function (Builder $builder, string $value) {}),
            SelectFilter::make('Tanggal Proses')
                ->options($printDates)
                ->filter(function (Builder $builder, string $value) {
                    $this->filters['print_date'] = $value; // Add this line
                    $builder->where('print_date', $value);
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
            'setProsesDate' => 'Proses Nota Baru',
            'nomorFaktur' => 'Set Nomor Faktur',
            'deleteNomorFaktur' => 'Hapus Nomor Faktur',
            'changeNomorFaktur' => 'Ubah Nomor Faktur',
            'cetakProsesDate' => 'Cetak Proses Faktur Pajak',
        ];
    }

    public function setProsesDate()
    {
        // Update all print_date to current date if it is '1900-01-01'
        OrderHdr::where('print_date', '1900-01-01')
            ->update(['print_date' => now()]);

        $this->dispatch('success', 'Tanggal proses berhasil disimpan');
    }

    public function nomorFaktur()
    {
        if (count($this->getSelected()) > 0) {
            $config = Configsnum::where('code', 'SO_FPAJAK_LASTID')->first();
            if ($config) {
                $lastId = (int) $config->last_cnt;
                $stepCnt = (int) $config->step_cnt;
                $wrapHigh = (int) $config->wrap_high;
                $maxAssigned = $lastId; // Variabel untuk menyimpan nomor faktur tertinggi yang telah dipakai

                $selectedOrderIds = $this->getSelected();
                foreach ($selectedOrderIds as $orderId) {
                    do {
                        if (!empty($this->deletedRemarks)) {
                            // Urutkan deletedRemarks sehingga nomor terkecil digunakan terlebih dahulu
                            sort($this->deletedRemarks);
                            $newId = array_shift($this->deletedRemarks);
                        } else {
                            $newId = $lastId + $stepCnt;
                            if ($newId > $wrapHigh) {
                                $newId = 1; // Reset ke 1 jika melebihi batas
                            }
                            $lastId = $newId;
                        }
                    } while (OrderHdr::where('print_remarks', $newId)->exists());

                    // Update nomor faktur pada order yang bersangkutan
                    OrderHdr::where('id', $orderId)->update(['print_remarks' => $newId]);

                    // Perbarui nomor faktur tertinggi yang telah dipakai
                    if ($newId > $maxAssigned) {
                        $maxAssigned = $newId;
                    }
                }

                // Update last_cnt berdasarkan nomor faktur tertinggi yang baru diset
                $config->last_cnt = $maxAssigned;
                $config->save();

                // Kosongkan deletedRemarks setelah digunakan
                $this->deletedRemarks = [];
                $this->clearSelected();
                $this->dispatch('success', 'Nomor faktur berhasil disimpan');
            } else {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => 'Konfigurasi SO_FPAJAK_LASTID tidak ditemukan'
                ]);
            }
        }
    }


    public function deleteNomorFaktur()
    {
        if (count($this->getSelected()) > 0) {
            $orders = OrderHdr::whereIn('id', $this->getSelected())->get(['id', 'print_remarks']);
            $config = Configsnum::where('code', 'SO_FPAJAK_LASTID')->first();
            $deletedRemarks = [];

            foreach ($orders as $order) {
                if ($order->print_remarks) {
                    $deletedRemarks[] = $order->print_remarks;
                    // Simpan nomor yang dihapus agar bisa digunakan kembali
                    $this->deletedRemarks[] = $order->print_remarks;
                }
            }

            if ($config && !empty($deletedRemarks)) {
                // Jika nomor tertinggi yang dihapus sama dengan last_cnt, perbarui last_cnt
                $maxDeletedRemark = max($deletedRemarks);
                if ($maxDeletedRemark == $config->last_cnt) {
                    $newLastCnt = min($deletedRemarks) - $config->step_cnt;
                    // Pastikan tidak menjadi nilai negatif
                    $config->last_cnt = ($newLastCnt < 0) ? 0 : $newLastCnt;
                    $config->save();
                }
            }

            // Hapus nomor faktur pada order yang dipilih
            OrderHdr::whereIn('id', $this->getSelected())->update(['print_remarks' => null]);

            $this->clearSelected();
            $this->dispatch('success', 'Nomor faktur berhasil dihapus');
        }
    }

    public function changeNomorFaktur()
    {
        if (count($this->getSelected()) > 0) {
            $config = Configsnum::where('code', 'SO_FPAJAK_LASTID')->first();
            if ($config) {
                $lastId   = (int) $config->last_cnt;
                $stepCnt  = (int) $config->step_cnt;
                $wrapHigh = (int) $config->wrap_high;
                $maxAssigned = $lastId; // Menyimpan nomor faktur tertinggi yang telah dipakai

                $selectedOrderIds = $this->getSelected();
                foreach ($selectedOrderIds as $orderId) {
                    do {
                        if (!empty($this->deletedRemarks)) {
                            // Urutkan agar nomor terkecil dipakai terlebih dahulu
                            sort($this->deletedRemarks);
                            $newId = array_shift($this->deletedRemarks);
                        } else {
                            $newId = $lastId + $stepCnt;
                            if ($newId > $wrapHigh) {
                                $newId = 1; // Reset ke 1 jika melebihi batas
                            }
                            $lastId = $newId;
                        }
                    } while (OrderHdr::where('print_remarks', $newId)->exists());

                    // Jika order sudah memiliki nomor, simpan sebagai reusable
                    $order = OrderHdr::find($orderId);
                    if ($order->print_remarks) {
                        $this->deletedRemarks[] = $order->print_remarks;
                    }

                    OrderHdr::where('id', $orderId)->update(['print_remarks' => $newId]);

                    // Update maxAssigned jika nomor baru lebih tinggi
                    if ($newId > $maxAssigned) {
                        $maxAssigned = $newId;
                    }
                }

                // Update konfigurasi last_cnt sesuai dengan nomor faktur tertinggi yang telah dipakai
                $config->last_cnt = $maxAssigned;
                $config->save();

                // Kosongkan reusable deletedRemarks setelah dipakai
                $this->deletedRemarks = [];
                $this->clearSelected();
                $this->dispatch('success', 'Nomor faktur berhasil diubah');
            } else {
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => 'Konfigurasi SO_FPAJAK_LASTID tidak ditemukan'
                ]);
            }
        }
    }


    public function getConfigDetails()
    {
        $config = Configsnum::where('code', 'SO_FPAJAK_LASTID')->first();
        if ($config) {
            return [
                'last_cnt' => $config->last_cnt,
                'wrap_high' => $config->wrap_high,
            ];
        }
        return [
            'last_cnt' => 'N/A',
            'wrap_high' => 'N/A',
        ];
    }

    public function cetakProsesDate()
    {
        $selectedPrintDate = $this->filters['print_date'] ?? null;
        if ($selectedPrintDate) {
            $orders = OrderHdr::where('print_date', $selectedPrintDate)->get();
            $orderIds = $orders->pluck('id')->toArray(); // Add this line
            return redirect()->route('TrdTire1.Tax.TaxInvoice.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey(json_encode($orderIds)), // Modify this line
            ]);
        }
    }
}
