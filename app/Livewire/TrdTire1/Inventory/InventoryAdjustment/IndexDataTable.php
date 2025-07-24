<?php

namespace App\Livewire\TrdTire1\Inventory\InventoryAdjustment;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Inventories\IvttrHdr;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = IvttrHdr::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
    }

    public function builder(): Builder
    {
        return IvttrHdr::query()
            ->with(['IvttrDtl'])
            ->where('status_code', Status::OPEN)
            ->orWhere('status_code', Status::ACTIVE); // Include non-active records
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("tr_code"), "tr_code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->appCode . '.Inventory.InventoryAdjustment.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey((string)$row->id)
                    ]) . '">' . $row->tr_code . '</a>';
                })
                ->html()
                ->sortable(),
            Column::make($this->trans("Tipe Transaksi"), "tr_type")
                ->sortable(),
            Column::make('Kode Barang', 'matl_code')
                ->label(function ($row) {
                    if ($row->IvttrDtl && $row->IvttrDtl->count() > 0) {
                        return $row->IvttrDtl->pluck('matl_code')->filter()->first();
                    }
                    return '-';
                }),
            Column::make('Batch', 'batch_code')
                ->label(function ($row) {
                    if ($row->IvttrDtl && $row->IvttrDtl->count() > 0) {
                        $batch = $row->IvttrDtl->pluck('batch_code')->filter()->first();
                        return $batch ?: '-';
                    }
                    return '-';
                }),
            Column::make('Gudang', 'wh_code')
                ->label(function ($row) {
                    if ($row->tr_type === 'IA') {
                        // Ambil wh_code dari detail dengan tr_seq > 0 (biasanya 1)
                        if ($row->IvttrDtl && $row->IvttrDtl->count() > 0) {
                            return $row->IvttrDtl->where('tr_seq', '>', 0)->pluck('wh_code')->filter()->first() ?: '-';
                        }
                        return '-';
                    }
                    if ($row->IvttrDtl && $row->IvttrDtl->count() > 0) {
                        return $row->IvttrDtl->where('tr_seq', -1)->pluck('wh_code')->filter()->implode(', ');
                    }
                    return '-';
                }),
            Column::make('Gudand Tujuan', 'wh_code')
                ->label(function ($row) {
                    if ($row->tr_type === 'IA') {
                        // Untuk IA, gudang tujuan dikosongkan
                        return '-';
                    }
                    if ($row->IvttrDtl && $row->IvttrDtl->count() > 0) {
                        // Ambil wh_code hanya untuk tr_seq == 1
                        return $row->IvttrDtl->where('tr_seq', 1)->pluck('wh_code')->filter()->implode(', ');
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
            // $this->createTextFilter('Material', 'matl_code', 'Cari Kode Material', function (Builder $builder, string $value) {
            //     $builder->whereExists(function ($query) use ($value) {
            //         $query->select(DB::raw(1))
            //             ->from('deliv_dtls')
            //             ->whereRaw('deliv_dtls.tr_code = ivttr_hdrs.tr_code')
            //             ->where(DB::raw('UPPER(deliv_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
            //             ->where('deliv_dtls.tr_type', 'PD');
            //     });
            // }),
        ];
    }
}
