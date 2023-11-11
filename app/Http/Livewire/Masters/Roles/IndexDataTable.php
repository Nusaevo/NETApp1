<?php

namespace App\Http\Livewire\Masters\Roles;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Spatie\Permission\Models\Role;

class IndexDataTable extends DataTableComponent
{
    protected $model = Role::class;

    protected $listeners = ['master_role_refresh' => 'render',];

    public function builder(): Builder
    {
        return Role::query()->whereNotIn('name', ['superadmin']);
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make('Aksi','id')
                ->format(
                    fn($value, $row, Column $column) => view('livewire.masters.roles.index-data-table-action')->withRow($row)
                ),
        ];
    }

    // public function filters(): array
    // {
    //     return [
    //         SelectFilter::make('Tampilkan Terhapus')
    //             ->options([
    //                 '0' => 'Tidak',
    //                 '1' => 'Saja',
    //                 '2' => 'Semua',
    //             ])->filter(function(Builder $builder, string $value) {
    //                 if ($value === '0') $builder->withoutTrashed();
    //                 else if ($value === '1') $builder->onlyTrashed()->select('*');
    //                 else if ($value === '2') $builder->withTrashed()->select('*');
    //             }),
    //     ];
    // }
}
