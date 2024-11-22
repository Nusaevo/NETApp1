<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TrdRetail1\Master\Material;
use App\Models\SysConfig1\ConfigRight;
use App\Models\Util\GenericExport;
use App\Enums\Status;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class IndexDataTable extends BaseDataTableComponent
{
    protected $model = Material::class;

    public function mount(): void
    {
        $this->customRoute = '';
        $this->getPermission($this->customRoute);
        $this->setSearchDisabled();
        $this->setFilter('Status', 0);
        $this->setDefaultSort('created_at', 'desc');
    }

    public function builder(): Builder
    {
        return Material::with(['IvtBal'])->select('materials.*');
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
                    return $imageUrl
                        ? '<img src="' . $imageUrl . '" alt="Foto" style="width: 100px; height: 100px; object-fit: cover;">'
                        : '<span>No Image</span>';
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
            Column::make($this->trans('code'), 'code')
                ->sortable(),
            Column::make($this->trans('barcode'), 'id')
                ->format(function ($value, $row) {
                    return $row->MatlUom[0]->barcode ?? '';
                })
                ->sortable(),
            Column::make($this->trans('remarks'), 'remarks')
                ->sortable(),
            Column::make($this->trans('action'), 'id')
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

    public function filters(): array
    {
        $categories = Material::distinct('category')
            ->pluck('category', 'category')
            ->toArray();
        $brands = Material::distinct('brand')
            ->pluck('brand', 'brand')
            ->toArray();
        $types = Material::distinct('type_code')
            ->pluck('type_code', 'type_code')
            ->toArray();

        return [
            $this->createTextFilter('Barang', 'name', 'Cari Kode Barang', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), '=', strtoupper($value));
            }),
            SelectFilter::make($this->trans('kategori'), 'kategori')
                ->options(['' => 'All'] + $categories) // Add 'All' option manually
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('category', $value);
                    }
                })
                ->setWireLive(),
            SelectFilter::make($this->trans('brand'), 'brand')
                ->options(['' => 'All'] + $brands) // Add 'All' option manually
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('brand', $value);
                    }
                })
                ->setWireLive(),
            SelectFilter::make($this->trans('type'), 'type_code')
                ->options(['' => 'All'] + $types) // Add 'All' option manually
                ->filter(function (Builder $builder, string $value) {
                    if ($value !== '') {
                        $builder->where('type_code', $value);
                    }
                })
                ->setWireLive(),
            SelectFilter::make('Status', 'Status')
                ->options([
                    '0' => 'Active',
                    '1' => 'Non Active',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '0') {
                        $builder->withoutTrashed();
                    } elseif ($value === '1') {
                        $builder->onlyTrashed();
                    }
                })
                ->setWireLive(),
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

    public function bulkActions(): array
    {
        return [
            'downloadTemplate' => 'Download Template',
        ];
    }

    public function downloadTemplate()
    {
        $headers = [
            'Kategori*',    // Required field
            'Merk*',        // Required field
            'Jenis*',       // Required field
            'No',           // Optional field
            'Kode Warna',   // Optional field
            'Nama Warna',   // Optional field
            'UOM*',         // Required field
            'Harga Jual*',  // Required field
            'STOK',         // Optional field
            'Kode Barang',  // Optional field
            'Kode Barcode', // Optional field
            'Keterangan'    // Optional field
        ];

        $filename = Material::FILENAME_PREFIX . now()->format('Y-m-d') . '.xlsx';

        return \Excel::download(new GenericExport([], $headers, Material::SHEET_NAME), $filename);
    }


}
