<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking, DelivPicking};
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
        return DelivHdr::with(['DelivPacking.DelivPickings', 'Partner'])
            ->where('deliv_hdrs.tr_type', 'PD')
            ->orderBy('deliv_hdrs.updated_at', 'desc')
            ->orderBy('deliv_hdrs.tr_date', 'desc')
            ->orderBy('deliv_hdrs.tr_code', 'desc');
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
            // Column::make('currency', "curr_rate")
            //     ->hideIf(true)
            //     ->sortable(),
            Column::make($this->trans("Nomor Surat Jalan"), "tr_code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->appCode . '.Transaction.PurchaseDelivery.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey((string)$row->id)  // Ensure it's a string
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
            Column::make($this->trans('Total Barang'), 'total_qty')
                ->label(function ($row) {
                    $totalQty = DelivPacking::where('tr_code', $row->tr_code)->sum('qty');
                    return round($totalQty);
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
            DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
                $builder->where('deliv_hdrs.tr_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('deliv_hdrs.tr_date', '<=', $value);
            }),
            $this->createTextFilter('Nomor Surat Jalan', 'tr_code', 'Cari Nomor Nota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Supplier', 'name', 'Cari Supplier', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            SelectFilter::make('Tipe Kendaraan', 'vehicle_type')
                ->options([
                    '' => 'Semua',
                    'O' => 'Mobil',
                    'I' => 'Motor',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->whereExists(function ($query) use ($value) {
                            $query->select(DB::raw(1))
                                ->from('deliv_packings')
                                ->join('order_hdrs', 'order_hdrs.tr_code', '=', 'deliv_packings.reffhdrtr_code')
                                ->whereRaw('deliv_packings.trhdr_id = deliv_hdrs.id')
                                ->where('order_hdrs.sales_type', $value);
                        });
                    }
                }),
        ];
    }
}
