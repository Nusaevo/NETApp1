<?php

namespace App\Livewire\TrdTire1\Master\CustomerInfo;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{
    Column,
    Columns\LinkColumn,
    Filters\SelectFilter,
    Filters\TextFilter,
    Filters\DateFilter
};
use App\Models\TrdTire1\Transaction\{DelivPacking, OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use App\Models\TrdTire1\Master\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_code', 'desc');
    }

    public function builder(): Builder
    {
        $query = OrderHdr::with(['OrderDtl', 'Partner'])
            ->where('order_hdrs.tr_type', 'SO')
            ->select('order_hdrs.*');

        // Debug: cek apakah ada data SO
        $totalSO = OrderHdr::where('tr_type', 'SO')->count();
        Log::info("Total SO records: " . $totalSO);

        // Debug: cek partner yang ada
        $partners = Partner::where('grp', 'C')->count();
        Log::info("Total Customer partners: " . $partners);

        return $query;
    }

    protected function isFirstFilterApplied(Builder $query): bool
    {
        // Check if the query has only one where condition (whereRaw('1=0'))
        return count($query->getQuery()->wheres) === 1 && $query->getQuery()->wheres[0]['type'] === 'raw';
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("Tanggal Nota"), "tr_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make('currency', "curr_rate")
                ->hideIf(true)
                ->sortable(),
            Column::make('Kode Barang', 'tr_code')
                ->format(function ($value, $row) {
                    // Ambil semua kode barang dari OrderDtl berdasarkan tr_code
                    $orderDtls = OrderDtl::where('tr_code', $row->tr_code)
                        ->where('tr_type', 'SO')
                        ->get();

                    if ($orderDtls->count() > 0) {
                        return $orderDtls->pluck('matl_code')->implode(', ');
                    }
                    return '-';
                }),
            Column::make('Nama Barang', 'tr_code')
                ->format(function ($value, $row) {
                    // Ambil semua nama barang dari OrderDtl berdasarkan tr_code
                    $orderDtls = OrderDtl::where('tr_code', $row->tr_code)
                        ->where('tr_type', 'SO')
                        ->get();

                    if ($orderDtls->count() > 0) {
                        return $orderDtls->pluck('matl_descr')->implode(', ');
                    }
                    return '-';
                }),
            Column::make($this->trans('amt'), 'total_amt')
                ->label(function ($row) {
                    return rupiah($row->total_amt, false);
                })
                ->sortable(),
            Column::make('Diskon', 'tr_code')
                ->format(function ($value, $row) {
                    // Ambil semua diskon dari OrderDtl berdasarkan tr_code
                    $orderDtls = OrderDtl::where('tr_code', $row->tr_code)
                        ->where('tr_type', 'SO')
                        ->get();

                    if ($orderDtls->count() > 0) {
                        $discounts = $orderDtls->pluck('disc_pct')->map(function($disc) {
                            return $disc ? $disc . '%' : '0%';
                        });
                        return $discounts->implode(', ');
                    }
                    return '-';
                }),
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
            SelectFilter::make('Customer', 'customer_filter')
                ->options(['' => 'Pilih Customer'] +
                    Partner::whereIn('id', function($query) {
                        $query->select('partner_id')
                            ->from('order_hdrs')
                            ->where('tr_type', 'SO')
                            ->whereNotNull('partner_id')
                            ->distinct();
                    })
                    ->where('grp', 'C')
                    ->whereNull('deleted_at')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    if ($this->isFirstFilterApplied($builder)) {
                        $builder->getQuery()->wheres = [];
                    }
                    if (!empty($value)) {
                        $builder->where('order_hdrs.partner_id', $value);
                    }
                }),

            SelectFilter::make('Kode Barang', 'material_filter')
                ->options(['' => 'Pilih Kode Barang'] +
                    OrderDtl::select('matl_code')
                        ->whereNotNull('matl_code')
                        ->where('matl_code', '!=', '')
                        ->where('tr_type', 'SO')
                        ->distinct()
                        ->orderBy('matl_code')
                        ->pluck('matl_code', 'matl_code')
                        ->toArray()
                )
                ->filter(function (Builder $builder, string $value) {
                    if ($this->isFirstFilterApplied($builder)) {
                        $builder->getQuery()->wheres = [];
                    }
                    if (!empty($value)) {
                        $builder->whereHas('OrderDtl', function ($q) use ($value) {
                            $q->where('matl_code', $value);
                        });
                    }
                }),
        ];
    }

}
