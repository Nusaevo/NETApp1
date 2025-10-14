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
            ->whereIn('status_code', [Status::OPEN, Status::ACTIVE])
            ->orderBy('updated_at', 'desc')
            ->orderBy('tr_date', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("Tgl. Transaksi"), "tr_date")
                ->format(function ($value) {
                    return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : '';
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Nomor Transaksi"), "tr_code")
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
                        $materialCodes = $row->IvttrDtl->pluck('matl_code')->filter()->unique()->values();
                        return $materialCodes->count() > 0 ? $materialCodes->implode(', ') : '-';
                    }
                    return '-';
                }),
            Column::make('Batch', 'batch_code')
                ->label(function ($row) {
                    if ($row->IvttrDtl && $row->IvttrDtl->count() > 0) {
                        $batchCodes = $row->IvttrDtl->pluck('batch_code')->filter()->unique()->values();
                        return $batchCodes->count() > 0 ? $batchCodes->implode(', ') : '-';
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
            Column::make('Gudang Tujuan', 'wh_code')
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
            // Filter Tanggal Periode
            DateFilter::make('Tanggal Awal')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('tr_date', '>=', $value);
                }),

            DateFilter::make('Tanggal Akhir')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('tr_date', '<=', $value);
                }),

            // Filter Nomor Transaksi
            TextFilter::make('Nomor Transaksi')
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(tr_code)'), 'like', '%' . strtoupper($value) . '%');
                }),

            TextFilter::make('Kode Barang')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereExists(function ($query) use ($value) {
                        $query->select(DB::raw(1))
                            ->from('ivttr_dtls')
                            ->whereRaw('ivttr_dtls.tr_code = ivttr_hdrs.tr_code')
                            ->where(DB::raw('UPPER(ivttr_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%');
                    });
                }),
            SelectFilter::make('Tipe Transaksi')
                ->options([
                    '' => 'Semua Transaksi',
                    'IA' => 'IA - Adjustment',
                    'TW' => 'TW - Transfer',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('tr_type', $value);
                }),
        ];
    }
}
