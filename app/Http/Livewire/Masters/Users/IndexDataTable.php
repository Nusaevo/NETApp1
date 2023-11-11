<?php

namespace App\Http\Livewire\Masters\Users;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\User;

class IndexDataTable extends DataTableComponent
{
    protected $model = User::class;

    protected $listeners = [
        'master_user_refresh' => 'render',
    ];

    public function builder(): Builder
    {
        return User::query()->orderByName()->WithoutPurpose();
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Nama", "name")
                ->sortable()
                ->searchable(),
            Column::make("Peranan", "purpose")
                ->sortable(),
            Column::make("Surel", "email")
                ->sortable()
                ->searchable(),
            Column::make("Updated at", "updated_at")
                ->sortable(),
            Column::make('Aksi','id')
                ->format(
                    fn($value, $row, Column $column) => view('livewire.masters.users.index-data-table-action')->withRow($row)
                ),
        ];
    }
}
