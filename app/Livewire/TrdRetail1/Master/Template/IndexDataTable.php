<?php

namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdRetail1\Config\AppAudit;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = AppAudit::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSearchDisabled();
        $this->setDefaultSort('created_at', 'desc');
        $this->showCreateButton = false;
    }
    protected $listeners = [ 'renderAuditTable' => 'render'];

    public function builder(): Builder
    {
        return AppAudit::query()
        ->select();
    }

    public function columns(): array
    {
        return [
            Column::make("Upload Date", "log_time")
                ->sortable(),
            Column::make("Status", "audit_trail")
                ->searchable()
                ->sortable(),
            Column::make("Table Name", "table_name")
                ->searchable()
                ->sortable(),
            Column::make("Created By", "created_by")
                ->searchable()
                ->sortable(),
            Column::make("Created Date", "created_at")
                ->sortable(),
            Column::make("Actions", "id")
                ->format(function ($value, $row) {
                    $buttons = '<div class="btn-group">';

                    // Download Result Button
                    $buttons .= '<button wire:click="downloadResult(' . $row->id . ')" class="btn btn-sm btn-primary">Download Result</button>';
                    // Conditionally show Reupload button if status is success
                    if (strpos($row->audit_trail, 'success') !== false) {
                        $buttons .= '<button wire:click="reupload(' . $row->id . ')" class="btn btn-sm btn-secondary">Reupload</button>';
                    }

                    $buttons .= '</div>';
                    return $buttons;
                })
                ->html(),
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }
}
