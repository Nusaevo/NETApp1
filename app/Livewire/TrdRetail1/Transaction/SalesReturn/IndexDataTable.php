<?php

namespace App\Livewire\TrdRetail1\Transaction\SalesReturn;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\TextFilter};
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl, ReturnHdr, ReturnDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ReturnHdr::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_id', 'desc');
    }

    public function builder(): Builder
    {
        return ReturnHdr::with('ReturnDtl', 'Partner', 'ExchangeOrder')
            ->where('return_hdrs.tr_type', 'SR')
            ->where('return_hdrs.status_code', Status::OPEN)
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
                        return '<a href="' . route($this->redirectAppCode.'.Transaction.SalesReturn.Detail', [
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
                        return '<a href="' . route($this->redirectAppCode.'.Master.Partner.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->partner_id)
                        ]) . '">' . $row->Partner->name . '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
             Column::make($this->trans('matl_code'), 'id')
                ->label(function ($row) {
                    return $row->matl_codes;
                })
                ->format(function ($value, $row) {
                    if (!empty($row->matl_codes)) {
                        $items = explode(', ', $row->matl_codes);
                        $formatted = array_map(function($item) {
                            return '<span class="badge bg-primary text-white me-1">' . $item . '</span>';
                        }, $items);
                        return implode('', $formatted);
                    }
                    return '-';
                })
                ->html(),
            Column::make($this->trans("return_qty"), "total_qty")
                ->label(function ($row) {
                    return $row->total_qty;
                })
                ->sortable(),
            Column::make($this->trans("return_amt"), "total_amt")
                ->label(function ($row) {
                    return rupiah($row->total_amt);
                })
                ->sortable(),
            Column::make($this->trans("exchange_qty"), "id")
                ->label(function ($row) {
                    return $row->exchange_qty;
                }),
            Column::make($this->trans("exchange_amt"), "id")
                ->label(function ($row) {
                    return rupiah($row->exchange_amt);
                }),
            Column::make($this->trans("exchange_items"), "id")
                ->label(function ($row) {
                    return $row->exchange_matl_codes;
                })
                ->format(function ($value, $row) {
                    if (!empty($row->exchange_matl_codes)) {
                        $items = explode(', ', $row->exchange_matl_codes);
                        $formatted = array_map(function($item) {
                            return '<span class="badge bg-info text-dark me-1">' . $item . '</span>';
                        }, $items);
                        return implode('', $formatted);
                    }
                    return '-';
                })
                ->html(),
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
                                'route' => route($this->redirectAppCode.'.Transaction.SalesReturn.PrintPdf', [
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
                        ->from('return_dtls')
                        ->whereRaw('return_dtls.return_hdr_id = return_hdrs.id')
                        ->where(DB::raw('UPPER(return_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            $this->createTextFilter('Barang Exchange', 'exchange_matl_code', 'Cari Barang Exchange', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('order_dtls')
                        ->join('order_hdrs', 'order_hdrs.id', '=', 'order_dtls.trhdr_id')
                        ->where('order_hdrs.tr_type', 'SOR')
                        ->whereRaw('order_hdrs.partner_id = return_hdrs.partner_id')
                        ->whereRaw('order_hdrs.tr_date = return_hdrs.tr_date')
                        ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
        ];
    }
}
