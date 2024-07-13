<x-ui-page-card title="Config Menu" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-index">
        @livewire('sys-config1.config-menu.index-data-table')
    </div>
</x-ui-page-card>

