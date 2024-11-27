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
            ['-1' => 'Pilih Kategori'],
            collect($this->materialCategories)->pluck('label', 'value')->toArray()
        );
        $filters[] = SelectFilter::make($this->trans('Kategori'), 'kategori')
            ->options($kategoriOptions)
            ->filter(function (Builder $builder, string $value) {
                $this->filters['kategori'] = $value;
                if (empty($this->filters['kategori']) && empty($this->filters['brand']) && empty($this->filters['type_code'])) {
                    return $builder->whereRaw('1 = 0');
                }

                $builder->where('category', $value);
            });

        // Brand Filter
        $brandOptions = array_merge(
            ['-1' => 'Pilih Merek'], // Prepend "Pilih Merek"
            Material::distinct('brand')->pluck('brand', 'brand')->toArray()
        );
        $filters[] = SelectFilter::make($this->trans('Brand'), 'brand')
            ->options($brandOptions)
            ->filter(function (Builder $builder, string $value) {
                $this->filters['brand'] = $value;

                if (empty($this->filters['kategori']) && empty($this->filters['brand']) && empty($this->filters['type_code'])) {
                    return $builder->whereRaw('1 = 0');
                }

                $builder->where('brand', $value);
            });

        // Type Filter
        $typeOptions = array_merge(
            ['-1' => 'Pilih Jenis'], // Prepend "Pilih Jenis"
            Material::distinct('type_code')->pluck('type_code', 'type_code')->toArray()
        );
        $filters[] = SelectFilter::make($this->trans('Type'), 'type_code')
            ->options($typeOptions)
            ->filter(function (Builder $builder, string $value) {
                $this->filters['type_code'] = $value;

                if (empty($this->filters['kategori']) && empty($this->filters['brand']) && empty($this->filters['type_code'])) {
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
            'Kategori', // Optional field
            'Merk*', // Required field
            'Jenis*', // Required field
            'No*', // Required field
            'Kode Warna', // Optional field
            'Nama Warna', // Optional field
            'UOM*', // Required field
            'Harga Jual*', // Required field
            'STOK', // Optional field
            'Kode Barang', // Optional field
            'Kode Barcode', // Optional field
            'Nama Barang', // Optional field
            'Non Aktif', // Optional field
            'Keterangan', // Optional field
            'Version', // Optional field
        ];

        $selectedIds = $this->getSelected(); // IDs of the selected rows
        $selectedRows = Material::whereIn('id', $selectedIds)->get();

        $data = $selectedRows
            ->map(function ($row) {
                return [
                    'Kategori' => $row->category ?? '',
                    'Merk*' => $row->brand ?? '',
                    'Jenis*' => $row->type_code ?? '',
                    'No*' => $row->id,
                    'Kode Warna' => $row->specs['color_code'] ?? '',
                    'Nama Warna' => $row->specs['color_name'] ?? '',
                    'UOM*' => $row->MatlUom[0]->uom ?? '',
                    'Harga Jual*' => $row->selling_price ?? '',
                    'STOK' => $row->stock ?? '',
                    'Kode Barang' => $row->code ?? '',
                    'Kode Barcode' => $row->MatlUom[0]->barcode ?? '',
                    'Nama Barang' => $row->name ?? '',
                    'Non Aktif' => $row->deleted_at ? 'Yes' : 'No',
                    'Keterangan' => $row->remarks ?? '',
                    'Version' => $row->version_number ?? '',
                ];
            })
            ->toArray();

        $filename = 'Material_Update_Template_' . now()->format('Y-m-d') . '.xlsx';

        return \Excel::download(new GenericExport($data, $headers, 'Material_Update_Template'), $filename);
    }
}
