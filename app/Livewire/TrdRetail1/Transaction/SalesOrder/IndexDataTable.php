<?php

namespace App\Livewire\TrdRetail1\Transaction\SalesOrder;

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
        // Hapus duplicate sorting - hanya gunakan satu
        $this->setDefaultSort('tr_date', 'desc');
    }

    public function builder(): Builder
    {
        return OrderHdr::with('OrderDtl', 'Partner')
            ->where('order_hdrs.tr_type', 'SO')
            ->where('order_hdrs.status_code', Status::OPEN)
            ->orderBy('tr_date', 'desc')
            ->orderBy('tr_id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->sortable(),
            Column::make($this->trans("tr_id"), "tr_id")
                ->format(function ($value, $row) {
                        return '<a href="' . route($this->appCode.'.Transaction.SalesOrder.Detail', [
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
                    // Ambil orderDtl
                    $orderDtl = OrderDtl::where('tr_id', $row->tr_id)
                        ->where('tr_type', $row->tr_type)
                        ->orderBy('id')
                        ->get();

                    // Ambil cuma matl_code
                    $matlCodes = $orderDtl->pluck('matl_code');

                    // Gabungkan pakai koma
                    return $matlCodes->implode(', ');
                }),
            Column::make($this->trans("qty"), "total_qty")
                ->label(function ($row) {
                    return $row->total_qty;
                })
                ->sortable(),
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
                                'route' => route($this->appCode.'.Transaction.SalesOrder.PrintPdf', [
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
            $this->createTextFilter('Barang', 'matl_code', 'Cari Barang', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('order_dtls')
                        ->whereRaw('order_dtls.tr_id = order_hdrs.tr_id')
                        ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
                        ->where('order_dtls.tr_type', 'SO');
                });
            }),
        ];
    }
}
