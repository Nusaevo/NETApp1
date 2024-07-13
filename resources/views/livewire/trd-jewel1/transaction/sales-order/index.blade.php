<x-ui-page-card title="Transaksi Penjualan" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('trd-jewel1.transaction.sales-order.index-data-table')
    </div>
</x-ui-page-card>

