<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\SysConfig1\Configsnum;

class IndexDataTable extends BaseDataTableComponent
{
    public $print_date;
    public $selectedItems = [];
    public $deletedRemarks = [];
    public $filters = [];

    protected $model = OrderDtl::class;
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('orderHdr.tr_date', 'desc');
    }

    public function builder(): Builder
    {
        return OrderDtl::query()
            ->with(['OrderHdr', 'OrderHdr.Partner'])
            ->where('order_dtls.tr_type', 'SO')
            ->select('order_dtls.*')
            ->join('order_hdrs','order_dtls.trhdr_id','=','order_hdrs.id');

    }

    public function columns(): array
    {
        return [
            Column::make('Partner', 'orderHdr.Partner.name')
                ->sortable()
                ->format(fn($value, $row) => $row->orderHdr->Partner->name ?? ''),
            Column::make('No. Nota','orderHdr.tr_code')
                ->sortable()
                ->format(fn($value,$row) =>
                    $row->orderHdr
                      ? '<a href="'.route($this->appCode.'.Transaction.SalesOrder.Detail',[
                             'action'=>encryptWithSessionKey('Edit'),
                             'objectId'=>encryptWithSessionKey($row->orderHdr->id)
                         ]).'">'.$row->orderHdr->tr_code.'</a>'
                      : ''
                )->html(),


            Column::make('Kode barang','matl_code')
                ->sortable(),
            Column::make('Nama barang','matl_descr')
                ->sortable(),

            Column::make('Qty','qty')
                ->sortable(),

            Column::make('DPP','dpp')
                ->label(fn($row)=> rupiah($row->dpp))
                ->sortable(),
            Column::make('No Nota GT','gt_tr_code')
                ->label(fn($row)=> ($row->gt_tr_code))
                ->sortable(),
            Column::make('Tgl Proses GT','gt_process_date')
                ->sortable(),


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
        $configDetails = $this->getConfigDetails();
        $printDates = OrderHdr::select('print_date')
            ->distinct()
            ->whereNotNull('print_date')
            ->pluck('print_date', 'print_date')
            ->toArray();

        return [
            SelectFilter::make('Nomor Faktur Pajak Terakhir')
                ->options([$configDetails['last_cnt'] => $configDetails['last_cnt']])
                ->filter(function (Builder $builder, string $value) {}),
            SelectFilter::make('Batas Nomor Faktur')
                ->options([$configDetails['wrap_high'] => $configDetails['wrap_high']])
                ->filter(function (Builder $builder, string $value) {}),
            // SelectFilter::make('Tanggal Proses')
            //     ->options($printDates)
            //     ->filter(function (Builder $builder, string $value) {
            //         $this->filters['print_date'] = $value; // Add this line
            //         $builder->where('print_date', $value);
            //     }),
            // DateFilter::make('Tanggal Nota')->filter(function (Builder $builder, string $value) {
            //     $builder->where('order_hdrs.tr_date', '=', $value);
            // }),
            TextFilter::make('Nomor Nota')->filter(function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(order_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            TextFilter::make('Custommer')->filter(function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
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
    public function bulkActions(): array
    {
        return [
            'setNotaGT' => 'Nomor Nota Baru',
        ];
    }
    public function getConfigDetails()
    {
        $config = Configsnum::where('code', 'SO_FPAJAK_LASTID')->first();
        if ($config) {
            return [
                'last_cnt' => $config->last_cnt,
                'wrap_high' => $config->wrap_high,
            ];
        }
        return [
            'last_cnt' => 'N/A',
            'wrap_high' => 'N/A',
        ];
    }

    public function setNotaGT()
    {
        // Get the current year and month
        $year = now()->format('y'); // Two-digit year
        $month = now()->format('m'); // Two-digit month

        // Get the last sequence number for the current month
        $lastSequence = OrderDtl::whereNotNull('gt_tr_code')
            ->where('gt_tr_code', 'like', $year . $month . '%')
            ->orderBy('gt_tr_code', 'desc')
            ->value('gt_tr_code');

        // Extract the last sequence number or start from 0
        $sequence = $lastSequence ? (int)substr($lastSequence, -4) : 0;

        // Iterate over selected items and assign GT numbers
        foreach ($this->getSelected() as $id) {
            $sequence++; // Increment the sequence number
            $gtTrCode = $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT); // Format GT number

            // Update the record with the generated GT number
            OrderDtl::where('id', $id)->update(['gt_tr_code' => $gtTrCode]);
        }

        // Dispatch a success message
        $this->dispatch('success', 'Nomor Nota GT berhasil diatur.');
    }
}
