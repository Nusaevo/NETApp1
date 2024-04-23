<?php

namespace App\Http\Livewire\TrdJewel1\Master\Currency;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = GoldPriceLog::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->setSort('log_date', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("Date", "log_date")
                ->searchable()
                ->sortable(),
            Column::make("Currency Rate", "curr_rate")
                ->searchable()
                ->sortable(),
            Column::make("Gold Price", "goldprice_curr")
                ->searchable()
                ->sortable(),
            Column::make("Gold Price base Curr", "goldprice_basecurr")
                ->searchable()
                ->sortable(),
            Column::make('Created Date', 'created_at')
                    ->sortable(),
                    Column::make('Actions', 'id')
                    ->format(function ($value, $row, Column $column) {
                        return view('layout.customs.data-table-action', [
                            'row' => $row,
                            'enable_this_row' => true,
                            'allow_details' => false,
                            'allow_edit' => true,
                            'allow_disable' => false,
                            'allow_delete' => false,
                            'access' => $this->customRoute ? $this->customRoute : $this->baseRoute
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
