<?php

namespace App\Livewire\TrdJewel1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Master\Material;
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Material::class;


    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSearchVisibilityStatus(false);
        $this->setFilter('Status', 0);
        $this->setFilter('stock_filter', 'above_0');
        $this->setDefaultSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return Material::query()->with('IvtBal');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("code"), "code")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("description_material"), "name")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("description_bom"), "descr")
                ->searchable()
                ->sortable(),
            Column::make("Qty Onhand", "IvtBal.qty_oh")
                ->format(function ($value, $row, Column $column) {
                    return currencyToNumeric($row->IvtBal?->qty_oh) ?? 0; // Ensure null values are shown as 0
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans("selling_price"), "jwl_selling_price")
                ->format(function ($value, $row, Column $column) {
                    return dollar($value);
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans("status"), "status_code")
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                })
                ->searchable()
                ->sortable(),
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
            TextFilter::make('Barang', 'name')
                ->config([
                    'placeholder' => 'Cari Kode Barang',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('code', 'like', '%' . $value . '%');
                }),
            SelectFilter::make('Status', 'Status')
                ->options([
                    '0' => 'Active',
                    '1' => 'Non Active'
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === '0') {
                        $builder->withoutTrashed();
                    } else if ($value === '1') {
                        $builder->onlyTrashed();
                    }
                }),
            SelectFilter::make('Stock', 'stock_filter')
                ->options([
                    'all' => 'All',
                    'above_0' => 'Available',
                    'below_0' => 'Out of Stock',
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === 'above_0') {
                        $builder->whereHas('IvtBal', function ($query) {
                            $query->where('qty_oh', '>', 0);
                        });
                    } elseif ($value === 'below_0') {
                        $builder->where(function ($query) {
                            $query->whereDoesntHave('IvtBal')
                                  ->orWhereHas('IvtBal', function ($query) {
                                      $query->where('qty_oh', '<=', 0);
                                  });
                        });
                    }
                }),
        ];
    }
}
