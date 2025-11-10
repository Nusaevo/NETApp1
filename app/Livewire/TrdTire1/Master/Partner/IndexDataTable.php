<?php

namespace App\Livewire\TrdTire1\Master\Partner;

use App\Livewire\Component\BaseDataTableComponent;
use App\Models\SysConfig1\ConfigConst;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter, Filters\TextFilter};
use App\Models\TrdTire1\Master\Partner;
use App\Services\SysConfig1\ConfigService;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Partner::class;

    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return Partner::query();
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans('grp'), "grp")
                ->searchable()
                ->sortable(),
            Column::make($this->trans("code"), "code")
                ->format(function ($value, $row) {
                    return '<a href="' . route($this->appCode . '.Master.Partner.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($row->id)
                    ]) . '">' . $row->code . '</a>';
                })
                ->html(),
            Column::make($this->trans('name'), "name")
                ->searchable()
                ->sortable(),
            Column::make($this->trans('address'), "address")
                ->searchable()
                ->sortable(),
            Column::make($this->trans('city'), "city")
                ->searchable()
                ->sortable(),
            BooleanColumn::make($this->trans("Status"), "deleted_at")
                ->setCallback(function ($value) {
                    return $value === null;
                }),
            Column::make($this->trans('created_date'), 'created_at')
                ->sortable(),
            Column::make($this->trans('actions'), 'id')
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
        // Ambil opsi kategori dari config const MPARTNER_TYPE
        $kategoriOptions = ConfigConst::where('const_group', 'MPARTNER_TYPE')
            ->orderBy('seq')
            ->pluck('str2', 'str1')
            ->toArray();
        $kategoriOptions = ['' => 'All'] + $kategoriOptions;

        return [
            SelectFilter::make('Kategori', 'grp')
                ->options($kategoriOptions)
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('grp', $value);
                    }
                }),
            $this->createTextFilter('Kode Partner', 'code', 'Cari Kode Partner', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }, true),
            $this->createTextFilter('Nama Partner', 'name', 'Cari Nama Partner', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
            }, true),
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
        ];
    }
}
