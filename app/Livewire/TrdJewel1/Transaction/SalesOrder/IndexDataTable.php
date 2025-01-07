<?php

namespace App\Livewire\TrdJewel1\Transaction\SalesOrder;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\TrdJewel1\Transaction\OrderDtl;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\SysConfig1\ConfigRight;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Status;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->customRoute = '';
        $this->getPermission($this->customRoute);
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_id', 'desc');
    }

    public function builder(): Builder
    {
        return OrderHdr::with('OrderDtl', 'Partner')
            ->where('order_hdrs.tr_type', 'SO')
            ->where('order_hdrs.status_code', Status::OPEN);
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans('date'), 'tr_date')->sortable(),
            Column::make($this->trans('tr_id'), 'tr_id')
                ->format(function ($value, $row) {
                    return '<a href="' .
                        route($this->appCode . '.Transaction.SalesOrder.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id),
                        ]) .
                        '">' .
                        $row->tr_id .
                        '</a>';
                })
                ->html(),
            Column::make($this->trans('tr_type'), 'tr_type')->hideIf(true)->sortable(),
            Column::make($this->trans('customer'), 'partner_id')
                ->format(function ($value, $row) {
                    if ($row->partner_id) {
                        return '<a href="' .
                            route($this->appCode . '.Master.Partner.Detail', [
                                'action' => encryptWithSessionKey('Edit'),
                                'objectId' => encryptWithSessionKey($row->partner_id),
                            ]) .
                            '">' .
                            $row->Partner->name .
                            '</a>';
                    } else {
                        return '';
                    }
                })
                ->html(),
            Column::make($this->trans('matl_code'), 'id')
                ->format(function ($value, $row) {
                    // Manually load OrderDtl using a query
                    $orderDtl = OrderDtl::where('tr_id', $row->tr_id)
                        ->where('tr_type', $row->tr_type)
                        ->orderBy('id')
                        ->get();

                    // Generate links if data is available
                    $matlCodes = $orderDtl->pluck('matl_code', 'matl_id');
                    $links = $matlCodes->map(function ($code, $id) {
                        return '<a href="' .
                            route($this->appCode . '.Master.Material.Detail', [
                                'action' => encryptWithSessionKey('Edit'),
                                'objectId' => encryptWithSessionKey($id),
                            ]) .
                            '">' .
                            $code .
                            '</a>';
                    });

                    return $links->implode(', ');
                })
                ->html(),
            Column::make($this->trans('qty'), 'total_qty')
                ->label(function ($row) {
                    return $row->total_qty;
                })
                ->sortable(),
            Column::make($this->trans('amt'), 'total_amt')
                ->label(function ($row) {
                    return rupiah($row->total_amt);
                })
                ->sortable(),
            BooleanColumn::make($this->trans('Status'), 'status_code')->setCallback(function ($value) {
                return $value === Status::ACTIVE;
            }),
            Column::make('Actions', 'id')->format(function ($value, $row, Column $column) {
                return view('layout.customs.data-table-action', [
                    'row' => $row,
                    'custom_actions' => [
                        [
                            'label' => 'Print',
                            'route' => route($this->appCode . '.Transaction.SalesOrder.PrintPdf', [
                                'action' => encryptWithSessionKey('Edit'),
                                'objectId' => encryptWithSessionKey($row->id),
                            ]),
                            'icon' => 'bi bi-printer',
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
                    $query
                        ->select(DB::raw(1))
                        ->from('order_dtls')
                        ->whereRaw('order_dtls.tr_id = order_hdrs.tr_id')
                        ->where(DB::raw('UPPER(order_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
                        ->where('order_dtls.tr_type', 'SO');
                });
            }),
            // SelectFilter::make('Status', 'status_code')
            //     ->options([
            //         Status::OPEN => 'Open',
            //         Status::COMPLETED => 'Selesai',
            //         '' => 'Semua',
            //     ])->filter(function ($builder, $value) {
            //         if ($value === Status::ACTIVE) {
            //             $builder->where('order_hdrs.status_code', Status::ACTIVE);
            //         } else if ($value === Status::COMPLETED) {
            //             $builder->where('order_hdrs.status_code', Status::COMPLETED);
            //         } else if ($value === '') {
            //             $builder->withTrashed();
            //         }
            //     }),
            // DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
            //     $builder->where('order_hdrs.tr_date', '>=', $value);
            // }),
            // DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
            //     $builder->where('order_hdrs.tr_date', '<=', $value);
            // }),
        ];
    }
}
