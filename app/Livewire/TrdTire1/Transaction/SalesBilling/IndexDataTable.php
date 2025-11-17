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
    protected $model = BillingHdr::class;
    public $bulkSelectedIds = null;
    public $tanggalTagih; // Field untuk tanggal tagih - specific untuk sales billing

    protected $listeners = [
        'autoUpdateTanggalTagih',
        'updateTanggalTagih',
        'clearTanggalTagih',
        'clearSelections',
    ];

    public $selectedRows = []; // Array untuk tracking selected rows

    public function clearSelections()
    {
        $this->selectedRows = [];

        // Force refresh the entire component to update checkbox states
        $this->dispatch('$refresh');

        // Dispatch event to update the custom filters
        $this->dispatch('selectionUpdated');
    }



    /**
     * Updated when selectedRows changes (automatically by wire:model)
     */
    public function updatedSelectedRows($value, $name)
    {
        // Dispatch event to update the custom filters
        $this->dispatch('selectionUpdated');

        // Jika ada perubahan pada selectedRows, cek item yang baru ditambah/dihapus
        if (is_array($this->selectedRows)) {
            // Logic untuk auto-update tanggal tagih akan dipindah ke view dengan Alpine.js
            // Karena kita tidak bisa tahu item mana yang baru di-check/uncheck dari sini
        }
    }

    public function mount(): void
    {
        $this->setSearchDisabled();

        // Disable bulk selection - kita pakai custom checkbox
        $this->setBulkActionsStatus(false);
        $this->setHideBulkActionsWhenEmptyStatus(true);
        $this->setSelectAllStatus(false);

        // Initialize tanggal tagih untuk sales billing
        $this->initializeTanggalTagih();

        // Clear any existing sorts and set default
        $this->clearSorts();
        $this->setDefaultSort('Partner.name', 'asc');
    }



    /**
     * Auto update tanggal tagih ketika row dipilih
     */
    public function autoUpdateTanggalTagih($rowId)
    {
        if (empty($this->tanggalTagih)) {
            $this->dispatch('warning', 'Silakan pilih tanggal tagih terlebih dahulu');
            return;
        }

        try {
            DB::beginTransaction();

            $billing = BillingHdr::find($rowId);
            if (!$billing) {
                $this->dispatch('error', 'Data tidak ditemukan');
                return;
            }

            // Simpan print_date lama untuk audit log
            $oldPrintDate = $billing->print_date;

            // Update tanggal tagih dan status
            $billing->print_date = $this->tanggalTagih;
            $billing->status_code = Status::PRINT;
            $billing->updated_at = now();
            $billing->save();

            // Create audit log
            try {
                AuditLogService::createPrintDateAuditLogs(
                    [$rowId],
                    $this->tanggalTagih,
                    $oldPrintDate
                );
            } catch (\Exception $e) {
                Log::error('Failed to create audit logs: ' . $e->getMessage());
            }

            DB::commit();

            $this->dispatch('success', "Tanggal tagih untuk nota {$billing->tr_code} berhasil diupdate");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to auto update tanggal tagih: ' . $e->getMessage());
            $this->dispatch('error', 'Gagal mengupdate tanggal tagih: ' . $e->getMessage());
        }
    }

    /**
     * Update tanggal tagih dengan tanggal yang diberikan
     */
    public function updateTanggalTagih($rowId, $tanggalTagih)
    {
        if (empty($tanggalTagih)) {
            $this->dispatch('warning', 'Tanggal tagih tidak valid');
            return;
        }

        try {
            DB::beginTransaction();

            $billing = BillingHdr::find($rowId);
            if (!$billing) {
                $this->dispatch('error', 'Data tidak ditemukan');
                return;
            }

            // Simpan print_date lama untuk audit log
            $oldPrintDate = $billing->print_date;

            // Update tanggal tagih dan status
            $billing->print_date = $tanggalTagih;
            $billing->status_code = Status::PRINT;
            $billing->updated_at = now();
            $billing->save();

            // Create audit log
            try {
                AuditLogService::createPrintDateAuditLogs(
                    [$rowId],
                    $tanggalTagih,
                    $oldPrintDate
                );
            } catch (\Exception $e) {
                Log::error('Failed to create audit logs: ' . $e->getMessage());
            }

            DB::commit();

            $this->dispatch('success', "Tanggal tagih untuk nota {$billing->tr_code} berhasil diupdate ke {$tanggalTagih}");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to update tanggal tagih: ' . $e->getMessage());
            $this->dispatch('error', 'Gagal mengupdate tanggal tagih: ' . $e->getMessage());
        }
    }

    /**
     * Clear tanggal tagih ketika row di-uncheck
     */
    public function clearTanggalTagih($rowId)
    {
        try {
            DB::beginTransaction();

            $billing = BillingHdr::find($rowId);
            if (!$billing) {
                $this->dispatch('error', 'Data tidak ditemukan');
                return;
            }

            // Simpan print_date lama untuk audit log
            $oldPrintDate = $billing->print_date;

            // Set tanggal tagih menjadi null dan ubah status kembali ke ACTIVE
            $billing->print_date = null;
            $billing->status_code = Status::ACTIVE;
            $billing->updated_at = now();
            $billing->save();

            // Create audit log
            try {
                AuditLogService::createPrintDateAuditLogs(
                    [$rowId],
                    '', // Empty string untuk null
                    $oldPrintDate
                );
            } catch (\Exception $e) {
                Log::error('Failed to create audit logs: ' . $e->getMessage());
            }

            DB::commit();

            $this->dispatch('info', "Tanggal tagih untuk nota {$billing->tr_code} berhasil dihapus");

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to clear tanggal tagih: ' . $e->getMessage());
            $this->dispatch('error', 'Gagal menghapus tanggal tagih: ' . $e->getMessage());
        }
    }

    public function configure(): void
    {
        // Call parent configure first
        parent::configure();

        // Enable multiple column sorting
        $this->setSingleSortingStatus(false);

        // Enable sorting functionality
        $this->setSortingStatus(true);

        // Hide sorting pills to avoid confusion
        $this->setSortingPillsStatus(false);

        // Enable tanggal tagih functionality - specific untuk sales billing
        $this->enableTanggalTagihArea('livewire.trd-tire1.transaction.sales-billing.custom-filters');
    }    /**
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
    }

    public function builder(): Builder
    {
        $query = BillingHdr::with([
                'Partner',
                'OrderHdr' => function($query) {
                    $query->where('tr_type', 'SO');
                },
                'DeliveryHdr' => function($query) {
                    $query->where('tr_type', 'SD');
                }
            ])
            ->leftJoin('order_hdrs', function($join) {
                $join->on('billing_hdrs.tr_code', '=', 'order_hdrs.tr_code')
                     ->where('order_hdrs.tr_type', 'SO');
            })
            ->leftJoin('partners', 'billing_hdrs.partner_id', '=', 'partners.id')
            ->select('billing_hdrs.*')
            ->where('billing_hdrs.tr_type', 'ARB')
            ->whereIn('billing_hdrs.status_code', [Status::ACTIVE, Status::PRINT, Status::OPEN, Status::PAID, Status::SHIP, Status::BILL]);

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make("Pilih ", "id")
                ->format(function ($value, $row) {
                    return '
                        <div class="text-center">
                            <input type="checkbox"
                                   class="form-check-input custom-checkbox"
                                   wire:model.live="selectedRows"
                                   value="' . $row->id . '"
                                   id="checkbox-' . $row->id . '">
                        </div>';
                })
                ->html(),
            Column::make($this->trans("Nomor Nota"), "DeliveryHdr.tr_code")
                ->format(function ($value, $row) {
                    if ($row->partner_id && $row->OrderHdr && $row->DeliveryHdr) {
                        return '<a href="' . route($this->appCode . '.Transaction.SalesOrder.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->OrderHdr->id)
                        ]) . '">' . $row->DeliveryHdr->tr_code . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html()
                ->sortable(),
            // Column::make($this->trans("Tgl. Nota"), "tr_date")
            //     ->format(function ($value) {
            //         return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '';
            //     })
            //     ->searchable(),
                // ->sortable(),
            Column::make($this->trans("Tgl. Nota"), "OrderHdr.tr_date")
                ->format(function ($value, $row) {
                    // Gunakan relasi OrderHdr yang sudah ada
                    return $row->OrderHdr && $row->OrderHdr->tr_date ?
                        \Carbon\Carbon::parse($row->OrderHdr->tr_date)->format('d-m-Y') : '';
                })
                ->sortable(),
            Column::make($this->trans("Tgl. Kirim"), "DeliveryHdr.tr_date")
                ->format(function ($value, $row) {
                    // Gunakan relasi DeliveryHdr yang akan dibuat
                    return $row->DeliveryHdr && $row->DeliveryHdr->tr_date ?
                        \Carbon\Carbon::parse($row->DeliveryHdr->tr_date)->format('d-m-Y') : '';
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
                ->sortable(),
            // Use relation field for customer so SQL selects a real column.
            // Default ordering that ignores spaces is applied in builder() via orderByRaw(REPLACE(partners.name, ' ', '')) when no user sort is set.
            Column::make($this->trans("Customer"), "Partner.name")
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
                ->html()
                ->sortable(),

            Column::make($this->trans('Total Harga'), 'amt')
                ->label(function ($row) {
                    return $row->amt ? rupiah($row->amt, false) : '-';
                })
                ->sortable(),

            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true),
            Column::make('currency', "curr_rate")
                ->hideIf(true),
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
                    $builder->whereDate('order_hdrs.tr_date', '>=', $value);
                }),
            DateFilter::make('Tanggal Nota Akhir')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('order_hdrs.tr_date', '<=', $value);
                }),
            $this->createTextFilter('Nomor Nota', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw("UPPER(billing_hdrs.tr_code)"), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter($this->trans("Customer"), 'name', 'Cari Custommer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
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
                            $builder->whereDate('billing_hdrs.print_date', $value)
                                   ->reorder()
                                   ->orderByRaw("REPLACE(partners.name, ' ', '') asc")
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
        return [];
    }


    /**
     * Get selected items untuk custom filters
     */
    public function getSelectedItems()
    {
        return $this->selectedRows;
    }

    /**
     * Get selected count untuk custom filters
     */
    public function getSelectedItemsCount()
    {
        return count($this->selectedRows);
    }

    public function cetak()
    {
        if (empty($this->tanggalTagih)) {
            $this->dispatch('error', 'Silakan pilih tanggal tagih terlebih dahulu');
            return;
        }

        $selectedOrderIds = $this->selectedRows;
        if (count($selectedOrderIds) > 0) {
            try {
                DB::beginTransaction();

                $billingOrders = BillingHdr::whereIn('id', $selectedOrderIds)->get();

                // Simpan print_date lama untuk audit log
                $oldPrintDates = $billingOrders->pluck('print_date', 'id')->toArray();

                // Update tanggal tagih dan status ke PRINT
                foreach ($billingOrders as $billing) {
                    $billing->print_date = $this->tanggalTagih;
                    $billing->status_code = Status::PRINT;
                    $billing->updated_at = now();
                    $billing->save();
                }

                // Create audit logs
                try {
                    AuditLogService::createPrintDateAuditLogs(
                        $selectedOrderIds,
                        $this->tanggalTagih,
                        $oldPrintDates[$selectedOrderIds[0]] ?? null
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to create audit logs: ' . $e->getMessage());
                }

                DB::commit();

                // Clear selected items
                $this->selectedRows = [];

                // Dispatch success message
                $this->dispatch('success', 'Nota berhasil dicetak dengan tanggal tagih ' . $this->tanggalTagih);

                // Redirect to print view
                return redirect()->route($this->appCode . '.Transaction.SalesBilling.PrintPdf', [
                    'action' => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey(json_encode($selectedOrderIds)),
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Error printing invoices: ' . $e->getMessage());
                $this->dispatch('error', 'Gagal mencetak nota: ' . $e->getMessage());
            }
        } else {
            $this->dispatch('error', 'Nota belum dipilih.');
        }
    }
}
