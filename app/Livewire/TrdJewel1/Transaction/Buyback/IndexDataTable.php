<?php
namespace App\Livewire\TrdJewel1\Transaction\Buyback;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdJewel1\Transaction\ReturnHdr;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Status;
use App\Models\TrdJewel1\Transaction\ReturnDtl;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = ReturnHdr::class;

    public function mount(): void
    {
        $this->setSearchVisibilityStatus(false);
        $this->customRoute = "";
        $this->getPermission($this->customRoute);
    }

    public function builder(): Builder
    {
        return ReturnHdr::with('ReturnDtl', 'Partner')
            ->where('return_hdrs.tr_type', 'BB')
            ->where('return_hdrs.status_code', Status::OPEN);
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans("date"), "tr_date")
                ->sortable(),
            Column::make($this->trans("tr_id"), "tr_id")
                ->sortable(),
            Column::make($this->trans("tr_type"), "tr_type")
                ->hideIf(true)
                ->sortable(),
            Column::make($this->trans("customer"), "partner_id")
                ->format(function ($value, $row) {
                    return $this->formatPartnerLink($row);
                })
                ->html(),
            Column::make($this->trans("matl_code"), 'id')
                ->format(function ($value, $row) {
                    return $this->formatMaterialLinks($row);
                })
                ->html(),
            Column::make($this->trans("qty"), "total_qty")
                ->label(function ($row) {
                    return currencyToNumeric($row->total_qty);
                })
                ->sortable(),
            Column::make($this->trans("amt"), "total_amt")
                ->label(function ($row) {
                    return rupiah(currencyToNumeric($row->total_amt));
                })
                ->sortable(),
            Column::make($this->trans('status'), "status_code")
                ->sortable()
                ->format(function ($value) {
                    return Status::getStatusString($value);
                }),
            Column::make($this->trans("action"), 'id')
                ->format(function ($value, $row) {
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

    protected function formatPartnerLink($row)
    {
        if ($row->partner_id) {
            return '<a href="' . route('TrdJewel1.Master.Partner.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($row->partner_id)
            ]) . '">' . $row->Partner->name . '</a>';
        }
        return '';
    }

    protected function formatMaterialLinks($row)
    {
        $orderDtl = ReturnDtl::where('tr_id', $row->tr_id)
            ->where('tr_type', $row->tr_type)
            ->get();

        $matlCodes = $orderDtl->pluck('matl_code', 'matl_id');
        $links = $matlCodes->map(function ($code, $id) {
            return '<a href="' . route('TrdJewel1.Master.Material.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($id)
            ]) . '">' . $code . '</a>';
        });
        return $links->implode(', ');
    }

    public function filters(): array
    {
        return [
            $this->createTextFilter('Customer', 'name', 'Cari Customer', function (Builder $builder, string $value) {
                $builder->whereHas('Partner', function ($query) use ($value) {
                    $query->where(DB::raw('UPPER(name)'), 'like', '%' . strtoupper($value) . '%');
                });
            }),
            $this->createTextFilter('Barang', 'matl_code', 'Cari Barang', function (Builder $builder, string $value) {
                $builder->whereExists(function ($query) use ($value) {
                    $query->select(DB::raw(1))
                        ->from('return_dtls')
                        ->whereRaw('return_dtls.tr_id = return_hdrs.tr_id')
                        ->where(DB::raw('UPPER(return_dtls.matl_code)'), 'like', '%' . strtoupper($value) . '%')
                        ->where('return_dtls.tr_type', 'BB');
                });
            }),
        ];
    }
}
