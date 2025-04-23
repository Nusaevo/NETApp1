<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter};
use App\Models\TrdRetail1\Master\MatlUom;
use App\Models\TrdRetail1\Master\Material;
use App\Models\Util\GenericExcelExport;
use App\Services\TrdRetail1\Master\MasterService;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = MatlUom::class;

    protected MasterService $masterService;

    protected array $defaultSorts = [];
    public array $materialCategories;
    protected $listeners = ['refreshTable' => '$refresh', 'deleteMaterial'];

    public function mount(): void
    {
        $this->customRoute = '';
        $this->masterService = new MasterService();
        $this->materialCategories = $this->masterService->getMatlCategoryData();
        $this->setSearchDisabled();
        $this->setDefaultSort('Material.code', 'asc');
    }

    public function builder(): Builder
    {
        $query = MatlUom::withTrashed()
            ->with('Material')
            ->join('materials', 'materials.id', '=', 'matl_uoms.matl_id')
            ->select('matl_uoms.*')
            ->whereRaw('1=0');
        return $query;
    }

    public function columns(): array
    {
        return [
            Column::make($this->trans('no'), 'Material.seq')
            ->sortable(),
            Column::make($this->trans('code'), 'Material.code')
            ->format(function ($value, $row) {
                return '<a href="' .
                    route($this->appCode . '.Master.Material.Detail', [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($row->Material->id),
                    ]) .
                    '">' .
                    $row->Material->code .
                    '</a>';
            })
            ->html()->sortable(
                fn(Builder $query, string $direction) =>
                    // karena kita sudah join ke materials di builder()
                    $query->orderBy('materials.code', $direction)
            ),
            Column::make($this->trans('name'), 'Material.name')->sortable(),
            // Column::make($this->trans('brand'), 'Material.brand')->sortable(),
            Column::make($this->trans('photo'), 'Material.id')
                ->format(function ($value, $row) {
                    $attachment = $row->Material->Attachment->first();
                    $url = $attachment?->getUrl();
                    return $url
                        ? view('components.ui-image', [
                            'src' => $url,
                            'alt' => $this->trans('photo'),
                            'width' => '50px',
                            'height' => '50px',
                        ])
                        : '<span>' . $this->trans('no_image') . '</span>';
                })
                ->html(),

            Column::make($this->trans('selling_price'), 'selling_price')->label(fn($row) => rupiah($row->selling_price))->sortable(),

            Column::make($this->trans('buying_price'), 'buying_price')->label(fn($row) => rupiah($row->buying_price))->sortable(),

            Column::make($this->trans('stock'), 'qty_oh')->label(fn($row) => $row->qty_oh)->sortable(),

            Column::make($this->trans('uom'), 'matl_uom'),

            BooleanColumn::make($this->trans('status'), 'deleted_at')->setCallback(fn($value) => $value === null),
            Column::make($this->trans('action'), 'id')
            ->format(fn($value, $matlUom) => view('layout.customs.data-table-action', [
                'row'    => $matlUom->Material,
                'custom_actions'  => [],
                'enable_this_row' => true,
                'allow_details'   => false,
                'allow_edit'      => true,
                'allow_disable'   => false,
                'allow_delete'    => false,
                'permissions'     => $this->permissions,
            ])),

        ];
    }

    public function filters(): array
    {
        $kategoriOptions = ['' => $this->trans('select_category')] + collect($this->materialCategories)->pluck('label', 'value')->toArray();

        $brandOptions = ['' => $this->trans('select_brand')] + Material::distinct('brand')->pluck('brand', 'brand')->toArray();

        $typeOptions = ['' => $this->trans('select_type')] + Material::distinct('class_code')->pluck('class_code', 'class_code')->toArray();

        return [
            // CODE
            $this->createTextFilter($this->trans('product'), 'tag', "Cari Produk", function (Builder $q, string $v) {
                if ($this->isFirstFilterApplied($q)) {
                    $q->getQuery()->wheres = [];
                }
                $q->whereHas('Material', fn(Builder $m) => $m->where('tag', 'ILIKE', "%{$v}%"));
            }),

            // CATEGORY
            SelectFilter::make($this->trans('category_label'), 'category')
                ->options($kategoriOptions)
                ->filter(function (Builder $q, string $v) {
                    if ($this->isFirstFilterApplied($q)) {
                        $q->getQuery()->wheres = [];
                    }
                    if ($v !== '') {
                        $q->whereHas('Material', fn(Builder $m) => $m->where('category', $v));
                    }
                })
                ->setWireLive(true),

            // BRAND
            SelectFilter::make($this->trans('brand_label'), 'brand')
                ->options($brandOptions)
                ->filter(function (Builder $q, string $v) {
                    if ($this->isFirstFilterApplied($q)) {
                        $q->getQuery()->wheres = [];
                    }
                    if ($v !== '') {
                        $q->whereHas('Material', fn(Builder $m) => $m->where('brand', $v));
                    }
                })
                ->setWireLive(true),

            // CLASS_CODE / TYPE
            $this->createTextFilter($this->trans('type_label'), 'class_code', $this->trans('class_code'), function (Builder $q, string $v) {
                if ($this->isFirstFilterApplied($q)) {
                    $q->getQuery()->wheres = [];
                }
                $q->whereHas('Material', fn(Builder $m) => $m->where('class_code', 'ILIKE', "%{$v}%"));
            }),
            // STOCK
            SelectFilter::make($this->trans('stock_label'), 'qty_oh')
                ->options([
                    'all' => $this->trans('all'),
                    'above_0' => $this->trans('available'),
                    'below_0' => $this->trans('out_of_stock'),
                ])
                ->filter(function (Builder $q, string $v) {
                    if ($this->isFirstFilterApplied($q)) {
                        $q->getQuery()->wheres = [];
                    }
                    if ($v === 'above_0') {
                        $q->where('qty_oh', '>', 0);
                    } elseif ($v === 'below_0') {
                        $q->where('qty_oh', '<=', 0);
                    }
                }),

            // STATUS
            SelectFilter::make($this->trans('status_label'), 'status_filter')
                ->options([
                    'active' => $this->trans('active'),
                    'deleted' => $this->trans('non_active'),
                ])
                ->filter(function (Builder $q, string $v) {
                    if ($v === 'active') {
                        $q->whereHas('Material', fn(Builder $m) => $m->whereNull('deleted_at'));
                    } elseif ($v === 'deleted') {
                        $q->whereHas('Material', fn(Builder $m) => $m->withTrashed()->whereNotNull('deleted_at'));
                    }
                }),
        ];
    }

    protected function isFirstFilterApplied(Builder $query): bool
    {
        // Check if the query has only one where condition (whereRaw('1=0'))
        return count($query->getQuery()->wheres) === 1 && $query->getQuery()->wheres[0]['type'] === 'raw';
    }

    public function bulkActions(): array
    {
        return [
            'deleteSelected' => 'Disable Selected',
            'downloadCreateTemplate' => 'Download Create Template',
        ];
    }

    public function downloadCreateTemplate()
    {
        $sheets = [Material::getCreateTemplateConfig()];
        $filename = 'Material_Create_Template_' . now()->format('Y-m-d') . '.xlsx';

        return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
    }

    public function deleteSelected()
    {
        $ids = $this->getSelected() ?? [];
        if (empty($ids)) {
            $this->dispatch('error', $this->trans('no_materials_selected'));
            return;
        }
        $this->dispatch('open-confirm-dialog', [
            'title' => 'Confirm Delete',
            'message' => $this->trans('delete_confirm'),
            'icon' => 'warning',
            'confirmMethod' => 'deleteMaterial',
            'confirmParams' => implode(',', $ids),
            'confirmButtonText' => 'Yes, delete it!',
        ]);
    }

    public function deleteMaterial($data)
    {
        $uomIds = explode(',', $data);

        // Fetch related material IDs before deletion
        $uoms = MatlUom::whereIn('id', $uomIds)->get();
        $materialIds = $uoms->pluck('matl_id')->unique();

        // Delete UOM records
        MatlUom::whereIn('id', $uomIds)->delete();

        // For each affected material, delete if it has no more UOMs
        foreach ($materialIds as $matlId) {
            $hasUoms = MatlUom::where('matl_id', $matlId)->exists();
            if (! $hasUoms) {
                Material::where('id', $matlId)->delete();
            }
        }

        // Refresh table and notify
        $this->dispatch('refreshTable');
        $message = count($uomIds) > 1
            ? $this->trans('delete_success_multiple')
            : $this->trans('delete_success_single');
        $this->dispatch('success', $message);
    }
}
