<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{BillingHdr, DelivHdr, DelivPacking, DelivPicking, OrderDtl, OrderHdr};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use App\Models\TrdTire1\Master\MatlUom;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Services\TrdTire1\{AuditLogService, BillingService, DeliveryService};
use App\Services\TrdTire1\Master\MasterService;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Master\Material;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire; // pastikan namespace ini diimport
use Illuminate\Support\Facades\{DB, Log, Session};
use Illuminate\Support\Carbon;
use Exception;
use Rappasoft\LaravelLivewireTables\Views\Filters\BooleanFilter;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = DelivHdr::class;
    public $bulkSelectedIds = null;
    public $tanggalKirim; // Field untuk tanggal kirim
    public $warehouse; // Field untuk warehouse
    public $warehouses = []; // Array untuk dropdown warehouse
    public $selectedRows = []; // Array untuk menyimpan ID rows yang dipilih
    public $batchProcessing = false; // Flag untuk batch processing

    protected $listeners = ['clearSelections'];

    /**
     * Computed property untuk selected count
     */
    public function getSelectedCountProperty()
    {
        return is_array($this->selectedRows) ? count($this->selectedRows) : 0;
    }

    public function clearSelections()
    {
        $this->clearSelected();
        $this->bulkSelectedIds = null;
        $this->selectedRows = []; // Clear custom selection

        // Dispatch event to update the custom filters
        $this->dispatch('selectionUpdated');
    }
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'asc');
        $this->setDefaultSort('tr_code', 'asc');

        // Initialize tanggal kirim dan warehouse
        $this->initializeTanggalKirim();
        $this->loadWarehouses();

        // Optimize rendering
        $this->selectedRows = [];
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

        // Disable default bulk actions area
        $this->setBulkActionsStatus(false);

        // Keep toolbar enabled for configurable areas
        $this->setToolBarStatus(true);

        // Enable custom filters area
        $this->setConfigurableAreas([
            'after-toolbar' => 'livewire.trd-tire1.transaction.sales-delivery.custom-filters',
        ]);
    }

    /**
     * Initialize tanggal kirim with current date
     */
    private function initializeTanggalKirim(): void
    {
        if (empty($this->tanggalKirim)) {
            $this->tanggalKirim = now()->format('Y-m-d');
        }
    }

    /**
     * Load warehouses from MasterService
     */
    private function loadWarehouses(): void
    {
        $masterService = new MasterService();
        $this->warehouses = $masterService->getWarehouse();
    }




    public function builder(): Builder
    {
        return OrderHdr::with(['OrderDtl', 'Partner'])
            ->where('order_hdrs.tr_type', 'SO')
            ->whereIn('order_hdrs.status_code', [Status::ACTIVE, Status::PRINT, Status::OPEN, Status::PAID, Status::SHIP, Status::BILL, Status::CANCEL])
            ->select('order_hdrs.*') // Pastikan semua field dari order_hdrs di-select
            ->orderBy('order_hdrs.tr_code', 'asc');
            // ->orderBy('order_hdrs.tr_date', 'desc');
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
                                   wire:model="selectedRows"
                                   value="' . $row->id . '"
                                   id="checkbox-' . $row->id . '">
                        </div>';
                })
                ->html(),
            Column::make($this->trans("Tanggal Nota"), "tr_date")
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
            Column::make($this->trans("supplier"), "partner_id")
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
            Column::make($this->trans('Total Barang'))
                ->label(function ($row) {
                    return $row->total_qty;
                })
                ->sortable(),
            Column::make($this->trans('amt'), 'total_amt')
                ->label(function ($row) {
                    return rupiah($row->total_amt);
                })
                ->sortable(),
            Column::make($this->trans('Ongkos Kirim'), 'amt_shipcost')
                ->label(function ($row) {
                    return rupiah($row->amt_shipcost);
                })
                ->sortable(),
            Column::make($this->trans("Tanggal Kirim"), "tr_date")
                ->label(function ($row) {
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery && $delivery->tr_date ? \Carbon\Carbon::parse($delivery->tr_date)->format('d-m-Y') : '';
                })
                ->sortable(),
            Column::make($this->trans("warehouse"), "warehouse")
                ->label(function ($row) {
                    // Mengambil warehouse dari DelivPicking
                    $delivPicking = DelivPicking::whereHas('DelivPacking', function($query) use ($row) {
                        $query->where('tr_code', $row->tr_code)
                              ->where('tr_type', 'SD');
                    })->first();
                    return $delivPicking ? $delivPicking->wh_code : '-';
                })
                ->sortable(),
            Column::make($this->trans("Status"), "status")
                ->label(function ($row) {
                    // Cek apakah order dibatalkan
                    if ($row->status_code == Status::CANCEL) {
                        return 'Batal';
                    }

                    // Cek apakah sudah ada delivery
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery ? 'Terkirim' : 'Belum';
                })
                ->sortable(),
            Column::make($this->trans(''), 'id')
                ->hideIf(true),
                // ->format(function ($value, $row, Column $column) {
                //     return view('layout.customs.data-table-action', [
                //         'row' => $row,
                //         'custom_actions' => [],
                //         'enable_this_row' => false,
                //         'allow_details' => false,
                //         'allow_edit' => false,
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
                $builder->where('tr_code', 'like', '%' . strtoupper($value) . '%');
            }, true),
            $this->createTextFilter($this->trans("supplier"), 'name', 'Cari Custommer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            SelectFilter::make($this->trans("Tipe Penjualan"), 'sales_type')
                ->options([
                    ''          => 'Semua',
                    'O'    => 'Mobil',
                    'I' => 'Motor',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('sales_type', $value);
                    }
                }),
            SelectFilter::make($this->trans("shipping status"))
                ->options([
                    ''  => 'Semua',
                    '1' => 'Terkirim',
                    '0' => 'Belum Terkirim',
                    '2' => 'Nota Batal',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '1') {
                        $builder->whereHas('DelivHdr', function ($query) {
                            $query->where('tr_type', 'SD');
                        });
                    } elseif ($value === '0') {
                        $builder->whereDoesntHave('DelivHdr', function ($query) {
                            $query->where('tr_type', 'SD');
                        })->where('status_code', '!=', Status::CANCEL);
                    } elseif ($value === '2') {
                        $builder->where('status_code', Status::CANCEL);
                    }
                }),
        ];
    }

    public function bulkActions(): array
    {
        // Bulk actions dipindah ke custom filters
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
        return is_array($this->selectedRows) ? count($this->selectedRows) : 0;
    }



    /**
     * Updated when selectedRows changes (automatically by wire:model)
     * Optimized untuk client-side processing
     */
    public function updatedSelectedRows()
    {
        // Hanya dispatch jika bukan dalam batch processing
        if (!$this->batchProcessing) {
            $this->dispatch('selectionUpdated');
        }
    }    /**
     * Method untuk refresh selection count di custom filters
     */
    public function refreshCustomFilters()
    {
        // Method untuk trigger refresh custom filters area
        $this->dispatch('refresh-custom-filters');
    }

    /**
     * Validate selections before processing - untuk client-side validation
     */
    public function validateSelections()
    {
        $selectedCount = is_array($this->selectedRows) ? count($this->selectedRows) : 0;

        if ($selectedCount === 0) {
            $this->dispatch('error', 'Silakan pilih minimal satu nota terlebih dahulu');
            return false;
        }

        return true;
    }

    /**
     * Get real-time processing status untuk client-side updates
     */
    public function getProcessingStatus()
    {
        return [
            'isProcessing' => $this->batchProcessing,
            'selectedCount' => $this->selectedCount,
        ];
    }

    public function setDeliveryDate()
    {
        // Set batch processing flag
        $this->batchProcessing = true;

        // Validasi tanggal kirim dan warehouse
        if (empty($this->tanggalKirim)) {
            $this->batchProcessing = false;
            $this->dispatch('error', 'Silakan pilih tanggal kirim terlebih dahulu');
            return;
        }

        if (empty($this->warehouse)) {
            $this->batchProcessing = false;
            $this->dispatch('error', 'Silakan pilih warehouse terlebih dahulu');
            return;
        }

        // Validasi tanggal kirim tidak boleh lebih besar dari tanggal sekarang
        $deliveryDate = Carbon::parse($this->tanggalKirim);
        $today = Carbon::now()->startOfDay();

        if ($deliveryDate->gt($today)) {
            $this->batchProcessing = false;
            $this->dispatch('error', 'Tanggal kirim tidak boleh lebih besar dari tanggal sekarang.');
            return;
        }

        $selectedOrderIds = $this->selectedRows;
        if (count($selectedOrderIds) === 0) {
            $this->batchProcessing = false;
            $this->dispatch('error', 'Silakan pilih minimal satu nota untuk dikirim.');
            return;
        }

        $selectedOrders = OrderHdr::whereIn('id', $selectedOrderIds)->get();
        $warehouse = ConfigConst::where('str1', $this->warehouse)->first();

        if (!$warehouse) {
            $this->batchProcessing = false;
            $this->dispatch('error', 'Warehouse tidak ditemukan.');
            return;
        }

        $successCount = 0;
        $successOrders = [];
        $failedOrders = [];

        // Proses setiap order secara individual
        foreach ($selectedOrders as $order) {
            $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();
            $hasStockError = false;
            $orderStockErrors = [];

            // Validasi stok untuk order ini (skip untuk material JASA)
            foreach ($orderDetails as $detail) {
                // Skip validasi stok jika material category adalah JASA
                $material = Material::find($detail->matl_id);
                $isJasa = $material && strtoupper(trim($material->category ?? '')) === 'JASA';

                if (!$isJasa) {
                    $totalStock = IvtBal::where('matl_id', $detail->matl_id)
                        ->where('matl_uom', $detail->matl_uom)
                        ->where('wh_id', $warehouse->id)
                        ->sum('qty_oh');

                    if ($totalStock < $detail->qty) {
                        $orderStockErrors[] = [
                            'matl_code' => $detail->matl_code,
                            'wh_code' => $warehouse->str1,
                            'stock' => rtrim(rtrim(number_format($totalStock, 3, '.', ''), '0'), '.'),
                            'required' => $detail->qty
                        ];
                        $hasStockError = true;
                    }
                }
            }

            // Jika ada error stok untuk order ini, skip dan catat
            if ($hasStockError) {
                $failedOrders[] = [
                    'tr_code' => $order->tr_code,
                    'stock_errors' => $orderStockErrors,
                    'errors' => [] // Untuk error non-stock
                ];
                continue;
            }

            // Jika tidak ada error stok, lanjutkan proses delivery untuk order ini
            // Persiapan array inputs
            $inputs = [
                'tr_type' => 'SD',
                'tr_code' => $order->tr_code,
                'tr_date' => $this->tanggalKirim,
                'partner_id' => $order->partner_id,
                'partner_code' => $order->partner_code,
                'wh_code' => $warehouse->str1,
                'wh_id' => $warehouse->id,
                'payment_term_id' => $order->payment_term_id,
                'payment_term' => $order->payment_term,
                'payment_due_days' => $order->payment_due_days,
                'note' => '',
                'reff_date' => null,
                'amt_shipcost' => $order->amt_shipcost ?? 0,
                'status_code' => Status::OPEN,
            ];

            // Persiapan array input_details
            $input_details = [];
            $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();

            foreach ($orderDetails as $detail) {
                $input_details[] = [
                    'matl_id' => $detail->matl_id,
                    'matl_code' => $detail->matl_code,
                    'matl_descr' => $detail->matl_descr,
                    'matl_uom' => $detail->matl_uom,
                    'qty' => $detail->qty,
                    'wh_id' => $warehouse->id,
                    'wh_code' => $warehouse->str1,
                    'reffdtl_id' => $detail->id,
                    'reffhdr_id' => $order->id,
                    'reffhdrtr_type' => $detail->OrderHdr->tr_type,
                    'reffhdrtr_code' => $order->tr_code,
                    'reffdtltr_seq' => $detail->tr_seq,
                ];
            }

            // Panggil DeliveryService dengan array inputs dan input_details
            if (!empty($input_details)) {
                // Get connection name untuk transaction
                $connectionName = Session::get('app_code') ?: config('database.default');
                DB::connection($connectionName)->beginTransaction();

                try {
                    $deliveryService = app(DeliveryService::class);
                    $result = $deliveryService->saveDelivery($inputs, $input_details);

                    // Persiapan data untuk BillingService
                    $billingHeaderData = [
                        'id' => 0,
                        'tr_type' => 'ARB',
                        'tr_code' => $order->tr_code,
                        'tr_date' => $this->tanggalKirim,
                    ];

                    // Ambil delivery_id dari hasil saveDelivery
                    $deliveryDetails = [];
                    if (!empty($result['header'])) {
                        $deliveryDetails[] = [
                            'deliv_id' => $result['header']->id,
                        ];
                    }

                    $billingService = app(BillingService::class);
                    $billingResult = $billingService->saveBilling($billingHeaderData, $deliveryDetails);

                    if (!empty($result['header'])) {
                        AuditLogService::createDeliveryKirim([$result['header']->id]);
                        // Audit log for Sales Delivery KIRIM

                        // Cek hasil billing
                        if (!empty($billingResult['billing_hdr'])) {
                            // Billing berhasil dibuat
                            // Update status_code OrderHdr menjadi SHIP tanpa mengubah updated_at dan version_number
                            OrderHdr::where('id', $order->id)
                                ->update(['status_code' => Status::SHIP]);

                            // testing rollback
                            // throw new Exception('Testing rollback set delivery date');

                            DB::connection($connectionName)->commit();

                            $successOrders[] = $order->tr_code;
                            $successCount++;
                        } else {
                            // Billing gagal dibuat
                            DB::connection($connectionName)->rollBack();
                            $failedOrders[] = [
                                'tr_code' => $order->tr_code,
                                'stock_errors' => [],
                                'errors' => ['Gagal membuat Billing']
                            ];
                        }
                    } else {
                        // Delivery gagal dibuat
                        DB::connection($connectionName)->rollBack();
                        $failedOrders[] = [
                            'tr_code' => $order->tr_code,
                            'stock_errors' => [],
                            'errors' => ['Gagal membuat Delivery']
                        ];
                    }
                } catch (Exception $e) {
                    DB::connection($connectionName)->rollBack();
                    Log::error("Method setDeliveryDate Error for Order {$order->tr_code}: " . $e->getMessage());
                    $failedOrders[] = [
                        'tr_code' => $order->tr_code,
                        'stock_errors' => [],
                        'errors' => ['Error: ' . $e->getMessage()]
                    ];
                }
            }
        }

        // Tampilkan hasil dengan detail
        $this->showProcessResults($successOrders, $failedOrders, $successCount);

        // Reset batch processing flag
        $this->batchProcessing = false;

        // Clear selections after successful completion
        $this->selectedRows = [];
    }

    /**
     * Tampilkan hasil proses delivery dengan detail
     */
    private function showProcessResults($successOrders, $failedOrders, $successCount)
    {
        $message = '';
        $type = 'info';

        if ($successCount > 0 && empty($failedOrders)) {
            // Semua berhasil
            $message = '<strong>Berhasil!</strong><br><br>';
            $message .= $successCount . ' Sales Delivery berhasil dibuat:<br>';
            $message .= '• ' . implode('<br>• ', $successOrders);
            $type = 'success';
        } elseif ($successCount > 0 && !empty($failedOrders)) {
            // Sebagian berhasil
            $message = '<strong>Hasil Proses Delivery</strong><br><br>';
            $message .= '<strong>✅ Berhasil (' . $successCount . ' nota):</strong><br>';
            $message .= '• ' . implode('<br>• ', $successOrders) . '<br><br>';

            $message .= '<strong>❌ Gagal (' . count($failedOrders) . ' nota):</strong><br>';
            foreach ($failedOrders as $failed) {
                $message .= $this->formatFailedOrder($failed);
            }
            $type = 'warning';
        } elseif (empty($successOrders) && !empty($failedOrders)) {
            // Semua gagal
            $message = '<strong>Gagal!</strong><br><br>';
            $message .= 'Semua nota gagal diproses:<br><br>';
            foreach ($failedOrders as $failed) {
                $message .= $this->formatFailedOrder($failed);
            }
            $type = 'error';
        }

        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message,
            'width' => '800px'
        ]);
    }

    /**
     * Format failed order dengan tabel untuk stock errors
     */
    private function formatFailedOrder($failed)
    {
        $output = '<div style="margin-bottom: 20px;">';
        $output .= '<div style="font-weight: bold; margin-bottom: 10px; color: #dc3545;">• ' . htmlspecialchars($failed['tr_code']) . ':</div>';

        // Tampilkan stock errors dalam tabel
        if (!empty($failed['stock_errors']) && count($failed['stock_errors']) > 0) {
            $output .= '<div style="margin-top: 8px;">';
            $output .= '<table style="width: 100%; border-collapse: collapse; margin: 0; font-size: 0.8rem; border: 1px solid #dee2e6;">';
            $output .= '<thead>';
            $output .= '<tr style="background-color: #f8f9fa;">';
            $output .= '<th style="padding: 6px 8px; text-align: center; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Barang</th>';
            $output .= '<th style="padding: 6px 8px; text-align: center; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Gudang</th>';
            $output .= '<th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Stok</th>';
            $output .= '<th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Butuh</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';

            foreach ($failed['stock_errors'] as $error) {
                $output .= '<tr>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; font-size: 0.8rem;">' . htmlspecialchars($error['matl_code']) . '</td>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; font-size: 0.8rem;">' . htmlspecialchars($error['wh_code']) . '</td>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; text-align: right; font-size: 0.8rem;">' . htmlspecialchars($error['stock']) . '</td>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; text-align: right; font-size: 0.8rem;">' . htmlspecialchars($error['required']) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
        }

        // Tampilkan error lainnya (non-stock)
        if (!empty($failed['errors']) && count($failed['errors']) > 0) {
            foreach ($failed['errors'] as $error) {
                $output .= '<div style="margin-top: 8px; color: #dc3545;">' . htmlspecialchars($error) . '</div>';
            }
        }

        $output .= '</div>';
        return $output;
    }

    public function cancelDeliveryDate()
    {
        // Set batch processing flag
        $this->batchProcessing = true;

        $selectedOrderIds = $this->selectedRows;
        if (count($selectedOrderIds) > 0) {
            // Ambil tr_code dari OrderHdr yang terpilih
            $selectedTrCodes = OrderHdr::whereIn('id', $selectedOrderIds)
                ->pluck('tr_code')
                ->toArray();

            // Validasi apakah ada delivery yang sudah dibuat
            $delivHdrs = DelivHdr::where('tr_type', 'SD')
                ->whereIn('tr_code', $selectedTrCodes)
                ->get();

            if ($delivHdrs->isEmpty()) {
                $this->batchProcessing = false;
                $this->dispatch('error', 'Tidak ada data pengiriman yang dapat dibatalkan');
                $this->selectedRows = [];
                return;
            }

            // Proses setiap delivery secara individual
            $deliveryService = app(DeliveryService::class);
            $billingService = app(BillingService::class);
            $deletedCount = 0;
            $successOrders = [];
            $failedOrders = [];
            $successOrderIds = [];

            // Get connection name untuk transaction
            $connectionName = Session::get('app_code') ?: config('database.default');

            foreach ($delivHdrs as $delivHdr) {
                // Cek apakah billing sudah di-print atau sudah ada pembayaran untuk nota ini
                $billing = BillingHdr::where('tr_code', $delivHdr->tr_code)->first();

                if ($billing) {
                    // if ($billing->print_date) {
                    //     // Nota ini tidak bisa dibatalkan karena sudah ditagih
                    //     $failedOrders[] = [
                    //         'tr_code' => $delivHdr->tr_code,
                    //         'reason' => 'Sudah ditagih (Tanggal: ' . $billing->print_date . ')'
                    //     ];
                    //     continue;
                    // }

                    if ($billing->amt_reff > 0) {
                        // Nota ini tidak bisa dibatalkan karena sudah ada pembayaran
                        $failedOrders[] = [
                            'tr_code' => $delivHdr->tr_code,
                            'reason' => 'Sudah ada pembayaran (Sebanyak: ' . number_format($billing->amt_reff, 0, ',', '.') . ')'
                        ];
                        continue;
                    }
                }

                // Get connection name untuk transaction
                DB::connection($connectionName)->beginTransaction();

                try {
                    // Simpan ID sebelum penghapusan untuk audit log
                    $delivId = $delivHdr->id;

                    // Audit log for BATAL KIRIM - dibuat SEBELUM penghapusan data
                    AuditLogService::createDeliveryBatalKirim([$delivId]);

                    $deliveryService->delDelivery($delivHdr->tr_type, $delivHdr->id);
                    $billingService->delBilling($delivHdr->billhdr_id);

                    // Get OrderHdr ID from tr_code relationship since order_id field doesn't exist
                    $orderHdr = OrderHdr::where('tr_code', $delivHdr->tr_code)->where('tr_type', 'SO')->first();
                    if ($orderHdr) {
                        // Update status OrderHdr kembali ke PRINT tanpa mengubah updated_at dan version_number
                        OrderHdr::where('id', $orderHdr->id)
                            ->update(['status_code' => Status::PRINT]);
                        $successOrderIds[] = $orderHdr->id;
                    }

                    // testing rollback
                    // throw new Exception('Testing rollback cancel delivery date');

                    DB::connection($connectionName)->commit();

                    $successOrders[] = $delivHdr->tr_code;
                    $deletedCount++;
                } catch (Exception $e) {
                    DB::connection($connectionName)->rollBack();
                    $failedOrders[] = [
                        'tr_code' => $delivHdr->tr_code,
                        'reason' => 'Error: ' . $e->getMessage()
                    ];
                }
            }

            // Tampilkan hasil dengan detail
            $this->showBatalKirimResults($successOrders, $failedOrders, $deletedCount);

            // Reset batch processing flag
            $this->batchProcessing = false;
            // $this->dispatch('refresh-page');
        }
    }

    /**
     * Tampilkan hasil proses BATAL KIRIM dengan detail
     */
    private function showBatalKirimResults($successOrders, $failedOrders, $deletedCount)
    {
        $message = '';
        $type = 'info';

        if ($deletedCount > 0 && empty($failedOrders)) {
            // Semua berhasil
            $message = '<strong>Berhasil!</strong><br><br>';
            $message .= $deletedCount . ' pengiriman berhasil dibatalkan:<br>';
            $message .= '• ' . implode('<br>• ', $successOrders);
            $type = 'success';
        } elseif ($deletedCount > 0 && !empty($failedOrders)) {
            // Sebagian berhasil
            $message = '<strong>Hasil Proses Batal Kirim</strong><br><br>';
            $message .= '<strong>✅ Berhasil (' . $deletedCount . ' nota):</strong><br>';
            $message .= '• ' . implode('<br>• ', $successOrders) . '<br><br>';

            $message .= '<strong>❌ Gagal (' . count($failedOrders) . ' nota):</strong><br>';
            foreach ($failedOrders as $failed) {
                $message .= '• ' . $failed['tr_code'] . ': ' . $failed['reason'] . '<br>';
            }
            $type = 'warning';
        } elseif (empty($successOrders) && !empty($failedOrders)) {
            // Semua gagal
            $message = '<strong>Gagal!</strong><br><br>';
            $message .= 'Nota gagal dibatalkan:<br>';
            foreach ($failedOrders as $failed) {
                $message .= '• ' . $failed['tr_code'] . ': ' . $failed['reason'] . '<br>';
            }
            $type = 'error';
        }

        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message
        ]);

        // Hanya clear selection jika semua berhasil atau semua gagal
        if (empty($failedOrders) || empty($successOrders)) {
            $this->selectedRows = [];
        }
    }

    public function cancel()
    {
        // Set batch processing flag
        $this->batchProcessing = true;

        $selectedOrderIds = $this->selectedRows;
        if (count($selectedOrderIds) > 0) {
            // Ambil tr_code dari OrderHdr yang terpilih
            $selectedTrCodes = OrderHdr::whereIn('id', $selectedOrderIds)
                ->pluck('tr_code')
                ->toArray();

            // Validasi jika ada status SHIP
            $shippedOrders = OrderHdr::whereIn('id', $selectedOrderIds)
                ->where('status_code', Status::SHIP)
                ->count();

            if ($shippedOrders > 0) {
                $this->batchProcessing = false;
                $this->dispatch('error', 'Tidak bisa membatalkan pesanan barang yang sudah dikirim');
                return;
            }

            // Get connection name untuk transaction
            $connectionName = Session::get('app_code') ?: config('database.default');

            $successCount = 0;
            $failedOrders = [];

            // Proses setiap order secara individual
            foreach ($selectedOrderIds as $orderId) {
                DB::connection($connectionName)->beginTransaction();

                try {
                    $orderHdr = OrderHdr::find($orderId);
                    if (!$orderHdr) {
                        throw new Exception("Order dengan ID {$orderId} tidak ditemukan");
                    }

                    // Validasi status SHIP untuk order ini
                    if ($orderHdr->status_code === Status::SHIP) {
                        throw new Exception("Order {$orderHdr->tr_code} sudah dikirim dan tidak bisa dibatalkan");
                    }

                    $orderDtls = OrderDtl::where('trhdr_id', $orderId)->get();

                    foreach ($orderDtls as $orderDtl) {
                        // Kembalikan qty_fgi ke IvtBal dengan batch_code null
                        $ivtBal = IvtBal::where('matl_id', $orderDtl->matl_id)
                            ->where('matl_uom', $orderDtl->matl_uom)
                            ->where('wh_id', 0)
                            ->where(function($query) {
                                $query->whereNull('batch_code')
                                      ->orWhere('batch_code', '');
                            })
                            ->first();

                        if ($ivtBal) {
                            $ivtBal->qty_fgi -= $orderDtl->qty;
                            $ivtBal->save();
                        }
                    }

                    // Update status to CANCEL
                    $orderHdr->status_code = Status::CANCEL;
                    $orderHdr->save();

                    // testing rollback
                    // throw new Exception('Testing rollback cancel order');

                    DB::connection($connectionName)->commit();
                    $successCount++;
                } catch (Exception $e) {
                    DB::connection($connectionName)->rollBack();
                    $failedOrders[] = [
                        'order_id' => $orderId,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            // Reset batch processing flag
            $this->batchProcessing = false;

            // Tampilkan hasil
            if ($successCount > 0) {
                $this->selectedRows = [];
                if (count($failedOrders) > 0) {
                    $this->dispatch('warning', "{$successCount} pesanan berhasil dibatalkan, " . count($failedOrders) . " gagal.");
                } else {
                    $this->dispatch('success', ['Pesanan berhasil dibatalkan']);
                }
            } else {
                $this->dispatch('error', 'Gagal membatalkan pesanan. Silakan cek log untuk detail.');
            }
        } else {
            $this->batchProcessing = false;
        }
    }

    public function unCancel()
    {
        // Set batch processing flag
        $this->batchProcessing = true;

        $selectedOrderIds = $this->selectedRows;
        if (count($selectedOrderIds) > 0) {
            // Get connection name untuk transaction
            $connectionName = Session::get('app_code') ?: config('database.default');

            $successCount = 0;
            $failedOrders = [];

            // Proses setiap order secara individual
            foreach ($selectedOrderIds as $orderId) {
                DB::connection($connectionName)->beginTransaction();

                try {
                    $orderHdr = OrderHdr::find($orderId);
                    if (!$orderHdr) {
                        throw new Exception("Order dengan ID {$orderId} tidak ditemukan");
                    }

                    // Validasi status CANCEL untuk order ini
                    if ($orderHdr->status_code !== Status::CANCEL) {
                        throw new Exception("Order {$orderHdr->tr_code} tidak dalam status CANCEL");
                    }

                    $orderDtls = OrderDtl::where('trhdr_id', $orderId)->get();

                    foreach ($orderDtls as $orderDtl) {
                        // Kembalikan qty_fgi ke IvtBal dengan batch_code null
                        $ivtBal = IvtBal::where('matl_id', $orderDtl->matl_id)
                            ->where('matl_uom', $orderDtl->matl_uom)
                            ->where('wh_id', 0)
                            ->where(function($query) {
                                $query->whereNull('batch_code')
                                      ->orWhere('batch_code', '');
                            })
                            ->first();

                        if ($ivtBal) {
                            $ivtBal->qty_fgi += $orderDtl->qty;
                            $ivtBal->save();
                        }
                    }

                    // Update status to PRINT
                    $orderHdr->status_code = Status::PRINT;
                    $orderHdr->save();

                    // testing rollback
                    // throw new Exception('Testing rollback uncancel order');

                    DB::connection($connectionName)->commit();
                    $successCount++;
                } catch (Exception $e) {
                    DB::connection($connectionName)->rollBack();
                    $failedOrders[] = [
                        'order_id' => $orderId,
                        'reason' => $e->getMessage()
                    ];
                }
            }

            // Reset batch processing flag
            $this->batchProcessing = false;

            // Tampilkan hasil
            if ($successCount > 0) {
                $this->selectedRows = [];
                if (count($failedOrders) > 0) {
                    $this->dispatch('showAlert', [
                        'type' => 'warning',
                        'message' => "{$successCount} pesanan berhasil dikembalikan, " . count($failedOrders) . " gagal."
                    ]);
                } else {
                    $this->dispatch('showAlert', [
                        'type' => 'success',
                        'message' => 'Pesanan berhasil dikembalikan dan stok diperbarui'
                    ]);
                }
            } else {
                $this->dispatch('error', 'Gagal mengembalikan pesanan. Silakan cek log untuk detail.');
            }
        } else {
            $this->batchProcessing = false;
        }
    }

    /**
     * Handle method calls yang gagal dengan graceful error handling
     */
    public function exception($e, $stopPropagation)
    {
        // Reset batch processing flag jika ada error
        $this->batchProcessing = false;

        // Log error untuk debugging
        \Illuminate\Support\Facades\Log::error('Sales Delivery error: ' . $e->getMessage(), [
            'selectedRows' => $this->selectedRows,
            'trace' => $e->getTraceAsString()
        ]);

        // Dispatch error ke client
        $this->dispatch('error', 'Terjadi kesalahan: ' . $e->getMessage());
    }
}
