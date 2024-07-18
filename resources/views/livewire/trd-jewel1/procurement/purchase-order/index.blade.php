<x-ui-page-card title="{{ $menuName }}" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-container">
        @livewire('trd-jewel1.procurement.purchase-order.index-data-table')
    </div>
</x-ui-page-card>

