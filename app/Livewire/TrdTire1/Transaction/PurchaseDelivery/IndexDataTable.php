<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

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
        return DelivHdr::with(['DelivDtl', 'Partner'])
            ->where('deliv_hdrs.tr_type', 'PD')
            ->where(function ($query) {
                $query->where('deliv_hdrs.status_code', Status::OPEN)
                      ->orWhere('deliv_hdrs.status_code', Status::ACTIVE);
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
                    return '<a href="' . route($this->appCode . '.Transaction.PurchaseDelivery.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey((string)$row->id)  // Ensure it's a string
                    ]) . '">' . $row->tr_code . '</a>';
                })
                ->html(),
            Column::make($this->trans("Tanggal Surat Jalan"), "tr_date")
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
            Column::make($this->trans('Kode Barang'), 'total_item')
                    ->label(function ($row) {
                        $lastDetail = DelivDtl::where('trhdr_id', $row->id)
                            ->orderBy('tr_seq', 'desc')
                            ->first();
                        return $lastDetail ? $lastDetail->tr_seq : 0;
                    })
                    ->sortable(),
            Column::make($this->trans('Total Barang'), 'total_qty')
                ->label(function ($row) {
                    $totalQty = DelivDtl::where('trhdr_id', $row->id)->sum('qty');
                    return $totalQty;
                })
                ->sortable(),
            // Column::make($this->trans("amt"), "total_amt_in_idr")
            //     ->label(function ($row) {
            //         $totalAmt = 0;

            //         $orderDetails = DelivDtl::where('trhdr_id', $row->id)->get();

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
            DateFilter::make('Tanggal Terima Barang')->filter(function (Builder $builder, string $value) {
                $builder->where('deliv_hdrs.tr_date', '=', $value);
            }),
            $this->createTextFilter('Nomor Nota', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Supplier', 'name', 'Cari Supplier', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            $this->createTextFilter('Material', 'matl_code', 'Cari Kode Material', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('deliv_dtls')
                        ->whereRaw('deliv_dtls.tr_code = deliv_hdrs.tr_code')
                        ->where(DB::raw('UPPER(deliv_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
                        ->where('deliv_dtls.tr_type', 'PD');
                });
            }),
        ];
    }
}
