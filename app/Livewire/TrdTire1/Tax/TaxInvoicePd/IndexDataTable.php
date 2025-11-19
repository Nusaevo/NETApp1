<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoicePd;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking, DelivPicking, BillingHdr};
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
    public $selectedItems = [];
    public $filters = [];
    public $bulkSelectedIds = null;

    protected $listeners = ['clearSelections' => 'clearSelections'];

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
            ->where('deliv_hdrs.tr_type', 'PD')
            ->orderBy('deliv_hdrs.updated_at', 'desc')
            ->orderBy('deliv_hdrs.tr_date', 'desc')
            ->orderBy('deliv_hdrs.tr_code', 'desc');
    }

    public function clearSelections(): void
    {
        $this->clearSelected();
        $this->bulkSelectedIds = null;
    }

    /**
     * Get BillingHdr (APB) from DelivHdr through DelivPacking -> reffhdrtr_code -> BillingOrder
     */
    private function getBillingHdrFromDelivHdr($delivHdr): ?BillingHdr
    {
        $delivPacking = DelivPacking::where('trhdr_id', $delivHdr->id)
            ->where('tr_type', 'PD')
            ->first();

        if (!$delivPacking || !$delivPacking->reffhdrtr_code) {
            return null;
        }

        return BillingHdr::join('billing_orders', function($join) {
                $join->on('billing_hdrs.id', '=', 'billing_orders.trhdr_id')
                     ->where('billing_orders.tr_type', '=', DB::raw('billing_hdrs.tr_type'));
            })
            ->where('billing_orders.reffhdrtr_code', $delivPacking->reffhdrtr_code)
            ->where('billing_hdrs.tr_type', 'APB')
            ->select('billing_hdrs.*')
            ->first();
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
            Column::make($this->trans("Nomor Surat Jalan"), "tr_code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->appCode . '.Transaction.PurchaseDelivery.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey((string)$row->id)
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
                        '<a href="' . route($this->appCode . '.Master.Partner.Detail', [
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
            // Column::make($this->trans('amt'), 'amt')
            //     ->label(function ($row) {
            //         $billingHdr = BillingHdr::where('tr_code', $row->tr_code)
            //             ->where('tr_type', 'APB')
            //             ->first();
            //         return $billingHdr ? rupiah($billingHdr->amt) : rupiah(0);
            //     })
            //     ->sortable(),
            // Column::make($this->trans('dpp'), 'amt_beforetax')
            //     ->label(function ($row) {
            //         $billingHdr = BillingHdr::where('tr_code', $row->tr_code)
            //             ->where('tr_type', 'APB')
            //             ->first();
            //         return $billingHdr ? rupiah($billingHdr->amt_beforetax) : rupiah(0);
            //     })
            //     ->sortable(),
            // Column::make($this->trans('ppn'), 'amt_tax')
            //     ->label(function ($row) {
            //         $billingHdr = BillingHdr::where('tr_code', $row->tr_code)
            //             ->where('tr_type', 'APB')
            //             ->first();
            //         return $billingHdr ? rupiah($billingHdr->amt_tax) : rupiah(0);
            //     })
            //     ->sortable(),
            Column::make($this->trans("No Faktur"), "taxinv_num")
                ->label(function ($row) {
                    $billingHdr = $this->getBillingHdrFromDelivHdr($row);
                    return ($billingHdr && $billingHdr->taxinv_num && $billingHdr->taxinv_num != 0)
                        ? $billingHdr->taxinv_num
                        : '';
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Tgl Proses"), "taxinv_date")
                ->label(function ($row) {
                    $billingHdr = $this->getBillingHdrFromDelivHdr($row);
                    return ($billingHdr && $billingHdr->taxinv_date && $billingHdr->taxinv_date != 0)
                        ? \Carbon\Carbon::parse($billingHdr->taxinv_date)->format('d-m-Y')
                        : '-';
                })
                ->searchable()
                ->sortable(),
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
            SelectFilter::make('Nomor Faktur')
                ->options([
                    '' => 'Semua',
                    'with' => 'Ada Nomor Faktur',
                    'without' => 'Tanpa Nomor Faktur',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'with') {
                        // Ada nomor faktur: check in BillingHdr
                        $builder->whereExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('billing_hdrs')
                                ->whereRaw('billing_hdrs.tr_code = deliv_hdrs.tr_code')
                                ->where('billing_hdrs.tr_type', 'APB')
                                ->whereNotNull('billing_hdrs.taxinv_num')
                                ->where('billing_hdrs.taxinv_num', '!=', '')
                                ->where('billing_hdrs.taxinv_num', '!=', 0);
                        });
                    } elseif ($value === 'without') {
                        // Tanpa nomor faktur: check in BillingHdr
                        $builder->where(function ($q) {
                            $q->whereNotExists(function ($query) {
                                $query->select(DB::raw(1))
                                    ->from('billing_hdrs')
                                    ->whereRaw('billing_hdrs.tr_code = deliv_hdrs.tr_code')
                                    ->where('billing_hdrs.tr_type', 'APB')
                                    ->whereNotNull('billing_hdrs.taxinv_num')
                                    ->where('billing_hdrs.taxinv_num', '!=', '')
                                    ->where('billing_hdrs.taxinv_num', '!=', 0);
                            });
                        });
                    }
                }),
            $this->createTextFilter('Nomor Surat Jalan', 'tr_code', 'Cari Nomor Surat Jalan', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(deliv_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }, true),
            $this->createTextFilter('Nomor Nota', 'reffhdrtr_code', 'Cari Kode Referensi', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('deliv_packings')
                        ->whereRaw('deliv_packings.trhdr_id = deliv_hdrs.id')
                        ->where(DB::raw('UPPER(reffhdrtr_code)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            $this->createTextFilter('Supplier', 'name', 'Cari Supplier', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
        ];
    }
    public function bulkActions(): array
    {
        return [
            'nomorFaktur' => 'Set Nomor Faktur',
            'deleteNomorFaktur' => 'Hapus Nomor Faktur',
            // 'cetakProsesDate' => 'Cetak Proses Faktur Pajak',
            // 'transferKeCTMS' => 'Transfer ke CTMS',
        ];
    }

    public function nomorFaktur()
    {
        if (count($this->getSelected()) === 0) {
            $this->dispatch('error', 'Tidak ada item yang dipilih.');
            return;
        }

        $selectedItems = DelivHdr::whereIn('id', $this->getSelected())
            ->with('Partner', 'DelivPacking')
            ->get(['id', 'tr_code', 'partner_id'])
            ->map(function ($deliv) {
                $billingHdr = $this->getBillingHdrFromDelivHdr($deliv);

                // Get PO OrderHdr from DelivPacking reffhdrtr_code for total_amt
                $delivPacking = $deliv->DelivPacking->first();
                $orderHdr = null;
                if ($delivPacking && $delivPacking->reffhdrtr_code) {
                    $orderHdr = \App\Models\TrdTire1\Transaction\OrderHdr::where('tr_code', $delivPacking->reffhdrtr_code)
                        ->where('tr_type', 'PO')
                        ->first();
                }

                return [
                    'id' => $deliv->id,
                    'nomor_nota' => $deliv->tr_code,
                    'nama' => $deliv->Partner ? $deliv->Partner->name : '',
                    'faktur' => $billingHdr ? ($billingHdr->taxinv_num ?: '') : '',
                    'total_amt' => $orderHdr ? rupiah($orderHdr->amt ?? 0) : rupiah(0),
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

        $selectedItems = DelivHdr::whereIn('id', $this->getSelected())
            ->with('Partner', 'DelivPacking')
            ->get(['id', 'tr_code', 'partner_id'])
            ->map(function ($deliv) {
                $billingHdr = $this->getBillingHdrFromDelivHdr($deliv);

                // Get PO OrderHdr from DelivPacking reffhdrtr_code for total_amt
                $delivPacking = $deliv->DelivPacking->first();
                $orderHdr = null;
                if ($delivPacking && $delivPacking->reffhdrtr_code) {
                    $orderHdr = \App\Models\TrdTire1\Transaction\OrderHdr::where('tr_code', $delivPacking->reffhdrtr_code)
                        ->where('tr_type', 'PO')
                        ->first();
                }

                return [
                    'id' => $deliv->id,
                    'nomor_nota' => $deliv->tr_code,
                    'nama' => $deliv->Partner ? $deliv->Partner->name : '',
                    'faktur' => $billingHdr ? ($billingHdr->taxinv_num ?: '') : '',
                    'total_amt' => $orderHdr ? rupiah($orderHdr->amt ?? 0) : rupiah(0),
                ];
            })
            ->toArray();

        $this->dispatch('openNomorFakturModal', orderIds: $this->getSelected(), selectedItems: $selectedItems, actionType: 'delete');
    }




    public function cetakProsesDate()
    {
        $selectedPrintDate = $this->filters['tax_process_date'] ?? null;
        if ($selectedPrintDate) {
            // Check if there are any deliveries with related PO orders for the selected print date
            $delivCount = DelivHdr::whereHas('OrderHdr', function($query) use ($selectedPrintDate) {
                    $query->where('tr_type', 'PO')
                          ->where('tax_process_date', $selectedPrintDate);
                })
                ->where('tr_type', 'PD')
                ->whereNull('deleted_at')
                ->count();

            if ($delivCount === 0) {
                $this->dispatch('error', 'Tidak ada data untuk tanggal proses yang dipilih.');
                return;
            }

            // Use array structure with JSON encoding
            $paramArray = [
                'selectedPrintDate' => $selectedPrintDate,
                'type' => 'cetakProsesDate'
            ];
            return redirect()->route($this->appCode . '.Tax.TaxInvoice.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey(''),
                'additionalParam' => encryptWithSessionKey(json_encode($paramArray)),
            ]);
        }
        $this->dispatch('error', 'Tanggal proses belum dipilih.');
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
