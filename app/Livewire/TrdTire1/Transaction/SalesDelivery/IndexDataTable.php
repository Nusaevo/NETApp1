<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl, OrderHdr};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use App\Models\TrdTire1\Master\MatlUom;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire; // pastikan namespace ini diimport
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Filters\BooleanFilter;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = DelivHdr::class;
    public $bulkSelectedIds = null;


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
            ->whereIn('order_hdrs.status_code', [Status::PRINT, Status::SHIP, Status::OPEN]);
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
            Column::make($this->trans('qty'))
                ->label(function ($row) {
                    return $row->total_qty;
                })
                ->sortable(),
            Column::make($this->trans('amt'), 'total_amt')
                ->label(function ($row) {
                    return rupiah($row->total_amt);
                })
                ->sortable(),
            Column::make($this->trans("Tanggal Kirim"), "tr_date")
                ->label(function ($row) {
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery ? $delivery->tr_date : '';
                })
                ->sortable(),
            Column::make($this->trans("warehouse"), "warehouse")
                ->label(function ($row) {
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery ? $delivery->wh_code : '';
                })
                ->sortable(),
            Column::make($this->trans("Status"), "status")
                ->label(function ($row) {
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery ? 'Terkirim' : 'Belum';
                })
                ->sortable(),
            Column::make($this->trans('action'), 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'custom_actions' => [],
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
            SelectFilter::make($this->trans("sales_type"), 'sales_type')
                ->options([
                    ''          => 'Semua',
                    '0'    => 'Motor',
                    '1' => 'Mobil',
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
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '1') {
                        $builder->whereHas('DelivHdr', function ($query) {
                            $query->where('tr_type', 'SD');
                        });
                    } elseif ($value === '0') {
                        $builder->whereDoesntHave('DelivHdr', function ($query) {
                            $query->where('tr_type', 'SD');
                        });
                    }
                }),
            TextFilter::make('Nomor Nota')->filter(function (Builder $builder, string $value) {
                $builder->where('tr_code', 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter($this->trans("supplier"), 'name', 'Cari Custommer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            DateFilter::make('Tanggal Awal')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('tr_date', '>=', $value);
                }),
            DateFilter::make('Tanggal Akhir')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('tr_date', '<=', $value);
                }),
        ];
    }

    public function bulkActions(): array
    {
        return [
            'setDeliveryDate' => 'Set Tanggal Kirim',
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
                ->toArray();

            // Update status to SHIP
            OrderHdr::whereIn('id', $this->getSelected())->update(['status_code' => Status::SHIP]);

            $this->dispatch('openDeliveryDateModal', orderIds: $this->getSelected(), selectedItems: $selectedItems);
            $this->dispatch('submitDeliveryDate');
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

            // Hapus DelivDtl dengan trhdr_id yang sesuai dengan DelivHdr yang akan dihapus
            $delivHdrs = DelivHdr::where('tr_type', 'SD')
                ->whereIn('tr_code', $selectedTrCodes)
                ->get();

            foreach ($delivHdrs as $delivHdr) {
                $delivDtls = DelivDtl::where('trhdr_id', $delivHdr->id)->get();
                foreach ($delivDtls as $delivDtl) {
                    $delivDtl->delete(); // Trigger the deleting event
                }
                $delivHdr->delete();
            }
            OrderHdr::whereIn('id', $this->getSelected())->update(['status_code' => Status::PRINT]);

            DB::commit();

            $this->clearSelected();
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => 'Tanggal pengiriman berhasil dibatalkan'
            ]);
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
