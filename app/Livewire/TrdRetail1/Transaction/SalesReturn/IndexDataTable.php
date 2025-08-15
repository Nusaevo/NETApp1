<?php

namespace App\Livewire\TrdRetail1\Transaction\SalesReturn;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\TextFilter};
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_id', 'desc');
    }

    public function builder(): Builder
    {
        return OrderHdr::with('OrderDtl', 'Partner')
            ->where('order_hdrs.tr_type', 'SR')
            ->where('order_hdrs.status_code', Status::OPEN);
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->sortable(),
            Column::make($this->trans("tr_id"), "tr_id")
                ->format(function ($value, $row) {
                        return '<a href="' . route($this->appCode.'.Transaction.SalesReturn.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id)
                        ]) . '">' . $row->tr_id . '</a>';
                })
                ->html(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("customer"), "partner_id")
                ->format(function ($value, $row) {
                    if ($row->partner_id) {
                        return '<a href="' . route($this->appCode.'.Master.Partner.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->partner_id)
                        ]) . '">' . $row->Partner->name . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
             Column::make($this->trans('matl_code'), 'id')
                ->format(function ($value, $row) {
                    // Ambil orderDtl untuk return items (qty negatif)
                    $orderDtl = OrderDtl::where('tr_id', $row->tr_id)
                        ->where('tr_type', $row->tr_type)
                        ->where('qty', '<', 0) // Return items have negative qty
                        ->orderBy('id')
                        ->get();

                    // Ambil cuma matl_code
                    $matlCodes = $orderDtl->pluck('matl_code');

                    // Gabungkan pakai koma
                    return $matlCodes->implode(', ');
                }),
            Column::make($this->trans("return_qty"), "total_qty")
                ->label(function ($row) {
                    // Hitung total qty return (absolute value dari qty negatif)
                    $returnQty = OrderDtl::where('tr_id', $row->tr_id)
                        ->where('tr_type', $row->tr_type)
                        ->where('qty', '<', 0)
                        ->sum(DB::raw('ABS(qty)'));
                    return $returnQty;
                })
                ->sortable(),
            Column::make($this->trans("exchange_qty"), "exchange_qty")
                ->label(function ($row) {
                    // Hitung total qty exchange (qty positif)
                    $exchangeQty = OrderDtl::where('tr_id', $row->tr_id)
                        ->where('tr_type', $row->tr_type)
                        ->where('qty', '>', 0)
                        ->sum('qty');
                    return $exchangeQty;
                }),
            Column::make($this->trans("amt"), "total_amt")
                ->label(function ($row) {
                    return rupiah($row->total_amt);
                })
                ->sortable(),
            Column::make($this->trans('status'), "status_code")
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'custom_actions' => [
                            [
                                'label' => 'Print',
                                'route' => route($this->appCode.'.Transaction.SalesReturn.PrintPdf', [
                                    'action' => encryptWithSessionKey('Edit'),
                                    'objectId' => encryptWithSessionKey($row->id)
                                ]),
                                'icon' => 'bi bi-printer'
                            ],
                        ],
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => true,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'permissions' => $this->permissions,

                    ]);
                }),
        ];
    }

    public function filters(): array
    {
        return [
            $this->createTextFilter('Customer', 'name', 'Cari Customer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            $this->createTextFilter('Barang Return', 'matl_code', 'Cari Barang Return', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('order_dtls')
                        ->whereRaw('order_dtls.tr_id = order_hdrs.tr_id')
                        ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
                        ->where('order_dtls.tr_type', 'SR')
                        ->where('order_dtls.qty', '<', 0); // Return items
                });
            }),
            $this->createTextFilter('Barang Exchange', 'exchange_matl_code', 'Cari Barang Exchange', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('order_dtls')
                        ->whereRaw('order_dtls.tr_id = order_hdrs.tr_id')
                        ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
                        ->where('order_dtls.tr_type', 'SR')
                        ->where('order_dtls.qty', '>', 0); // Exchange items
                });
            }),
        ];
    }
}
