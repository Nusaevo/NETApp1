<?php

namespace App\Livewire\TrdRetail1\Master\Partner;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{
    Column,
    Columns\BooleanColumn,
    Filters\SelectFilter,
    Filters\TextFilter
};
use App\Models\TrdRetail1\Master\Partner;
use App\Services\SysConfig1\ConfigService;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    // The Eloquent model for this table
    protected $model = Partner::class;

    // Holds the TYPE query‑param (C, V, or null)
    public $type;

    public function mount(): void
    {
        // Disable the built‑in search box and set default sort
        $this->setSearchDisabled();
        $this->setDefaultSort('created_at', 'desc');

        // Read the TYPE parameter from the URL, e.g. ?TYPE=C or ?TYPE=V
        $this->type = request()->query('TYPE');
    }

    public function builder(): Builder
    {
        $query = Partner::query();

        // If TYPE=C, show only customers (grp = 'C')
        if ($this->type === 'C') {
            $query->where('grp', 'C');
        }
        // If TYPE=V, show only suppliers (grp = 'V')
        elseif ($this->type === 'V') {
            $query->where('grp', 'V');
        }
        // Otherwise (no TYPE or other), show all

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans('code'), 'code')
                ->format(function ($value, $row) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        route(
                            $this->appCode . '.Master.Partner.Detail',
                            [
                                'action'   => encryptWithSessionKey('Edit'),
                                'objectId' => encryptWithSessionKey($row->id),
                            ]
                        ),
                        $row->code
                    );
                })
                ->html(),

            Column::make($this->trans('group'), 'grp')
                ->searchable()
                ->sortable()
                ->format(function ($value) {
                    return (new ConfigService())
                        ->getConstValueByStr1('PARTNERS_TYPE', $value) ?? '';
                }),

            Column::make($this->trans('name'), 'name')->searchable()->sortable(),
            Column::make($this->trans('address'), 'address')->searchable()->sortable(),
            Column::make($this->trans('phone'), 'phone')->searchable()->sortable(),
            Column::make($this->trans('email'), 'email')->searchable()->sortable(),

            BooleanColumn::make($this->trans('Status'), 'deleted_at')
                ->setCallback(fn($value) => $value === null),

            Column::make($this->trans('created_date'), 'created_at')->sortable(),

            Column::make($this->trans('actions'), 'id')
                ->format(function ($value, $row) {
                    return view('layout.customs.data-table-action', [
                        'row'             => $row,
                        'custom_actions'  => [],
                        'enable_this_row' => true,
                        'allow_details'   => false,
                        'allow_edit'      => true,
                        'allow_disable'   => false,
                        'allow_delete'    => false,
                        'permissions'     => $this->permissions,
                    ]);
                }),
        ];
    }

  public function filters(): array
{
    $filters = [
        // dua text filter selalu tampil
        $this->createTextFilter(
            'Partner', 'code', 'Cari Kode Partner',
            fn(Builder $b, string $v) =>
                $b->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($v) . '%')
        ),
        $this->createTextFilter(
            'Nama', 'name', 'Cari Nama',
            fn(Builder $b, string $v) =>
                $b->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($v) . '%')
        ),
    ];

    // kalau ?TYPE tidak di-set (show all), tampilkan dropdown Group
    if (empty($this->type)) {
        $filters[] = SelectFilter::make('Group', 'grp')
            ->options([
                ''  => 'All',
                'V' => 'Supplier',
                'C' => 'Pelanggan',
            ])
            ->filter(fn(Builder $b, string $v) => $b->where('grp', $v));
    }

    $filters[] = SelectFilter::make('Status', 'status_filter')
        ->options([
            'active'  => 'Active',
            'deleted' => 'Non Active',
        ])
        ->filter(function (Builder $b, string $v) {
            if ($v === 'active') {
                $b->whereNull('deleted_at');
            } elseif ($v === 'deleted') {
                $b->withTrashed()->whereNotNull('deleted_at');
            }
        });

    return $filters;
}

}
