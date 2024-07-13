<x-ui-page-card title="Currency" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-container">
        @livewire('trd-jewel1.master.currency.index-data-table')
    </div>
</x-ui-page-card>

