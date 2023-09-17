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
            Column::make("First Name", "first_name")
                ->searchable()
                ->sortable(),
            Column::make("Last Name", "last_name")
                ->sortable(),
            Column::make("Email", "email")
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.settings.users.index-data-table-action')->withRow($row)
                ),
        ];
    }

    // Update the filters as needed

    // ... Rest of the component
}
