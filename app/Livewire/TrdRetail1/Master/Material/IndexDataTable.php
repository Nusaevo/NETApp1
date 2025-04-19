<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter};
use App\Models\TrdRetail1\Master\Material;
use App\Models\Util\{GenericExport, GenericExcelExport};
use App\Services\TrdRetail1\Master\MasterService;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, Writer\Xlsx, Style\Protection};
use Illuminate\Support\Facades\{File, Http, DB};
use App\Models\Base\Attachment;
use Exception;
use App\Enums\Status;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Material::class;

    protected $masterService;
    public $materialCategories;
    protected $listeners = ['refreshTable' => '$refresh', 'deleteMaterial'];

    public function mount(): void
    {
        $this->customRoute = '';
        $this->masterService = new MasterService();
        $this->materialCategories = $this->masterService->getMatlCategoryData();

        // Disable default search
        $this->setSearchDisabled();
    }

    /**
     * ==================================================
     *  BUILDER:
     *   - Always start with an empty query using whereRaw('1=0')
     * ==================================================
     */
    public function builder(): Builder
    {
        return Material::query()
            ->with(['IvtBal', 'MatlUom'])
            ->select('materials.*')
            ->whereRaw('1=0')
            ->orderBy('brand', 'asc')
            ->orderBy('class_code', 'asc')
            ->orderBy('seq', 'asc');
    }

    /**
     * ==================================================
     *  DATA COLUMNS: Define table columns
     * ==================================================
     */
    public function columns(): array
    {
        return [
            // 1. Merk
            Column::make($this->trans('brand'), 'brand')->sortable()->collapseOnTablet(),

            // 2. Jenis
            Column::make($this->trans('class_code'), 'class_code')->sortable()->collapseOnTablet(),

            // 3. No
            Column::make($this->trans('no'), 'seq')->sortable(),

            // 4. Kode
            Column::make($this->trans('code'), 'code')->sortable()->collapseOnTablet(),

            Column::make($this->trans('color'), 'specs')
                ->label(function ($row) {
                    $code = data_get($row->specs, 'color_code', '');
                    $name = data_get($row->specs, 'color_name', '');
                    return trim("$code - $name", ' -');
                })
                ->sortable(),

            // 7. Photo
            Column::make($this->trans('photo'), 'id')
                ->format(function ($value, $row) {
                    $firstAttachment = $row->Attachment->first();
                    $url = $firstAttachment ? $firstAttachment->getUrl() : null;
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

            // 8. Harga Jual
            Column::make($this->trans('selling_price'), 'id')->label(fn($row) => rupiah($row->DefaultUom->selling_price ?? 0))->sortable()->collapseOnTablet(),

            // 9. Modal
            Column::make($this->trans('buying_price'), 'id')->label(fn($row) => rupiah($row->DefaultUom->buying_price ?? 0))->sortable()->collapseOnTablet(),

            // 10. Stock
            Column::make($this->trans('stock'), 'stock')->label(fn($row) => $row->stock)->sortable()->collapseOnTablet(),

            // 11. UOM
            Column::make($this->trans('uom'), 'id')->format(fn($value, $row) => $row->uom ?? '')->sortable()->collapseOnTablet(),

            // Status & Action tetap di paling bawah
            BooleanColumn::make($this->trans('status'), 'deleted_at')->setCallback(fn($value) => $value === null),

            Column::make($this->trans('action'), 'id')->format(function ($value, $row) {
                return view('layout.customs.data-table-action', [
                    'row' => $row,
                    'custom_actions' => [],
                    'enable_this_row' => true,
                    'allow_details' => false,
                    'allow_edit' => true,
                    'allow_disable' => false,
                    'allow_delete' => false,
                    'permissions' => $this->permissions,
                ]);
            }),
        ];
    }

    /**
     * ==================================================
     *  FILTERS: Add filtering logic for multiple filters
     * ==================================================
     */
    public function filters(): array
    {
        $kategoriOptions = array_merge(['' => $this->trans('select_category')], collect($this->materialCategories)->pluck('label', 'value')->toArray());

        $brandOptions = array_merge(['' => $this->trans('select_brand')], Material::distinct('brand')->pluck('brand', 'brand')->toArray());

        $typeOptions = array_merge(['' => $this->trans('select_type')], Material::distinct('class_code')->pluck('class_code', 'class_code')->toArray());

        return [
            // Category Filter
            // MultiSelectFilter::make('Tags')
            // ->options(
            //     Tag::query()
            //         ->orderBy('name')
            //         ->get()
            //         ->keyBy('id')
            //         ->map(fn($tag) => $tag->name)
            //         ->toArray()
            // )->filter(function(Builder $builder, array $values) {
            //     $builder->whereHas('tags', fn($query) => $query->whereIn('tags.id', $values));
            // })
            // ->setFilterPillValues([
            //     '3' => 'Tag 1',
            // ]),
            $this->createTextFilter($this->trans('code'), 'code', $this->trans('code'), function (Builder $builder, string $value) {
                if ($this->isFirstFilterApplied($builder)) {
                    $builder->getQuery()->wheres = [];
                    $builder->getQuery()->orders = [];
                }
                $builder->where('code', 'ILIKE', "%{$value}%");
            }),

            SelectFilter::make($this->trans('category_label'), 'category')
                ->options($kategoriOptions)
                ->filter(function (Builder $query, string $value) {
                    if ($value !== '') {
                        if ($this->isFirstFilterApplied($query)) {
                            $query->getQuery()->wheres = [];
                            $query->getQuery()->orders = [];
                        }
                        $query->where('category', $value);
                    }
                })
                ->setWireLive(true),

            // Brand Filter
            SelectFilter::make($this->trans('brand_label'), 'brand')
                ->options($brandOptions)
                ->filter(function (Builder $query, string $value) {
                    if ($value !== '') {
                        if ($this->isFirstFilterApplied($query)) {
                            $query->getQuery()->wheres = [];
                            $query->getQuery()->orders = [];
                        }
                        $query->where('brand', $value);
                    }
                })
                ->setWireLive(true),

            // Type Filter
            $this->createTextFilter(
                $this->trans('type_label'),
                'class_code',
                $this->trans('class_code'),
                function (Builder $builder, string $value) {
                    if ($this->isFirstFilterApplied($builder)) {
                        $builder->getQuery()->wheres = [];
                        $builder->getQuery()->orders = [];
                    }
                    $builder->where('class_code', 'ILIKE', "%{$value}%");
                }
            ),

            SelectFilter::make($this->trans('stock_label'), 'stock_filter')
                ->options([
                    'all'     => $this->trans('all'),
                    'above_0' => $this->trans('available'),
                    'below_0' => $this->trans('out_of_stock'),
                ])
                ->filter(function (Builder $builder, string $value) {
                    // reset default whereRaw/orders kalau ini filter pertama
                    if ($this->isFirstFilterApplied($builder)) {
                        $builder->getQuery()->wheres = [];
                        $builder->getQuery()->orders = [];
                    }

                    // join manual ke mu
                    $builder->leftJoin('matl_uoms as mu', function ($join) {
                        $join->on('mu.matl_id', '=', 'materials.id')
                             ->whereColumn('mu.matl_uom', 'materials.uom');
                    });

                    if ($value === 'above_0') {
                        $builder->where('mu.qty_oh', '>', 0);
                    } elseif ($value === 'below_0') {
                        $builder->where('mu.qty_oh', '<=', 0);
                    }
                    // pastikan select materials.* lagi setelah join
                    $builder->select('materials.*');
                }),


            SelectFilter::make($this->trans('status_label'), 'status_filter')
                ->options([
                    'active' => $this->trans('active'),
                    'deleted' => $this->trans('non_active'),
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'active') {
                        $builder->whereNull('deleted_at');
                    } elseif ($value === 'deleted') {
                        $builder->withTrashed()->whereNotNull('deleted_at');
                    }
                }),
        ];
    }

    /**
     * ==================================================
     *  HELPER: Check if this is the first applied filter
     * ==================================================
     */
    protected function isFirstFilterApplied(Builder $query): bool
    {
        // Check if the query has only one where condition (whereRaw('1=0'))
        return count($query->getQuery()->wheres) === 1 && $query->getQuery()->wheres[0]['type'] === 'raw';
    }

    /**
     * ==================================================
     *  BULK ACTIONS: Optional bulk operations
     * ==================================================
     */
    public function bulkActions(): array
    {
        return [
            'deleteSelected' => 'Delete Selected',
            'downloadCreateTemplate' => 'Download Create Template',
            // 'exportExcel' => 'Export Excel',
        ];
    }
    /**
     * Generate and download an Excel template for creating materials.
     */
    public function downloadCreateTemplate()
    {
        $sheets = [Material::getCreateTemplateConfig()];
        $filename = 'Material_Create_Template_' . now()->format('Y-m-d') . '.xlsx';

        return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
    }
    /**
     * Generate and download an Excel template for updating materials.
     */
    public function exportExcel()
    {
        $selectedIds = $this->getSelected();
        $materials = Material::whereIn('id', $selectedIds)->get();
        $data = $materials
            ->map(function ($material, $index) {
                $specs = $material->specs;

                return [$specs['color_code'] ?? '', $specs['color_name'] ?? '', $material->selling_price ?? '', $material->stock ?? '', $material->code ?? '', $material->DefaultUom->barcode ?? '', $material->name ?? ''];
            })
            ->toArray();

        $sheets = [Material::getExcelTemplateConfig($data)];
        $filename = 'Material_Data_' . now()->format('Y-m-d') . '.xlsx';

        return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
    }
    /**
     * Generate and download an Excel template for updating materials.
     */
    public function downloadUpdateTemplate()
    {
        $selectedIds = $this->getSelected();
        $materials = Material::whereIn('id', $selectedIds)->get();
        $data = $materials
            ->map(function ($material, $index) {
                $specs = $material->specs;

                return [$material->seq, $specs['color_code'] ?? '', $specs['color_name'] ?? '', $material->DefaultUom->matl_uom ?? '', $material->selling_price ?? '', $material->stock ?? '', $material->code ?? '', $material->DefaultUom->barcode ?? '', $material->name ?? '', $material->deleted_at ? 'Yes' : 'No', $material->remarks ?? '', $material->version_number ?? ''];
            })
            ->toArray();

        $sheets = [Material::getUpdateTemplateConfig($data)];
        $filename = 'Material_Update_Template_' . now()->format('Y-m-d') . '.xlsx';

        return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
    }

    public function deleteSelected()
    {
        $selectedIds = $this->getSelected() ?? [];

        if (empty($selectedIds)) {
            $this->dispatch('error', $this->trans('no_materials_selected'));
            return;
        }
        $ids = implode(',', $selectedIds);
        $this->dispatch('open-confirm-dialog', [
            'title' => 'Confirm Delete',
            'message' => $this->trans('delete_confirm'),
            'icon' => 'warning',
            'confirmMethod' => 'deleteMaterial',
            'confirmParams' => implode(',', (array) $selectedIds),
            'confirmButtonText' => 'Yes, delete it!',
        ]);
    }

    public function deleteMaterial($data)
    {
        $idsArray = explode(',', $data);
        Material::whereIn('id', $idsArray)->update(['status_code' => Status::NONACTIVE]);
        Material::whereIn('id', $idsArray)->delete();
        $this->dispatch('refreshTable');
        $message = count($idsArray) > 1 ? $this->trans('delete_success_multiple') : $this->trans('delete_success_single');
        $this->dispatch('success', $message);
    }
}
