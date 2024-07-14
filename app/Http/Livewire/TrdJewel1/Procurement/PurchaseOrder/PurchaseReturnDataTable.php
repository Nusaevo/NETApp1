<?php

namespace App\Http\Livewire\TrdJewel1\Procurement\PurchaseOrder;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Transaction\ReturnHdr;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Status;
use Lang;
use Exception;

class PurchaseReturnDataTable extends BaseDataTableComponent
{
    protected $model = ReturnHdr::class;
    public $returnIds;

    public function mount($returnIds = null): void
    {
        $this->customRoute = "TrdJewel1/Procurement/PurchaseReturn";
        $this->setSort('tr_date', 'desc');
        $this->setFilter('status_code',  Status::ACTIVE);
        $this->returnIds = $returnIds;
    }

    public function builder(): Builder
    {
        $query = ReturnHdr::query()->orderBy('tr_date');

        if ($this->returnIds !== null) {
            $query->whereIn('return_hdrs.id', $this->returnIds);
        }

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable()
                ->searchable(),
            Column::make("Tanggal Transaksi", "tr_date")
                ->searchable()
                ->sortable(),
            Column::make("Supplier", "Partner.name")
                ->searchable()
                ->sortable(),
            Column::make("Status", "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                       'row' => $row,
                        'custom_actions' => [],
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
            SelectFilter::make('Status', 'status_code')
                ->options([
                    Status::ACTIVE => 'Active',
                    Status::COMPLETED => 'Selesai',
                    '' => 'Semua',
                ])->filter(function ($builder, $value) {
                    if ($value === Status::ACTIVE) {
                        $builder->where('return_hdrs.status_code', Status::ACTIVE);
                    } else if ($value === Status::COMPLETED) {
                        $builder->where('return_hdrs.status_code', Status::COMPLETED);
                    } else if ($value === '') {
                        $builder->withTrashed();
                    }
                }),
            DateFilter::make('Tanggal Awal')->filter(function (Builder $builder, string $value) {
                $builder->where('return_hdrs.tr_date', '>=', $value);
            }),
            DateFilter::make('Tanggal Akhir')->filter(function (Builder $builder, string $value) {
                $builder->where('return_hdrs.tr_date', '<=', $value);
            }),

        ];
    }
}
