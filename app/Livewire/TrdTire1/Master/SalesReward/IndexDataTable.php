<?php

namespace App\Livewire\TrdTire1\Master\SalesReward;

use App\Livewire\Component\BaseDataTableComponent;
use App\Models\TrdTire1\Master\SalesReward;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigRight;
use App\Models\TrdTire1\Master\GoldPriceLog;
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = SalesReward::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
    }

    public function builder(): Builder
    {
        return SalesReward::query()
            ->whereIn('status_code', [Status::ACTIVE, Status::PRINT]);
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("Kode Program"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("Nama Program"), "descrs")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("matl_code"), "matl_code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("grp"), "grp")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("qty"), "qty")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("reward"), "reward")
                ->searchable()
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
        return [
            DateFilter::make('Tanggal Nota')->filter(function (Builder $builder, string $value) {
                $builder->where('order_hdrs.tr_date', '=', $value);
            }),
            TextFilter::make('Nomor Nota')->filter(function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(order_hdrs.tr_code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            TextFilter::make('Customer')->filter(function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
        ];
    }
}
