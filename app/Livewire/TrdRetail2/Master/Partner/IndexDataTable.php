<?php

namespace App\Livewire\TrdRetail2\Master\Partner;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Filters\SelectFilter, Filters\TextFilter};
use App\Models\TrdRetail2\Master\Partner;
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
            Column::make($this->trans('group'), "grp")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    $configService = new ConfigService();
                    return  $configService->getConstValueByStr1('PARTNERS_TYPE', $value) ?? '';
                }),
            Column::make($this->trans("code"), "code")
                ->format(function ($value, $row) {
                    return '<a href="' . route('TrdRetail2.Master.Partner.Detail', [
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
            Column::make($this->trans('status'), "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
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
        return [
            SelectFilter::make('Group', 'grp')
                ->options([
                    '' => 'ALL', // Opsi untuk semua grup
                    'V' => 'SUPPLIER',
                    'C' => 'CUSTOMER',
                    'W' => 'WAJIB PAJAK'
                ])->filter(function (Builder $builder, string $value) {
                    $builder->where('grp', $value);
                }),
            $this->createTextFilter('Kode Partner', 'code', 'Cari Kode Partner', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Nama Partner', 'name', 'Cari Nama', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
            }),
            $this->createTextFilter('Kota', 'city', 'Cari Kota', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(city)'), 'like', '%' . strtoupper($value) . '%');
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
        ];
    }
}
