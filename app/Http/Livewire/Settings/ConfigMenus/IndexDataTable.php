<?php
namespace App\Http\Livewire\Settings\ConfigMenus;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\ConfigMenu; // Import the ConfigGroup model
use Illuminate\Database\Eloquent\Builder;


class IndexDataTable extends DataTableComponent
{
    protected $model = ConfigMenu::class; // Use the ConfigMenu model

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    // Update the listeners and columns for the ConfigMenu model
    protected $listeners = [
        'settings_config_menu_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Appl Code", "appl_code")
                ->searchable()
                ->sortable(),
            Column::make("Menu Code", "menu_code")
                ->sortable(),
            Column::make("Menu Caption", "menu_caption")
                ->sortable(),
            Column::make("Status Code", "status_code")
                ->sortable(),
            Column::make("Is Active", "is_active")
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.settings.config-menus.index-data-table-action')->withRow($row)
                ),
        ];
    }

    // Update the filters as needed

    // ... Rest of the component
}
