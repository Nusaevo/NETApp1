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

    public function columns(): array
    {
        return [
            Column::make($this->trans('code'), 'code')
                ->format(function ($value, $row) {
                    return '<a href="' .
                        route($this->appCode . '.Master.Material.Detail', [
                            'action' => encryptWithSessionKey('Edit'),
                            'objectId' => encryptWithSessionKey($row->id),
                        ]) .
                        '">' .
                        $row->code .
                        '</a>';
                })
                ->html(),
            Column::make($this->trans('selling_price'), 'selling_price_text')
                ->label(function ($row) {
                    return $row->selling_price_text;
                })
                ->sortable(),
            Column::make('Qty Onhand', 'IvtBal.qty_oh')
                ->format(function ($value, $row, Column $column) {
                    return $row->IvtBal?->qty_oh ?? 0;
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans('status'), 'status_code')
                ->format(function ($value, $row, Column $column) {
                    return Status::getStatusString($value);
                })
                ->searchable()
                ->sortable(),
            Column::make($this->trans('created_date'), 'created_at')->sortable(),
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
        return [
            $this->createTextFilter('Barang', 'name', 'Cari Kode Barang', function (Builder $builder, string $value) {
                $builder->where(DB::raw('UPPER(code)'), 'like', '%' . strtoupper($value) . '%');
            }),
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
        $templateData = [
            ['1. Update kolom yang berwarna kuning'],
            ['2. Kolom warna merah tidak boleh di update (posisi bisa di hide kolom di template)'],
            ['3. Setelah upload selesai, kolom dengan warna putih akan terisi'],
            [], [],
            ['kategori', 'merk', 'jenis', 'No', 'Kode Warna', 'Nama Warna', 'UOM', 'Harga Jual', 'STOK', 'Kode Barang', 'Kode Barcode', 'Keterangan', 'Status'] // Headers
        ];

        $filename = 'Material_Template_' . now()->format('Y-m-d') . '.xlsx';

        return \Excel::download(new GenericExport(collect($templateData), [], 'materials'), $filename);
    }
}
