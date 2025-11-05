<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{BillingHdr, DelivHdr, DelivPacking, DelivPicking, OrderDtl, OrderHdr};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use App\Models\TrdTire1\Master\MatlUom;
use App\Services\TrdTire1\{AuditLogService, BillingService, DeliveryService};
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire; // pastikan namespace ini diimport
use Illuminate\Support\Facades\DB;
use Exception;
use Rappasoft\LaravelLivewireTables\Views\Filters\BooleanFilter;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = DelivHdr::class;
    public $bulkSelectedIds = null;

    protected $listeners = ['clearSelections'];

    public function clearSelections()
    {
        $this->clearSelected();
        $this->bulkSelectedIds = null;
    }


    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'asc');
        $this->setDefaultSort('tr_code', 'asc');
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

            TextFilter::make('Nomor Nota')->filter(function (Builder $builder, string $value) {
                $builder->where('tr_code', 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter($this->trans("supplier"), 'name', 'Cari Custommer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
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
        return [
            'setDeliveryDate' => 'Kirim',
            'cancelDeliveryDate' => 'Batal Kirim',
            'cancel' => 'Cancel',
            'unCancel' => 'UnCancel',
        ];
    }

    public function setDeliveryDate()
    {
        if (count($this->getSelected()) > 0) {
            $selectedItems = OrderHdr::whereIn('id', $this->getSelected())
                ->get(['tr_code as nomor_nota', 'partner_id'])
                ->map(function ($order) {
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $order->tr_code)
                        ->first();
                    return [
                        'nomor_nota' => $order->nomor_nota,
                        'nama' => $order->Partner->name,
                        'kota' => $order->Partner->city,
                        'tr_date' => $delivery ? $delivery->tr_date : null,
                    ];
                })
                ->sortBy('nomor_nota')
                ->values()
                ->toArray();

            OrderHdr::whereIn('id', $this->getSelected())->update(['status_code' => Status::SHIP]);

            $this->dispatch('openDeliveryDateModal', orderIds: $this->getSelected(), selectedItems: $selectedItems);
            // $this->dispatch('submitDeliveryDate'); // Dihapus agar tidak auto-submit
        }
    }

    public function cancelDeliveryDate()
    {
        $selectedOrderIds = $this->getSelected();
        if (count($selectedOrderIds) > 0) {
            DB::beginTransaction();

            // Ambil tr_code dari OrderHdr yang terpilih
            $selectedTrCodes = OrderHdr::whereIn('id', $selectedOrderIds)
                ->pluck('tr_code')
                ->toArray();

            // Validasi apakah ada delivery yang sudah dibuat
            $delivHdrs = DelivHdr::where('tr_type', 'SD')
                ->whereIn('tr_code', $selectedTrCodes)
                ->get();

	            if ($delivHdrs->isEmpty()) {
	                $this->dispatch('error', 'Tidak ada data pengiriman yang dapat dibatalkan');
	                $this->clearSelections();
	                return;
	            }

            // Proses setiap delivery secara individual
            $deliveryService = app(DeliveryService::class);
            $billingService = app(BillingService::class);
            $deletedCount = 0;
            $successOrders = [];
            $failedOrders = [];
            $successOrderIds = [];

            foreach ($delivHdrs as $delivHdr) {
                // Cek apakah billing sudah di-print atau sudah ada pembayaran untuk nota ini
                $billing = BillingHdr::where('tr_code', $delivHdr->tr_code)->first();

                if ($billing) {
                    if ($billing->print_date) {
                        // Nota ini tidak bisa dibatalkan karena sudah ditagih
                        $failedOrders[] = [
                            'tr_code' => $delivHdr->tr_code,
                            'reason' => 'Sudah ditagih (Tanggal: ' . $billing->print_date . ')'
                        ];
                        continue;
                    }

                    if ($billing->amt_reff > 0) {
                        // Nota ini tidak bisa dibatalkan karena sudah ada pembayaran
                        $failedOrders[] = [
                            'tr_code' => $delivHdr->tr_code,
                            'reason' => 'Sudah ada pembayaran (amt_reff: ' . number_format($billing->amt_reff, 0, ',', '.') . ')'
                        ];
                        continue;
                    }
                }

                try {
                    // Simpan ID sebelum penghapusan untuk audit log
                    $delivId = $delivHdr->id;

                    // Audit log for BATAL KIRIM - dibuat SEBELUM penghapusan data
                    AuditLogService::createDeliveryBatalKirim([$delivId]);

                    $deliveryService->delDelivery($delivHdr->id);
                    $billingService->delBilling($delivHdr->billhdr_id);

                    $successOrders[] = $delivHdr->tr_code;
                    // Get OrderHdr ID from tr_code relationship since order_id field doesn't exist
                    $orderHdr = OrderHdr::where('tr_code', $delivHdr->tr_code)->where('tr_type', 'SO')->first();
                    if ($orderHdr) {
                        $successOrderIds[] = $orderHdr->id;
                    }
                    $deletedCount++;
                } catch (Exception $e) {
                    $failedOrders[] = [
                        'tr_code' => $delivHdr->tr_code,
                        'reason' => 'Error: ' . $e->getMessage()
                    ];
                }
            }

            // Update status OrderHdr kembali ke PRINT hanya untuk yang berhasil
            if (!empty($successOrderIds)) {
                OrderHdr::whereIn('id', $successOrderIds)->update(['status_code' => Status::PRINT]);
            }

            DB::commit();

            // Tampilkan hasil dengan detail
            $this->showBatalKirimResults($successOrders, $failedOrders, $deletedCount);
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
            $message .= 'Semua nota gagal dibatalkan:<br>';
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
            $this->clearSelected();
        }
    }

    public function cancel()
    {
        $selectedOrderIds = $this->getSelected();
        if (count($selectedOrderIds) > 0) {
            DB::beginTransaction();

            // Ambil tr_code dari OrderHdr yang terpilih
            $selectedTrCodes = OrderHdr::whereIn('id', $selectedOrderIds)
                ->pluck('tr_code')
                ->toArray();

            // Validasi jika ada status SHIP
            $shippedOrders = OrderHdr::whereIn('id', $selectedOrderIds)
                ->where('status_code', Status::SHIP)
                ->count();

            if ($shippedOrders > 0) {
                $this->dispatch('error', 'Tidak bisa membatalkan pesanan barang yang sudah dikirim');
                return;
            }

            $orderDtls = OrderDtl::whereIn('trhdr_id', function ($query) use ($selectedTrCodes) {
                $query->select('id')
                    ->from('order_hdrs')
                    ->whereIn('tr_code', $selectedTrCodes)
                    ->where('tr_type', 'SO');
            })->get();

            foreach ($orderDtls as $orderDtl) {
                $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)->first();
                if ($matlUom) {
                    $matlUom->qty_fgi -= $orderDtl->qty;
                    $matlUom->save();
                }
            }

            // Update status to CANCEL
            OrderHdr::whereIn('id', $selectedOrderIds)->update(['status_code' => Status::CANCEL]);

            DB::commit();

            $this->clearSelected();
            $this->dispatch('success', ['Pesanan berhasil dibatalkan']);
        }
    }

    public function unCancel()
    {
        $selectedOrderIds = $this->getSelected();
        if (count($selectedOrderIds) > 0) {
            DB::beginTransaction();

            // Ambil tr_code dari OrderHdr yang terpilih
            $selectedTrCodes = OrderHdr::whereIn('id', $selectedOrderIds)
                ->pluck('tr_code')
                ->toArray();

            // Ambil matl_id dari OrderDtl yang sesuai dengan tr_code
            $orderDtls = OrderDtl::whereIn('trhdr_id', function ($query) use ($selectedTrCodes) {
                $query->select('id')
                    ->from('order_hdrs')
                    ->whereIn('tr_code', $selectedTrCodes)
                    ->where('tr_type', 'SO');
            })->get();

            foreach ($orderDtls as $orderDtl) {
                // Cari matl_id pada MatlUom dan tambahkan qty_fgi
                $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)->first();
                if ($matlUom) {
                    $matlUom->qty_fgi += $orderDtl->qty;
                    $matlUom->save();
                }
            }
            OrderHdr::whereIn('id', $this->getSelected())->update(['status_code' => Status::PRINT]);

            DB::commit();

            $this->clearSelected();
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => 'Pesanan berhasil dikembalikan dan stok diperbarui'
            ]);
        }
    }
}
