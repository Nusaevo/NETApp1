<?php

namespace App\Http\Livewire\Settings\ConfigGroups;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ConfigGroup; // Import the ConfigGroup model
use Illuminate\Database\Eloquent\Builder;

class IndexDataTable extends DataTableComponent
{
    protected $model = ConfigGroup::class; // Use the ConfigGroup model

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    // Update the listeners and columns for the ConfigGroup model
    protected $listeners = [
        'settings_config_group_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Appl Code", "appl_code")
                ->searchable()
                ->sortable(),
            Column::make("Group Code", "group_code")
                ->sortable(),
            Column::make("User Code", "user_code")
                ->sortable(),
            Column::make("Note1", "note1")
                ->sortable(),
            Column::make("Status Code", "status_code")
                ->sortable(),
            Column::make("Is Active", "is_active")
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.settings.config-groups.index-data-table-action')->withRow($row)
                ),
        ];
    }

    // Update the filters as needed

    // ... Rest of the component
}
