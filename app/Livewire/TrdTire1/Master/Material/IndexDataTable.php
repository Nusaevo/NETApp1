<?php

namespace App\Livewire\TrdTire1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter, Filters\TextFilter};
use App\Models\TrdTire1\Master\Material;
use App\Models\SysConfig1\ConfigRight;
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
        return Material::select('materials.*', 'matl_uoms.selling_price')
            ->leftJoin('matl_uoms', 'materials.id', '=', 'matl_uoms.matl_id');
    }


    public function columns(): array
    {
        return [
            Column::make($this->trans("category"), "category")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("code"), "code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->appCode . '.Master.Material.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($row->id)
                    ]) . '">' . $row->code . '</a>';
                })
                ->html(),
            Column::make($this->trans("description_material"), "name")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("selling_price"), "selling_price")
                ->label(function ($row) {
                    return $row->selling_price_text;
                })
                ->sortable(),
            Column::make('Stock', 'IvtBal.qty_oh')
                ->format(function ($value, $row, Column $column) {
                    return $row->IvtBal?->qty_oh ?? 0; // Ensure null values are shown as 0
                })
                ->searchable()
                ->sortable(),
            // Column::make($this->trans("point"), "point")
            //     ->searchable()
            //     ->sortable(),
            BooleanColumn::make($this->trans("Status"), "deleted_at")
                ->setCallback(function ($value) {
                    return $value === null;
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
            $this->createTextFilter('Barang', 'name', 'Cari Kode Barang', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), '=', strtoupper($value));
            }),
            SelectFilter::make('Status', 'status_filter')
                ->options([
                    'active' => 'Active',
                    'deleted' => 'Non Active',
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === 'active') {
                        $builder->whereNull('deleted_at');
                    } elseif ($value === 'deleted') {
                        $builder->whereNotNull('deleted_at');
                    }
                }),
        ];
    }
}
