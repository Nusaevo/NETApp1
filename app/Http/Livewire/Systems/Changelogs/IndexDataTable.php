<?php

namespace App\Http\Livewire\Systems\Changelogs;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Changelog;

class IndexDataTable extends DataTableComponent
{
    protected $model = Changelog::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Counter", "id")
                ->sortable(),
            Column::make("Tipe", "type")
                ->format(
                    fn($value, $row, Column $column) => view('livewire.systems.changelogs.index-data-table-view-type')->withValue($value)
                ),
            Column::make("Versi", "version")
                ->sortable(),
            Column::make("Patch", "patch"),
            Column::make("Status", "status")
                ->format(
                    fn($value, $row, Column $column) => view('livewire.systems.changelogs.index-data-table-view-status')->withValue($value)
                ),
            Column::make("SHA", "sha"),
            Column::make("LOG", "message"),
            Column::make("Diaplikasikan", "created_at")
                ->sortable(),
        ];
    }
}
