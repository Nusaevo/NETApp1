<x-ui-page-card title="Transaksi Pembelian" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('trd-jewel1.procurement.purchase-order.index-data-table')
    </div>
</x-ui-page-card>

