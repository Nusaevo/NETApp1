<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Columns\BooleanColumn, Filters\SelectFilter};
use App\Models\TrdRetail1\Master\Material;
use App\Models\Util\{GenericExport, GenericExcelExport};
use App\Services\TrdRetail1\Master\MasterService;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\{Spreadsheet, Writer\Xlsx, Style\Protection};
use Illuminate\Support\Facades\{File, Http};
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
        $this->setDefaultSort('created_at', 'desc');
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
            ->with(['IvtBal','MatlUom'])
            ->select('materials.*')
            ->whereRaw('1=0'); // Start with an empty query by default
    }

    /**
     * ==================================================
     *  DATA COLUMNS: Define table columns
     * ==================================================
     */
    public function columns(): array
    {
        return [
            Column::make('No', 'seq')->sortable(),

            Column::make('Code', 'code')->sortable()->collapseOnTablet(),
            Column::make('Merk', 'brand')
                ->sortable()
                ->collapseOnTablet(),

            Column::make('Jenis', 'class_code')
                ->sortable()
                ->collapseOnTablet(),

            Column::make('Color Code', 'specs->color_code')->format(fn($value, $row) => $row['specs->color_code'] ?? '')->sortable(),

            Column::make('Color Name', 'specs->color_name')->format(fn($value, $row) => $row['specs->color_name'] ?? '')->sortable(),

            Column::make('Photo', 'id')
                ->format(function ($value, $row) {
                    $firstAttachment = $row->Attachment->first();
                    $imageUrl = $firstAttachment ? $firstAttachment->getUrl() : null;
                    return $imageUrl
                        ? view('components.ui-image', [
                            'src' => $imageUrl,
                            'alt' => 'Photo',
                            'width' => '50px',
                            'height' => '50px',
                        ])
                        : '<span>No Image</span>';
                })
                ->html(),

            Column::make('UOM', 'id')->format(fn($value, $row) => $row->uom ?? '')->sortable()->collapseOnTablet(),

            Column::make('Selling Price', 'id')->label(fn($row) => rupiah($row->DefaultUom->selling_price ?? 0))->sortable()->collapseOnTablet(),
            Column::make('Buying Price', 'id')->label(fn($row) => rupiah($row->DefaultUom->buying_price ?? 0))->sortable()->collapseOnTablet(),
            Column::make('stock', 'stock')
            ->label(function ($row) {
                return $row->stock;
            })
            ->sortable()->collapseOnTablet(),
            BooleanColumn::make($this->trans('Status'), 'deleted_at')->setCallback(function ($value) {
                return $value === null;
            }),
            // Column::make($this->trans('created_date'), 'created_at')->sortable()->collapseOnTablet(),
            Column::make('Action', 'id')->format(function ($value, $row, $column) {
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
        $kategoriOptions = array_merge(['' => 'Select Category'], collect($this->materialCategories)->pluck('label', 'value')->toArray());

        $brandOptions = array_merge(['' => 'Select Brand'], Material::distinct('brand')->pluck('brand', 'brand')->toArray());

        $typeOptions = array_merge(['' => 'Select Type'], Material::distinct('class_code')->pluck('class_code', 'class_code')->toArray());

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
            SelectFilter::make('Category', 'category')
                ->options($kategoriOptions)
                ->filter(function (Builder $query, string $value) {
                    if ($value !== '') {
                        if ($this->isFirstFilterApplied($query)) {
                            $query->getQuery()->wheres = [];
                        }
                        $query->where('category', $value);
                    }
                })
                ->setWireLive(true),

            // Brand Filter
            SelectFilter::make('Brand', 'brand')
                ->options($brandOptions)
                ->filter(function (Builder $query, string $value) {
                    if ($value !== '') {
                        if ($this->isFirstFilterApplied($query)) {
                            $query->getQuery()->wheres = [];
                        }
                        $query->where('brand', $value);
                    }
                })
                ->setWireLive(true),

            // Type Filter
            SelectFilter::make('Type', 'class_code')
                ->options($typeOptions)
                ->filter(function (Builder $query, string $value) {
                    if ($value !== '') {
                        if ($this->isFirstFilterApplied($query)) {
                            $query->getQuery()->wheres = [];
                        }
                        $query->where('class_code', $value);
                    }
                })
                ->setWireLive(true),

            SelectFilter::make('Stock', 'stock_filter')
                ->options([
                    'all' => 'All',
                    'above_0' => 'Available',
                    'below_0' => 'Out of Stock',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'above_0') {
                        $builder->whereHas('ivtBals', function ($query) {
                            $query->havingRaw('SUM(qty_oh) > 0');
                        });
                    } elseif ($value === 'below_0') {
                        $builder->whereDoesntHave('ivtBals')
                            ->orWhereHas('ivtBals', function ($query) {
                                $query->havingRaw('SUM(qty_oh) <= 0');
                            });
                    }
                }),

            SelectFilter::make('Status', 'status_filter')
                ->options([
                    'active' => 'Active',
                    'deleted' => 'Non Active',
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
            'exportExcel' => 'Export Excel',
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
            $this->dispatch('error', 'No materials selected.');
            return;
        }
        $ids = implode(',', $selectedIds);
        $this->dispatch('open-confirm-dialog', [
            'title' => 'Confirm Delete',
            'message' => 'Are you sure you want to delete this?',
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
        $message = count($idsArray) > 1 ? 'Selected materials deleted successfully.' : 'Material deleted successfully.';
        $this->dispatch('success', $message);
    }
}
