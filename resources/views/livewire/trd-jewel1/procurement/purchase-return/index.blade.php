<x-ui-page-card title="Transaksi Retur Pembelian" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])
  <div class="table-responsive">
        @livewire('trd-jewel1.procurement.purchase-return.index-data-table')
    </div>
</x-ui-page-card>

