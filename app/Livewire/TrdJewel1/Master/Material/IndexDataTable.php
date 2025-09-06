<?php

namespace App\Livewire\TrdJewel1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter, Filters\TextFilter};
use App\Models\TrdJewel1\Master\Material;
use App\Models\SysConfig1\{ConfigRight, ConfigConst};
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Material::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setFilter('Status', 0);
        $this->setFilter('stock_filter', 'above_0');
        $this->setDefaultSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return Material::with(['IvtBal'])->select('materials.*');
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans('code'), 'code')
                ->format(function ($value, $row) {
                    return '<a href="' .
                        route($this->appCode . '.Master.Material.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id),
                        ]) .
                        '">' .
                        $row->code .
                        '</a>';
                })
                ->html(),
            Column::make($this->trans('description_material'), 'name')->searchable()->sortable(),
            Column::make($this->trans('description_bom'), 'descr')->searchable()->sortable()->collapseOnTablet(),
            Column::make('Qty Onhand', 'IvtBal.qty_oh')
                ->format(function ($value, $row, Column $column) {
                    return $row->IvtBal?->qty_oh ?? 0; // Ensure null values are shown as 0
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans('buying_price'), 'jwl_buying_price_text')
                ->label(function ($row) {
                    return $row->jwl_buying_price_text;
                })
                ->sortable()->collapseOnTablet(),
            Column::make($this->trans('selling_price'), 'jwl_selling_price_text')
                ->label(function ($row) {
                    return $row->jwl_selling_price_text;
                })
                ->sortable()->collapseOnTablet(),

            BooleanColumn::make($this->trans("Status"), "deleted_at")
                ->setCallback(function ($value) {
                    return $value === null;
                }),
            Column::make($this->trans('created_date'), 'created_at')->sortable()->collapseOnTablet(),
            Column::make($this->trans('action'), 'id')->format(function ($value, $row, Column $column) {
                return view('layout.customs.data-table-action', [
                    'row' => $row,
                    'custom_actions' => [],
                    'enable_this_row' => true,
                    'allow_details' => false,
                    'allow_edit' => true,
                    'allow_disable' => false,
                    'allow_delete' => false,
                    'permissions' => $this->permissions,
                ]);
            }),
        ];
    }

    private function getCategory1Options(): array
    {
        $categories = ConfigConst::where('const_group', 'MMATL_CATEGL1')
            ->whereNull('deleted_at')
            ->orderBy('str2')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->str1 => $item->str1 . ' - ' . $item->str2];
            })
            ->toArray();

        // If no data found, try without deleted_at filter
        if (empty($categories)) {
            $categories = ConfigConst::where('const_group', 'MMATL_CATEGL1')
                ->orderBy('str2')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->str1 => $item->str1 . ' - ' . $item->str2];
                })
                ->toArray();
        }

        return ['' => 'All'] + $categories;
    }

    private function getCategory2Options(): array
    {
        $categories = ConfigConst::where('const_group', 'MMATL_CATEGL2')
            ->whereNull('deleted_at')
            ->orderBy('str2')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->str1 => $item->str1 . ' - ' . $item->str2];
            })
            ->toArray();

        // If no data found, try without deleted_at filter
        if (empty($categories)) {
            $categories = ConfigConst::where('const_group', 'MMATL_CATEGL2')
                ->orderBy('str2')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->str1 => $item->str1 . ' - ' . $item->str2];
                })
                ->toArray();
        }

        return ['' => 'All'] + $categories;
    }

    public function filters(): array
    {
        return [
            $this->createTextFilter('Kode Barang', 'name', 'Cari Kode Barang', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), '=', strtoupper($value));
            }),
            SelectFilter::make('Kategori 1', 'category1_filter')
                ->options($this->getCategory1Options())
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('jwl_category1', $value);
                    }
                }),
            SelectFilter::make('Kategori 2', 'category2_filter')
                ->options($this->getCategory2Options())
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('jwl_category2', $value);
                    }
                }),
            SelectFilter::make('Status', 'status_filter')
            ->options([
                'active' => 'Active',
                'deleted' => 'Non Active',
            ])->filter(function (Builder $builder, string $value) {
                if ($value === 'active') {
                    $builder->whereNull('deleted_at');
                } elseif ($value === 'deleted') {
                    $builder->withTrashed()->whereNotNull('deleted_at');
                }
            }),
            SelectFilter::make('Stock', 'stock_filter')
                ->options([
                    'all' => 'All',
                    'above_0' => 'Available',
                    'below_0' => 'Out of Stock',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'above_0') {
                        $builder->whereHas('IvtBal', function ($query) {
                            $query->where('qty_oh', '>', 0);
                        });
                    } elseif ($value === 'below_0') {
                        $builder->where(function ($query) {
                            $query->whereDoesntHave('IvtBal')->orWhereHas('IvtBal', function ($query) {
                                $query->where('qty_oh', '<=', 0);
                            });
                        });
                    }
                }),
        ];
    }
}
