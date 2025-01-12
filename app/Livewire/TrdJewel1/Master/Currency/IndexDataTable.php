<?php

namespace App\Livewire\TrdJewel1\Master\Currency;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Filters\DateFilter};
use App\Models\TrdJewel1\Master\GoldPriceLog;
use Illuminate\Database\Eloquent\Builder;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = GoldPriceLog::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setSort('log_date', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "log_date")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("currency_rate"), "curr_rate")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return rupiah($value);
                }),

            Column::make($this->trans("gold_price_base"), "goldprice_basecurr")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return rupiah($value);
                }),
            Column::make($this->trans("gold_price_currency"), "goldprice_curr")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return dollar($value);
                }),
            Column::make($this->trans('created_date'), 'created_at')
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
            DateFilter::make($this->trans('From Date'), 'from_date')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('log_date', '>=', $value);
                }),

            DateFilter::make($this->trans('To Date'), 'to_date')
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereDate('log_date', '<=', $value);
                }),
        ];
    }
}
