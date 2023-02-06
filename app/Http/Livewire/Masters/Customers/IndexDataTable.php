<?php

namespace App\Http\Livewire\Masters\Customers;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class IndexDataTable extends DataTableComponent
{
    protected $model = Customer::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    protected $listeners = [
        'master_customer_refresh' => 'render',
    ];

    public function columns(): array
    {
        return [
            Column::make("Nama", "name")
                ->searchable()
                ->sortable(),
            Column::make("Alamat", "address")
                ->sortable(),
            Column::make("Kota", "city")
                ->sortable(),
            Column::make("Nomor Contact", "contact_number")
                ->sortable(),
            Column::make("Email", "email")
                ->sortable(),
            Column::make('Aksi', 'id')
                ->format(
                    fn ($value, $row, Column $column) => view('livewire.masters.customers.index-data-table-action')->withRow($row)
                ),
        ];
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Tampilkan Terhapus')
                ->options([
                    '0' => 'Tidak',
                    '1' => 'Saja',
                    '2' => 'Semua',
                ])->filter(function (Builder $builder, string $value) {
                    if ($value === '0') $builder->withoutTrashed();
                    else if ($value === '1') $builder->onlyTrashed()->select('*');
                    else if ($value === '2') $builder->withTrashed()->select('*');
                }),
        ];
    }
}
