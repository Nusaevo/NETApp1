<?php
namespace App\Http\Livewire\TrdJewel1\Procurement\PurchaseReturn;

use App\Http\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Enums\Status;
use Lang;
use Exception;
class PurchaseOrderDataTable extends BaseDataTableComponent
{
    protected $model = OrderHdr::class;
    public int $perPage = 50;
    public $selectedRows = [];
    public $groupId;

    public function mount($groupId = null, $selectedUserIds = null): void
    {
        $this->customRoute = "";
$this->getPermission($this->customRoute);
        $this->groupId = $groupId;
        $this->selectedRows = $selectedUserIds;
    }

    public function builder(): Builder
    {
        $query = OrderHdr::query()->orderBy('tr_date');

        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make("", "id")
                ->format(function ($value, $row, Column $column) {
                    return "<input class='form-check-input' type='checkbox' wire:model.lazy='selectedRows." . $row->id . ".selected'>";
                })
                ->html(),
            Column::make("Nota", "tr_id")
                ->sortable()
                ->searchable(),
            Column::make("Tanggal Transaksi", "tr_date")
                ->searchable()
                ->sortable(),
            Column::make("Supplier", "Partner.name")
                ->searchable()
                ->sortable(),
            Column::make("Status", "status_code")
                ->searchable()
                ->sortable()
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                }),
            Column::make("Tanggal dibuat", "created_at")
                ->searchable()
                ->sortable(),
            Column::make('Actions', 'id')
                ->format(function ($value, $row, Column $column) {
                    return view('layout.customs.data-table-action', [
                        'row' => $row,
                        'custom_actions' => [],
                        'enable_this_row' => true,
                        'allow_details' => false,
                        'allow_edit' => true,
                        'allow_disable' => false,
                        'allow_delete' => false,
                        'permissions' => $this->permissions
                    ]);
                }),
        ];
    }
}
