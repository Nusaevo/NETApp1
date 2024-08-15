<?php

namespace App\Livewire\SysConfig1\ConfigMenu;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\SysConfig1\ConfigMenu;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Models\SysConfig1\ConfigRight;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigMenu::class;


    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);

        $this->setSort('created_at', 'desc');
        $this->setFilter('Status', 0);
        $this->setSearchVisibilityStatus(false);
    }

    public function builder(): Builder
    {
        return ConfigMenu::query()
            ->withTrashed()
            ->select();
    }

    public function columns(): array
    {
        return [
            Column::make("Application", "id")
            ->format(function ($value, $row) {
                if ($row->app_id) {
                    return '<a href="' . route('SysConfig1.ConfigApplication.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($row->app_id)
                    ]) . '">' . optional($row->configAppl)->code . ' - ' . optional($row->configAppl)->name . '</a>';
                } else {
                    return '';
                }
            })
            ->html()
            ->sortable(),
            Column::make("Menu Code", "code")
                ->searchable()
                ->sortable(),
            Column::make("Menu Header", "menu_header")
                ->searchable()
                ->sortable(),
            Column::make("Menu Caption", "menu_caption")
                ->searchable()
                ->sortable(),
            Column::make("Status", "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            Column::make('Created Date', 'created_at')
                ->sortable(),
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
                        'permissions' => $this->permissions
                    ]);
                }),
        ];
    }

    public function filters(): array
    {
        return [
            TextFilter::make('Aplikasi', 'application')
            ->config([
                'placeholder' => 'Cari Kode/Nama Aplikasi',
                'maxlength' => '50',
            ])
            ->filter(function (Builder $builder, string $value) {
                $builder->whereHas('configAppl', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%')
                          ->orWhere(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            })->setWireLive(),
            TextFilter::make('Kode', 'code')
                ->config([
                    'placeholder' => 'Cari Kode',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
                })->setWireLive(),
            TextFilter::make('Header', 'menu_header')
                ->config([
                    'placeholder' => 'Cari Menu Header',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(menu_header)'), 'like', '%' . strtoupper($value) . '%');
                })->setWireLive(),
            TextFilter::make('Caption', 'menu_caption')
                ->config([
                    'placeholder' => 'Cari Menu Caption',
                    'maxlength' => '50',
                ])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where(DB::raw('UPPER(menu_caption)'), 'like', '%' . strtoupper($value) . '%');
                })->setWireLive(),
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
