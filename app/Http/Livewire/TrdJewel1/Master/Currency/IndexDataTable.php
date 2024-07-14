<?php

namespace App\Http\Livewire\TrdJewel1\Master\Currency;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = GoldPriceLog::class;

    
    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
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
                    return rupiah(currencyToNumeric($value));
                }),

            Column::make($this->trans("gold_price_base"), "goldprice_basecurr")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return rupiah(currencyToNumeric($value));
                }),
            Column::make($this->trans("gold_price_currency"), "goldprice_curr")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return dollar(currencyToNumeric($value));
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
            SelectFilter::make('Status', 'Status')
                ->options([
                    '0' => 'Active',
                    '1' => 'Non Active'
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === '0') $builder->withoutTrashed();
                    else if ($value === '1') $builder->onlyTrashed();
                }),
        ];
    }
}
