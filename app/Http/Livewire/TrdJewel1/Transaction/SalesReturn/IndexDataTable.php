<?php

namespace App\Http\Livewire\TrdJewel1\Transaction\SalesReturn;

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

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ReturnHdr::class;
    public $returnIds;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->setSort('tr_date', 'desc');
        $this->setFilter('status_code',  Status::ACTIVE);
    }

    public function builder(): Builder
    {
        return ReturnHdr::query()
            ->where('tr_type', 'SR');
    }

    public function columns(): array
    {
        return [
            Column::make("Nota", "tr_id")
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
            Column::make("Tanggal dibuat", "created_at")
                ->searchable()
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
            Column::make('', 'id')
                ->format(function ($value, $row, Column $column) {
                    // Tombol pertama (Nota Terima Supplier atau Print, tergantung pada status)
                    // if ($row->status_code === Status::ACTIVE) {
                    //     $firstButton = '<a href="' . route("TrdJewel1.Procurement.PurchaseOrder.Detail", ["action" => encryptWithSessionKey('Create'), "objectId" => encryptWithSessionKey($row->id)]) . '" class="btn btn-primary btn-sm" style="text-decoration: none;">Nota Terima Supplier</a>';
                    // } else {
                    //     $firstButton = '';
                    // }
                    $secondButton = '<a href="' . route('TrdJewel1.Procurement.PurchaseOrder.PrintPdf', ["action" => encryptWithSessionKey('Edit'),'objectId' => encryptWithSessionKey($row->id)]) . '" class="btn btn-primary btn-sm" style="margin-left: 5px; text-decoration: none;">Print</a>';

                    return "<div class='text-center'>". $secondButton."</div>";
                })->html(),

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
