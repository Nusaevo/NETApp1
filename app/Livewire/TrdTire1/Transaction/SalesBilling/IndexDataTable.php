<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl, OrderHdr, BillingHdr};
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        return BillingHdr::with(['Partner'])
            ->where('billing_hdrs.tr_type', 'ARB')
            ->whereIn('billing_hdrs.status_code', [Status::ACTIVE, Status::PRINT, Status::OPEN]);

    }

    public function columns(): array
    {
        return [
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
            Column::make($this->trans("date"), "tr_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Tgl. Kirim"), "tr_date")
                ->label(function ($row) {
                    $delivery = DelivHdr::where('tr_type', 'SD')
                        ->where('tr_code', $row->tr_code)
                        ->first();
                    return $delivery ? $delivery->tr_date : '';
                })
                ->sortable(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make('currency', "curr_rate")
                ->hideIf(true)
                ->sortable(),
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
            Column::make($this->trans("Tanggal Tagih"), "print_date")
                ->searchable()
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
            SelectFilter::make($this->trans("Sales type"), 'sales_type')
                ->options([
                    ''          => 'Semua',
                    'O'    => 'Motor',
                    'I' => 'Mobil',
                ])
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
            $this->createTextFilter('Nomor Nota', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(tr_code)'), 'like', '%' . strtoupper($value) . '%');
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
            'setDeliveryDate' => 'Set Tanggal Penagihan',
            'print' => 'Cetak',
        ];
    }

    public function setDeliveryDate()
    {
        if (count($this->getSelected()) > 0) {
            $selectedItems = BillingHdr::whereIn('id', $this->getSelected())
                ->get(['tr_code as nomor_nota', 'partner_id'])
                ->map(function ($billing) {
                    $delivery = DelivHdr::where('tr_type', 'ARB')
                        ->where('tr_code', $billing->tr_code)
                        ->first();
                    return [
                        'nomor_nota' => $billing->nomor_nota,
                        'nama' => $billing->Partner->name,
                        'kota' => $billing->Partner->city,
                        'tr_date' => $delivery ? $delivery->tr_date : null,
                    ];
                })
                ->toArray();

            $this->dispatch('openDeliveryDateModal', orderIds: $this->getSelected(), selectedItems: $selectedItems);
            $this->dispatch('submitDeliveryDate');
        }
    }

    public function submitDeliveryDate()
    {
        $selectedOrderIds = $this->getSelected();
        if (count($selectedOrderIds) > 0) {
            BillingHdr::whereIn('id', $selectedOrderIds)->update(['print_date' => $this->tr_date]);

            $this->clearSelected();
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => 'Tanggal penagihan berhasil diatur'
            ]);
        }
    }

    public function print()
    {
        $selectedOrderIds = $this->getSelected();
        if (count($selectedOrderIds) > 0) {
            $selectedOrders = BillingHdr::whereIn('id', $selectedOrderIds)->get();

            // Update status to PRINT
            BillingHdr::whereIn('id', $selectedOrderIds)->update(['status_code' => \App\Enums\TrdTire1\Status::PRINT]);

            // Clear selected items
            $this->clearSelected();

            // Dispatch event to show success message
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => 'Nota berhasil dicetak'
            ]);

            // Redirect to print view
            return redirect()->route('TrdTire1.Transaction.SalesBilling.PrintPdf', [
                // 'orderIds' => encryptWithSessionKey($selectedOrderIds),
                'action' => encryptWithSessionKey('Print'),
                'objectId' => encryptWithSessionKey(json_encode($selectedOrderIds)),
            ]);
        }
        $this->dispatch('error', 'Nota belum dipilih.');

    }

}
