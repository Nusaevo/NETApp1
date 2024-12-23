<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdRetail1\Master\Material;
use App\Models\Util\GenericExport;
use App\Models\Util\GenericExcelExport;
use App\Services\TrdRetail1\Master\MasterService;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Protection;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Material::class;

    protected $masterService;
    public $materialCategories;

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
            ->with(['IvtBal'])
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
            Column::make('Color Code', 'specs->color_code')->format(fn($value, $row) => $row['specs->color_code'] ?? '')->sortable(),

            Column::make('Color Name', 'specs->color_name')->format(fn($value, $row) => $row['specs->color_name'] ?? '')->sortable(),

            Column::make('Photo', 'id')
                ->format(function ($value, $row) {
                    $firstAttachment = $row->Attachment->first();
                    $imageUrl = $firstAttachment ? $firstAttachment->getUrl() : null;
                    return $imageUrl ? '<img src="' . $imageUrl . '" alt="Photo" style="width: 100px; height: 100px; object-fit: cover;">' : '<span>No Image</span>';
                })
                ->html(),

            Column::make('UOM', 'id')->format(fn($value, $row) => $row->MatlUom[0]->matl_uom ?? '')->sortable(),

            Column::make('Selling Price', 'selling_price_text')->label(fn($row) => $row->selling_price_text)->sortable(),

            Column::make('Stock', 'IvtBal.qty_oh')->format(fn($value, $row) => $row->IvtBal?->qty_oh ?? 0)->sortable(),

            Column::make('Code', 'code')->sortable(),

            Column::make('Barcode', 'id')->format(fn($value, $row) => $row->MatlUom[0]->barcode ?? '')->sortable(),

            Column::make('Remarks', 'remarks')->sortable(),

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
        $kategoriOptions = array_merge(
            ['' => 'Select Category'],
            collect($this->materialCategories)
                ->pluck('label', 'value')
                ->toArray(),
        );

        $brandOptions = array_merge(['' => 'Select Brand'], Material::distinct('brand')->pluck('brand', 'brand')->toArray());

        $typeOptions = array_merge(['' => 'Select Type'], Material::distinct('type_code')->pluck('type_code', 'type_code')->toArray());

        return [
            // Category Filter
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
            SelectFilter::make('Type', 'type_code')
                ->options($typeOptions)
                ->filter(function (Builder $query, string $value) {
                    if ($value !== '') {
                        if ($this->isFirstFilterApplied($query)) {
                            $query->getQuery()->wheres = [];
                        }
                        $query->where('type_code', $value);
                    }
                })
                ->setWireLive(true),
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
            'downloadCreateTemplate' => 'Download Create Template',
            'downloadUpdateTemplate' => 'Download Update Template',
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
    public function downloadUpdateTemplate()
    {
        $selectedIds = $this->getSelected();
        $materials = Material::whereIn('id', $selectedIds)->get();
        $data = $materials
        ->map(function ($material, $index) {
            $specs = is_array($material->specs) ? $material->specs : json_decode($material->specs, true);

            return [$index + 1, $specs['color_code'] ?? '', $specs['color_name'] ?? '', $material->MatlUom[0]->matl_uom ?? '', $material->selling_price ?? '', $material->stock ?? '', $material->code ?? '', $material->MatlUom[0]->barcode ?? '', $material->name ?? '', $material->deleted_at ? 'Yes' : 'No', $material->remarks ?? '', $material->version_number ?? ''];
        })
        ->toArray();

        $sheets = [Material::getUpdateTemplateConfig($data)];
        $filename = 'Material_Update_Template_' . now()->format('Y-m-d') . '.xlsx';

        return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
    }
}
