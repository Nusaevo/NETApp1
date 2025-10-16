<?php

namespace App\Livewire\TrdTire1\Transaction\AuditLogs;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\LinkColumn, Filters\SelectFilter, Filters\TextFilter, Filters\DateFilter};
use App\Models\TrdTire1\Transaction\{AuditLogs, BillingHdr};
use App\Enums\TrdTire1\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = AuditLogs::class;
    public $bulkSelectedIds = null;


    public function mount(): void
    {
        $this->setSearchDisabled();
        $this->setDefaultSort('event_time', 'desc');
    }

    public function builder(): Builder
    {
        return AuditLogs::where('group_code', 'BILLING')
            ->orderBy('event_time', 'desc');
    }

    public function columns(): array
    {
        return [
            // Column::make($this->trans("ID"), "id"),
            Column::make($this->trans("Group Code"), "group_code"),
            Column::make($this->trans("Event Code"), "event_code"),
            Column::make($this->trans("Event Time"), "event_time"),
            Column::make($this->trans("Key Value"), "key_value"),
            Column::make($this->trans("Audit Trail"), "audit_trail")
                ->format(function ($value, $row) {
                    if (is_array($value)) {
                        $formatted = [];
                        foreach ($value as $key => $val) {
                            $formatted[] = "<strong>{$key}:</strong> {$val}";
                        }
                        return implode('<br>', $formatted);
                    }
                    return $value;
                })
                ->html(),
            // Column::make($this->trans("Created At"), "created_at"),
        ];
    }

    public function filters(): array
    {
        return [
            // Simple filters without complex methods
        ];
    }

    public function bulkActions(): array
    {
        return [
            // No bulk actions for audit logs as they are read-only
        ];
    }

}
