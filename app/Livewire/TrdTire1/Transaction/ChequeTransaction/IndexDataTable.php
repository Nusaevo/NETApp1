<?php

namespace App\Livewire\TrdTire1\Transaction\ChequeTransaction;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{DelivPacking, OrderHdr, OrderDtl, PartnertrHdr, PartnertrDtl};
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = PartnertrHdr::class;
    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('tr_date', 'desc');
        $this->setDefaultSort('tr_code', 'desc');
    }

    public function builder(): Builder
    {
        return PartnertrHdr::with(['PartnertrDtl'])
            ->where('partnertr_hdrs.tr_type', 'CQDEP')
            ->orWhere('partnertr_hdrs.tr_type', 'CQREJ')
            ->orderBy('partnertr_hdrs.tr_date', 'desc');
    }
    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Tipe"), "tr_type")
                ->sortable(),
            Column::make($this->trans("nomor Transaksi"), "tr_code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->appCode . '.Transaction.ChequeTransaction.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($row->id)
                    ]) . '">' . $row->tr_code . '</a>';
                })
                ->html(),
            Column::make($this->trans("Bank"), "partner_code")
                ->label(function ($row) {
                    // Ambil partner_code dari detail pertama (tr_seq positif)
                    $firstDetail = $row->PartnertrDtl->where('tr_seq', '>', 0)->first();
                    return $firstDetail ? $firstDetail->partner_code : '-';
                }),
            Column::make($this->trans("Giro"), "bank_reff")
                ->label(function ($row) {
                    // Ambil bank_reff dari detail pertama
                    $firstDetail = $row->PartnertrDtl->where('tr_seq', '>', 0)->first();
                    if ($firstDetail) {
                        // Ambil data giro dari PaymentSrc berdasarkan reff_id
                        $giroData = \App\Models\TrdTire1\Transaction\PaymentSrc::where('id', $firstDetail->reff_id)->first();
                        return $giroData ? $giroData->bank_reff : '-';
                    }
                    return '-';
                }),
            Column::make($this->trans('Jumlah Item'), 'item_count')
                ->label(function ($row) {
                    return $row->PartnertrDtl->where('tr_seq', '>', 0)->count();
                })
                ->sortable(),
            Column::make($this->trans("Note"), "tr_descr")
                ->label(function ($row) {
                    // Ambil note dari detail pertama
                    $firstDetail = $row->PartnertrDtl->where('tr_seq', '>', 0)->first();
                    return $firstDetail ? $firstDetail->tr_descr : '-';
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
            DateFilter::make('Tanggal Transaksi')->filter(function (Builder $builder, string $value) {
                $builder->where('partnertr_hdrs.tr_date', '=', $value);
            }),
            $this->createTextFilter('Nomor Transaksi', 'tr_code', 'Cari Nomor Transaksi', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(partnertr_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Bank', 'partner_code', 'Cari Bank', function (Builder $builder, string $value) {
                $builder->whereHas('PartnertrDtl', function ($query) use ($value) {
                    $query->where('tr_seq', '>', 0)
                          ->where(DB::raw('UPPER(partner_code)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            $this->createTextFilter('Giro', 'bank_reff', 'Cari Giro', function (Builder $builder, string $value) {
                $builder->whereHas('PartnertrDtl', function ($query) use ($value) {
                    $query->where('tr_seq', '>', 0);
                });
            }),
            SelectFilter::make('Tipe Transaksi', 'tr_type')
                ->options([
                    '' => 'All',
                    'CQDEP' => 'Cheque Deposit',
                    'CQREJ' => 'Cheque Reject',
                ])
                ->filter(function ($builder, $value) {
                    if ($value !== '') {
                        $builder->where('partnertr_hdrs.tr_type', $value);
                    }
                }),
        ];
    }
}
