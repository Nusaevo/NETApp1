<x-ui-page-card title="Product" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-index">
        @livewire('trd-jewel1.master.material.index-data-table')
    </div>
</x-ui-page-card>

