<?php

namespace App\Http\Livewire\Settings\Users;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\User; // Import the User model
use Illuminate\Database\Eloquent\Builder;

class IndexDataTable extends DataTableComponent
{
    protected $model = User::class; // Use the User model

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    // Update the listeners and columns for the User model
    protected $listeners = [
        'settings_user_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Name", "name")
                ->searchable()
                ->sortable(),
            Column::make("Email", "email")
                ->searchable()
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.settings.users.index-data-table-action')->withRow($row)
                ),
        ];
    }
}
