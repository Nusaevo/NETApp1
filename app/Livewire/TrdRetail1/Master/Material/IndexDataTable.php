<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\BooleanColumn;
use App\Models\TrdRetail1\Master\Material;
use App\Models\Util\GenericExport;
use App\Models\Util\GenericExcelExport;
use App\Services\TrdRetail1\Master\MasterService;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use Illuminate\Support\Facades\File;
use Exception;
use App\Models\Base\Attachment;
use Illuminate\Support\Facades\Http;

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
            Column::make('No', 'seq')->sortable(),

            Column::make('Color Code', 'specs->color_code')->format(fn($value, $row) => $row['specs->color_code'] ?? '')->sortable(),

            Column::make('Color Name', 'specs->color_name')->format(fn($value, $row) => $row['specs->color_name'] ?? '')->sortable(),

            Column::make('Photo', 'id')
                ->format(function ($value, $row) {
                    $firstAttachment = $row->Attachment->first();
                    $imageUrl = $firstAttachment ? $firstAttachment->getUrl() : null;

                    return $imageUrl
                        ? '<div style="display: flex; align-items: center; gap: 5px;">
                            <img src="' .
                                $imageUrl .
                                '" alt="Photo" style="width: 50px; height: 50px; object-fit: cover; cursor: pointer;" onclick="showImagePreview(\'' .
                                $imageUrl .
                                '\')">
                            <button type="button" onclick="showImagePreview(\'' .
                                $imageUrl .
                                '\')" style="border: none; background: none; cursor: pointer;">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>'
                        : '<span>No Image</span>';
                })
                ->html(),

            Column::make('UOM', 'id')->format(fn($value, $row) => $row->MatlUom[0]->matl_uom ?? '')->sortable(),

            Column::make('Selling Price', 'selling_price_text')->label(fn($row) => $row->selling_price_text)->sortable(),

            Column::make('Stock', 'IvtBal.qty_oh')->format(fn($value, $row) => $row->IvtBal?->qty_oh ?? 0)->sortable(),

            Column::make('Code', 'code')->sortable(),

            Column::make('Barcode', 'id')->format(fn($value, $row) => $row->MatlUom[0]->barcode ?? '')->sortable(),

            Column::make('Remarks', 'remarks')->sortable(),
            BooleanColumn::make($this->trans('Status'), 'deleted_at')->setCallback(function ($value) {
                return $value === null;
            }),
            Column::make($this->trans('created_date'), 'created_at')->sortable(),
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

            SelectFilter::make('Stock', 'stock_filter')
                ->options([
                    'all' => 'All',
                    'above_0' => 'Available',
                    'below_0' => 'Out of Stock',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'above_0') {
                        $builder->whereHas('IvtBal', function ($query) {
                            $query->where('qty_oh', '>', 0);
                        });
                    } elseif ($value === 'below_0') {
                        $builder->where(function ($query) {
                            $query->whereDoesntHave('IvtBal')->orWhereHas('IvtBal', function ($query) {
                                $query->where('qty_oh', '<=', 0);
                            });
                        });
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
            'downloadCreateTemplate' => 'Download Create Template',
            'downloadUpdateTemplate' => 'Download Update Template',
            'syncImages' => 'Sync Photos from NetStorage',
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
                $specs = is_array($material->specs) ? $material->specs : $material->specs;

                return [$material->seq, $specs['color_code'] ?? '', $specs['color_name'] ?? '', $material->MatlUom[0]->matl_uom ?? '', $material->selling_price ?? '', $material->stock ?? '', $material->code ?? '', $material->MatlUom[0]->barcode ?? '', $material->name ?? '', $material->deleted_at ? 'Yes' : 'No', $material->remarks ?? '', $material->version_number ?? ''];
            })
            ->toArray();

        $sheets = [Material::getUpdateTemplateConfig($data)];
        $filename = 'Material_Update_Template_' . now()->format('Y-m-d') . '.xlsx';

        return (new GenericExcelExport(sheets: $sheets, filename: $filename))->download();
    }
    public $syncProgress = 0;
    public $syncedImages = [];
    public $failedImages = [];

    private function fetchUrlContent($url)
    {
        $response = Http::get($url);

        if ($response->failed()) {
            throw new Exception("Failed to fetch content from URL: {$url}, Status Code: {$response->status()}");
        }

        return $response->body();
    }

    public function syncImages()
    {
        $this->dispatch('openSyncModal');
        $this->syncProgress = 0;
        $this->syncedImages = [];
        $this->failedImages = [];

        // Ambil semua attachment dari NetStorage
        $attachments = Attachment::where('path', 'like', '%NetStorage%')->get();

        if ($attachments->isEmpty()) {
            $this->dispatch('error', 'No attachments found in NetStorage.');
            $this->dispatch('syncComplete');
            return;
        }

        $totalAttachments = $attachments->count();
        $processed = 0;

        foreach ($attachments as $attachment) {
            try {
                $attachmentFilename = pathinfo($attachment->name, PATHINFO_FILENAME);
                $material = Material::where('code', 'like', "%{$attachmentFilename}%")->first();
                if ($material) {
                    $url = $attachment->getUrl();
                    $response = Http::get($url);
                    if ($response->failed()) {
                        throw new Exception("Failed to fetch image from URL: {$url}");
                    }

                    $imageData = $response->body();
                    $mimeType = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpeg';
                    $dataUri = "data:image/{$mimeType};base64," . base64_encode($imageData);

                    $filename = uniqid() . '.jpg';

                    $filePath = Attachment::saveAttachmentByFileName($dataUri, $material->id, class_basename($material), $filename);

                    if ($filePath !== false) {
                        $this->syncedImages[] = [
                            'material_id' => $material->id,
                            'file_name' => $filename,
                            'path' => $filePath,
                        ];

                        $this->dispatch('pushSyncedImage', [
                            'material_id' => $material->id,
                            'file_name' => $filename,
                            'path' => $filePath,
                        ]);

                        // Hapus attachment yang berhasil disimpan
                        try {
                            Attachment::deleteAttachmentById($attachment->id);
                        } catch (Exception $e) {
                            // Log jika gagal menghapus attachment
                        }
                    } else {
                        throw new Exception("Failed to save attachment: {$filename}");
                    }
                } else {
                    throw new Exception("No material found matching attachment filename: {$attachmentFilename}");
                }
            } catch (Exception $e) {
                $this->failedImages[] = [
                    'file_name' => $attachmentFilename,
                    'error' => $e->getMessage(),
                ];

                $this->dispatch('pushFailedImage', [
                    'file_name' => $attachmentFilename,
                    'error' => $e->getMessage(),
                ]);
            }

            $processed++;
            $this->syncProgress = intval(($processed / $totalAttachments) * 100);
            $this->dispatch('updateSyncProgress', $this->syncProgress);
        }
        if (!empty($this->failedImages)) {
            $this->dispatch('error', 'Some images failed to sync. Please check the list.');
        } else {
            $this->dispatch('success', 'All images synchronized successfully.');
        }

        $this->dispatch('syncComplete');
    }
}
