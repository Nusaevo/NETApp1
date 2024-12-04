<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdRetail1\Master\Material;
use App\Models\SysConfig1\ConfigRight;
use App\Models\Util\GenericExport;
use App\Services\TrdRetail1\Master\MasterService;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Material::class;

    protected $masterService;
    public $filters = [];
    public $materialCategories;
    public function mount(): void
    {
        $this->customRoute = '';
        $this->masterService = new MasterService();
        $this->materialCategories = $this->masterService->getMatlCategoryData(); // Mengambil kategori material
        $this->setSearchDisabled();
        $this->setDefaultSort('created_at', 'desc');
        $this->setFilter('kategori', "");
        $this->setFilter('brand', "");
        $this->setFilter('type_code', "");
    }

    public function builder(): Builder
    {
        $query = Material::query();
        return $query->with(['IvtBal'])->select('materials.*');
    }


    public function columns(): array
    {
        return [
            Column::make($this->trans('color_code'), 'specs->color_code')
                ->format(function ($value, $row) {
                    return $row['specs->color_code'] ?? '';
                })
                ->sortable(),
            Column::make($this->trans('color_name'), 'specs->color_name')
                ->format(function ($value, $row) {
                    return $row['specs->color_name'] ?? '';
                })
                ->sortable(),
            Column::make($this->trans('photo'), 'id')
                ->format(function ($value, $row) {
                    $firstAttachment = $row->Attachment->first();
                    $imageUrl = $firstAttachment ? $firstAttachment->getUrl() : null;
                    return $imageUrl ? '<img src="' . $imageUrl . '" alt="Foto" style="width: 100px; height: 100px; object-fit: cover;">' : '<span>No Image</span>';
                })
                ->html(),
            Column::make($this->trans('uom'), 'id')
                ->format(function ($value, $row) {
                    return $row->MatlUom[0]->matl_uom ?? '';
                })
                ->sortable(),
            Column::make($this->trans('selling_price'), 'selling_price_text')
                ->label(function ($row) {
                    return $row->selling_price_text;
                })
                ->sortable(),
            Column::make($this->trans('stock'), 'IvtBal.qty_oh')
                ->format(function ($value, $row) {
                    return $row->IvtBal?->qty_oh ?? 0;
                })
                ->sortable(),
            Column::make($this->trans('code'), 'code')->sortable(),
            Column::make($this->trans('barcode'), 'id')
                ->format(function ($value, $row) {
                    return $row->MatlUom[0]->barcode ?? '';
                })
                ->sortable(),
            Column::make($this->trans('remarks'), 'remarks')->sortable(),
            Column::make($this->trans('action'), 'id')->format(function ($value, $row, Column $column) {
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
    public function filters(): array
    {
        $filters = [];

        // Kategori Filter
        $kategoriOptions = array_merge(
            ['' => 'Pilih Kategori'],
            collect($this->materialCategories)->pluck('label', 'value')->toArray()
        );
        $filters[] = SelectFilter::make($this->trans('Kategori'), 'kategori')
            ->options($kategoriOptions)
            ->filter(function (Builder $builder, string $value) {
                $this->filters['kategori'] = $value;
                if ($this->filters['kategori'] === '' && $this->filters['brand'] === '' && $this->filters['type_code'] === '') {
                    return $builder->whereRaw('1 = 0');
                }

                $builder->where('category', $value);
            });

        // Brand Filter
        $brandOptions = array_merge(
            ['' => 'Pilih Merek'], // Prepend "Pilih Merek"
            Material::distinct('brand')->pluck('brand', 'brand')->toArray()
        );
        $filters[] = SelectFilter::make($this->trans('Brand'), 'brand')
            ->options($brandOptions)
            ->filter(function (Builder $builder, string $value) {
                $this->filters['brand'] = $value;

                if ($this->filters['kategori'] === '' && $this->filters['brand'] === '' && $this->filters['type_code'] === '') {
                    return $builder->whereRaw('1 = 0');
                }


                $builder->where('brand', $value);
            });

        // Type Filter
        $typeOptions = array_merge(
            ['' => 'Pilih Jenis'], // Prepend "Pilih Jenis"
            Material::distinct('type_code')->pluck('type_code', 'type_code')->toArray()
        );
        $filters[] = SelectFilter::make($this->trans('Type'), 'type_code')
            ->options($typeOptions)
            ->filter(function (Builder $builder, string $value) {
                $this->filters['type_code'] = $value;

                if ($this->filters['kategori'] === '' && $this->filters['brand'] === '' && $this->filters['type_code'] === '') {
                    return $builder->whereRaw('1 = 0');
                }


                $builder->where('type_code', $value);
            });

        return $filters;
    }


    public function bulkActions(): array
    {
        return [
            'downloadCreateTemplate' => 'Download Create Template',
            'downloadUpdateTemplate' => 'Download Update Template',
        ];
    }

    public function downloadCreateTemplate()
    {
        $headers = [
            'Kategori*', // Required field
            'Merk*', // Required field
            'Jenis*', // Required field
            'No*', // Required field
            'Kode Warna', // Optional field
            'Nama Warna', // Optional field
            'UOM*', // Required field
            'Harga Jual*', // Required field
            'Keterangan', // Optional field
            'Kode Barcode', // Optional field
        ];

        $filename = 'Material_Create_Template_' . now()->format('Y-m-d') . '.xlsx';

        return \Excel::download(new GenericExport([], $headers, 'Material_Create_Template'), $filename);
    }

    public function downloadUpdateTemplate()
    {
        $headers = [
            'No*',            // Required field
            'Kode Warna',     // Optional field
            'Nama Warna',     // Optional field
            'UOM*',           // Required field
            'Harga Jual*',    // Required field
            'STOK',           // Optional field
            'Kode Barang',    // Required field
            'Kode Barcode',   // Optional field
            'Nama Barang',    // Optional field
            'Non Aktif',      // Optional field
            'Keterangan',     // Optional field
            'Version',        // Optional field
        ];

        $selectedIds = $this->getSelected(); // IDs of the selected rows
        $data = [];

        // Fetch materials for the selected IDs
        foreach ($selectedIds as $id) {
            $material = Material::find($id);
            if ($material) {
                $specs = is_array($material->specs) ? $material->specs : json_decode($material->specs, true);

                $data[] = [
                    'No*' => $id,                                    // Tambahkan ID sebagai nomor urut
                    'Kode Warna' => $specs['color_code'] ?? '',      // JSON field
                    'Nama Warna' => $specs['color_name'] ?? '',      // JSON field
                    'UOM*' => $material->MatlUom[0]->matl_uom ?? '', // Relational field
                    'Harga Jual*' => $material->selling_price ?? '',
                    'STOK' => $material->stock ?? '',
                    'Kode Barang' => $material->code ?? '',
                    'Kode Barcode' => $material->MatlUom[0]->barcode ?? '',
                    'Nama Barang' => $material->name ?? '',
                    'Non Aktif' => $material->deleted_at ? 'Yes' : 'No',
                    'Keterangan' => $material->remarks ?? '',
                    'Version' => $material->version_number ?? '',
                ];
            }
        }

        $filename = Material::FILENAME_PREFIX . '_Update_Template_' . now()->format('Y-m-d') . '.xlsx';

        return \Excel::download(new GenericExport($data, $headers, Material::FILENAME_PREFIX . '_Update_Template'), $filename);
    }


}
