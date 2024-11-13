<?php

namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdRetail1\Config\ConfigAudit;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ConfigAudit::class;

    public function mount(): void
    {
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
        $this->setSearchDisabled();
        $this->setDefaultSort('created_at', 'desc');
        $this->showCreateButton = false;
    }

    protected $listeners = ['renderAuditTable' => 'render'];

    public function builder(): Builder
    {
        return ConfigAudit::query()
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
            Column::make("Progress", "progress")
                ->sortable()
                ->format(function ($value, $row) {
                    $color = $row->status_code === Status::ERROR ? '#dc3545' : '#28a745'; // Red for error, green otherwise
                    return '
                        <div style="width: 100%; background-color: #e9ecef; border-radius: 5px;">
                            <div style="width: ' . $value . '%; background-color: ' . $color . '; height: 10px; border-radius: 5px;"></div>
                        </div>
                        <small>' . $value . '%</small>
                    ';
                })
                ->html(),
            Column::make("Status", "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    $color = $value === Status::ERROR ? 'red' : 'black'; // Red text for error, black otherwise
                    return '<span style="color: ' . $color . ';">' . Status::getStatusString($value) . '</span>';
                })
                ->html(),
            Column::make("Created Date", "created_at")
                ->sortable(),
            Column::make("Actions", "id")
                ->format(function ($value, $row) {
                    $buttons = '<div class="btn-group">';

                    // Download Result Button
                    $buttons .= '<x-ui-button clickEvent="downloadResult(' . $row->id . ')" button-name="Download Result" loading="true" cssClass="btn-primary" iconPath="download.svg" />';

                    // Conditionally show Reupload button if status is error
                    if ($row->status_code === Status::ERROR) {
                        $buttons .= '<x-ui-button clickEvent="reupload(' . $row->id . ')" button-name="Reupload" loading="true" cssClass="btn-secondary" iconPath="reupload.svg" />';
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
