<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl, OrderHdr, BillingHdr, BillingDeliv};
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TrdTire1\AuditLogService;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = DelivHdr::class;
    public $bulkSelectedIds = null;
    public $tanggalTagih; // Field untuk tanggal tagih - specific untuk sales billing

    protected $listeners = [
        'refreshDatatable' => '$refresh', // Listen untuk refresh table
    ];

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_code', 'asc');
        $this->setDefaultSort('partner_code', 'asc');

        // Enable bulk selection and actions untuk menampilkan checkbox
        $this->setBulkActionsStatus(true);
        $this->setHideBulkActionsWhenEmptyStatus(false);
        $this->setSelectAllStatus(true);
        $this->setBulkActionsEnabled();

        // Initialize tanggal tagih untuk sales billing
        $this->initializeTanggalTagih();
    }

    public function configure(): void
    {
        // Call parent configure first
        parent::configure();

        // Enable tanggal tagih functionality - specific untuk sales billing
        $this->enableTanggalTagihArea('livewire.trd-tire1.transaction.sales-billing.custom-filters');

        // Specific configurations for this datatable
        $this->setDefaultSort('tgl_transaksi', 'desc')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPerPage(10)
            ->setLoadingPlaceholderEnabled();
    }

    /**
     * Initialize tanggal tagih with current date - specific untuk sales billing
     */
    private function initializeTanggalTagih(): void
    {
        if (empty($this->tanggalTagih)) {
            $this->tanggalTagih = now()->format('Y-m-d');
        }
    }

    /**
     * Enable tanggal tagih functionality in datatable - specific untuk sales billing
     */
    private function enableTanggalTagihArea(string $viewPath = 'livewire.custom-filters'): void
    {
        $currentAreas = $this->getConfigurableAreas() ?? [];
        $currentAreas['after-toolbar'] = $viewPath;
        $this->setConfigurableAreas($currentAreas);
    }    public function builder(): Builder
    {
        return BillingHdr::with(['Partner', 'OrderHdr'])
            ->where('billing_hdrs.tr_type', 'ARB')
            ->whereIn('billing_hdrs.status_code', [Status::ACTIVE, Status::PRINT, Status::OPEN, Status::PAID, Status::SHIP, Status::BILL])
            ->orderBy('billing_hdrs.partner_code', 'asc')
            ->orderBy('billing_hdrs.tr_code', 'asc');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("Nomor Nota"), "tr_code")
                ->format(function ($value, $row) {
                    if ($row->partner_id && $row->OrderHdr) {
                        return '<a href="' . route($this->appCode . '.Transaction.SalesOrder.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->OrderHdr->id)
                        ]) . '">' . $row->tr_code . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
            // Column::make($this->trans("Tgl. Nota"), "tr_date")
            //     ->format(function ($value) {
            //         return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '';
            //     })
            //     ->searchable(),
                // ->sortable(),
            Column::make($this->trans("Tgl. Nota"), "tr_date")
                ->label(function ($row) {
                    $delivery = OrderHdr::where('tr_type', 'SO')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery && $delivery->tr_date ? \Carbon\Carbon::parse($delivery->tr_date)->format('d-m-Y') : '';
                })
                ->sortable(),
            Column::make($this->trans("Tgl. Kirim"), "tr_date")
                ->label(function ($row) {
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery && $delivery->tr_date ? \Carbon\Carbon::parse($delivery->tr_date)->format('d-m-Y') : '';
                })
                ->sortable(),
            Column::make($this->trans("Due Date"), "tr_date")
                ->label(function ($row) {
                    // Gunakan tr_date dan payment_due_days dari relasi OrderHdr
                    $orderTrDate = $row->OrderHdr ? $row->OrderHdr->tr_date : null;
                    $paymentDueDays = $row->OrderHdr && $row->OrderHdr->payment_due_days !== null
                        ? (int)$row->OrderHdr->payment_due_days
                        : 0;
                    if ($orderTrDate) {
                        $dueDate = \Carbon\Carbon::parse($orderTrDate)->addDays($paymentDueDays);
                        return $dueDate->format('d-m-Y');
                    }
                    return '-';
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Customer"), "partner_id")
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

            Column::make($this->trans('Total Harga'), 'total_amt')
                ->label(function ($row) {
                    // Ambil total_amt dari relasi OrderHdr
                    return $row->OrderHdr ? rupiah($row->OrderHdr->amt, false) : '-';
                })
                ->sortable(),

            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make('currency', "curr_rate")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("Tanggal Tagih"), "print_date")
                ->format(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '';
                })
                ->searchable()
                ->sortable(),
            // Column::make($this->trans("Status"), "status_code")
            //     ->format(function ($value, $row) {
            //         $statusMap = [
            //             Status::OPEN   => 'Open',
            //             Status::PRINT  => 'Print',
            //             Status::SHIP   => 'Ship',
            //             Status::CANCEL => 'Cancel',
            //             Status::ACTIVE => 'Active',
            //             Status::PAID   => 'Paid',
            //         ];
            //         return $statusMap[$value] ?? 'Unknown';

            //     }),
            Column::make( 'id')
                ->hideIf(true)
                // ->format(function ($value, $row, Column $column) {
                //     return view('layout.customs.data-table-action', [
                //         'row' => $row,
                //         'custom_actions' => [],
                //         'enable_this_row' => true,
                //         'allow_details' => false,
                //         'allow_edit' => true,
                //         'allow_disable' => false,
                //         'allow_delete' => false,
                //         'permissions' => $this->permissions
                //     ]);
                // }),
        ];
    }

    public function filters(): array
    {
        return [

            DateFilter::make('Tanggal Nota Awal')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('tr_date', '>=', $value);
                }),
            DateFilter::make('Tanggal Nota Akhir')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('tr_date', '<=', $value);
                }),
            $this->createTextFilter('Nomor Nota', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter($this->trans("Customer"), 'name', 'Cari Custommer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            SelectFilter::make($this->trans("Tipe Penjualan"), 'sales_type')
                ->options([
                    // '' => 'Semua',
                    'I' => 'Motor',
                    'O' => 'Mobil',
                ])
                ->setFilterDefaultValue('I')
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->whereExists(function ($query) use ($value) {
                            $query->select(DB::raw(1))
                                ->from('order_hdrs')
                                ->whereRaw('order_hdrs.tr_code = billing_hdrs.tr_code')
                                ->where('order_hdrs.sales_type', $value);
                        });
                    }
                }),
                DateFilter::make('Tanggal Tagih')
                    ->filter(function (Builder $builder, string $value) {
                        if ($value) { // Hanya terapkan filter jika ada nilai yang dipilih
                            $builder->whereDate('print_date', $value)
                                   ->reorder()
                                   ->orderBy('billing_hdrs.partner_code', 'asc')
                                   ->orderBy('billing_hdrs.tr_code', 'asc');
                        }
                    }),
                SelectFilter::make('Status Pembayaran')
                    ->options([
                        '' => 'Semua Nota',
                        'lunas' => 'Lunas',
                        'belum_lunas' => 'Belum Lunas',
                    ])
                    ->setFilterDefaultValue('')
                    ->filter(function (Builder $builder, string $value) {
                        if ($value === 'lunas') {
                            $builder->whereRaw('(billing_hdrs.amt - billing_hdrs.amt_reff) <= 0');
                        } elseif ($value === 'belum_lunas') {
                            $builder->whereRaw('(billing_hdrs.amt - billing_hdrs.amt_reff) > 0');
                        }
                    }),
        ];
    }

    public function bulkActions(): array
    {
        return [
            'autoUpdateSelected' => 'Update Tanggal Tagih',
        ];
    }

    /**
     * Bulk action untuk auto update tanggal tagih
     */
    public function autoUpdateSelected()
    {
        $selectedIds = $this->getSelected();

        if (count($selectedIds) > 0 && !empty($this->tanggalTagih)) {
            try {
                DB::beginTransaction();

                // Simpan print_date lama untuk audit log
                $oldPrintDates = BillingHdr::whereIn('id', $selectedIds)
                    ->pluck('print_date', 'id')
                    ->toArray();

                // Update print_date di billing_hdrs untuk selected IDs
                $updated = BillingHdr::whereIn('id', $selectedIds)
                    ->update([
                        'print_date' => $this->tanggalTagih,
                        'updated_at' => now()
                    ]);

                // Create audit logs menggunakan method yang sama seperti di Index.php
                try {
                    AuditLogService::createPrintDateAuditLogs(
                        $selectedIds,
                        $this->tanggalTagih,
                        $oldPrintDates[$selectedIds[0]] ?? null
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to create audit logs: ' . $e->getMessage());
                }

                DB::commit();

                $this->dispatch('success', "Berhasil update tanggal tagih untuk {$updated} record(s)");

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Error auto-update tanggal tagih: ' . $e->getMessage());
                $this->dispatch('error', 'Gagal update tanggal tagih: ' . $e->getMessage());
            }
        } else {
            $this->dispatch('warning', 'Pilih data dan pastikan tanggal tagih tidak kosong');
        }
    }
}
