<?php

namespace App\Livewire\TrdRetail1\Inventory\InventoryAdjustment;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdRetail1\Inventories\IvttrHdr;
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
            ->where(function ($query) {
                $query->where('status_code', Status::OPEN)
                      ->orWhere('status_code', Status::ACTIVE);
            })
            ->where('remark', '!=', 'Initial stock adjustment from Excel upload')
            ->whereNull('deleted_at');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("tr_id"), "tr_id")
                ->sortable(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->sortable(),
            Column::make($this->trans("remark"), "remark")
                ->sortable(),
            // tr_id
            // Column::make($this->trans("tr_code"), "tr_code")
            //     ->format(function ($value, $row) {
            //         return '<a href="' . route($this->appCode . '.Inventory.InventoryAdjustment.Detail', [
            //             'action' => encryptWithSessionKey('Edit'),
            //             'objectId' => encryptWithSessionKey((string)$row->id)  // Ensure it's a string
            //         ]) . '">' . $row->tr_code . '</a>';
            //     })
            //     ->html(),
            // Column::make($this->trans('qty'), 'total_qty')
            //     ->label(function ($row) {
            //         return $row->total_qty;
            //     })
            //     ->sortable(),
            // Column::make($this->trans('amt'), 'total_amt')
            //     ->label(function ($row) {
            //         return rupiah($row->total_amt);
            //     })
            //     ->sortable(),
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
