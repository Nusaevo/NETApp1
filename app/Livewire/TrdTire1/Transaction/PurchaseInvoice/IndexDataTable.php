<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseInvoice;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{BillingHdr, BillingOrder};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = BillingHdr::class;
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_code', 'desc');
    }

    public function builder(): Builder
    {
        return BillingHdr::with(['BillingOrder', 'Partner'])
            ->where('billing_hdrs.tr_type', 'APB')
            ->where(function ($query) {
                $query->where('billing_hdrs.status_code', Status::OPEN)
                      ->orWhere('billing_hdrs.status_code', Status::ACTIVE);
            });
    }
    public function columns(): array
    {
        return [
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("date"), "tr_date")
                ->searchable()
                ->sortable(),
            // Column::make('currency', "curr_rate")
            //     ->hideIf(true)
            //     ->sortable(),
            Column::make($this->trans("tr_code"), "tr_code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->appCode . '.Transaction.PurchaseInvoice.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey((string)$row->id)  // Ensure it's a string
                    ]) . '">' . $row->tr_code . '</a>';
                })
                ->html(),
            Column::make($this->trans("Tanggal Invoice"), "tr_date")
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
            Column::make($this->trans('Kode Barang'), 'kode_barang')
                ->label(function ($row) {
                    // Ambil semua kode barang dari BillingOrder, pisahkan dengan koma
                    $matlCodes = BillingOrder::where('trhdr_id', $row->id)->pluck('matl_descr');
                    return $matlCodes->isNotEmpty() ? $matlCodes->implode(', ') : '-';
                })
                ->sortable(),
            Column::make($this->trans('Total Barang'), 'total_qty')
                ->label(function ($row) {
                    $totalQty = BillingOrder::where('trhdr_id', $row->id)->sum('qty');
                    return round($totalQty);
                })
                ->sortable(),
            Column::make($this->trans('Total Amount'), 'amt')
                ->format(function ($value, $row) {
                    return number_format($row->amt, 0, ',', '.');
                })
                ->sortable(),
            Column::make($this->trans('action'), 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'row' => $row,
                        'custom_actions' => [
                            // [
                            //     'label' => 'Print',
                            //     'route' => route('TrdTire1..PurchaseInvoice.PrintPdf', [
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
            DateFilter::make('Tanggal Invoice')->filter(function (Builder $builder, string $value) {
                $builder->where('billing_hdrs.tr_date', '=', $value);
            }),
            $this->createTextFilter('Nomor Invoice', 'tr_code', 'Cari Nomor Invoice', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }, true),
            $this->createTextFilter('Supplier', 'name', 'Cari Supplier', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }, true),
            $this->createTextFilter('Material', 'matl_descr', 'Cari Kode Material', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('billing_orders')
                        ->whereRaw('billing_orders.trhdr_id = billing_hdrs.id')
                        ->where(DB::raw('UPPER(billing_orders.matl_descr)'), 'like', '%' . strtoupper($value) . '%')
                        ->where('billing_orders.tr_type', 'APB');
                });
            }),
        ];
    }
}
