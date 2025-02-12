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
        $this->setSearchDisabled();
        $this->setDefaultSort('created_at', 'desc');
    }

    protected $listeners = ['renderAuditTable' => 'render'];

    public function configure(): void
    {
        parent::configure();
        $this->setConfigurableAreas([
            'toolbar-left-start' => null,
        ]);
    }

    public function builder(): Builder
    {
        return ConfigAudit::query()
            ->where('action_code', 'UPLOAD')
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
            // Column::make("Table Name", "table_name")
            //     ->searchable()
            //     ->sortable(),
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
                    $color = $value === Status::ERROR ? 'red' : 'black';
                    return '<span style="color: ' . $color . ';">' . Status::getStatusString($value) . '</span>';
                })
                ->html(),
            Column::make("Created Date", "created_at")
                ->sortable(),
            Column::make("Actions", "id")
            ->format(function ($value, $row, Column $column) {
                return view('layout.customs.data-table-action', [
                    'row' => $row,
                   'custom_actions' => [
                        [
                            'label' => 'Download Result',
                            'onClick' => "downloadResult($row->id)",
                            'icon' => 'bi bi-download'
                        ],
                        // [
                        //     'label' => 'Reupload',
                        //     'onClick' => "reupload($row->id)",
                        //     'icon' => 'bi bi-arrow-repeat',
                        //     'condition' => $row->status_code === Status::ERROR
                        // ],
                    ],
                    'enable_this_row' => true,
                    'allow_details' => false,
                    'allow_edit' => false,
                    'allow_disable' => false,
                    'allow_delete' => false,
                    'permissions' => $this->permissions,
                ]);
            }),
        ];
    }

    public function downloadResult($id)
    {
        $audit = ConfigAudit::find($id);

        // Dapatkan attachment terkait
        $attachment = $audit->Attachment;
        if (!$attachment[0] || !$attachment[0]->getUrl()) {
            $this->dispatch('error', 'Attachment not found for this record.');
            return;
        }


        return redirect($attachment[0]->getUrl());
    }


    public function reupload($id)
    {

    }

    public function filters(): array
    {
        return [
        ];
    }
}
