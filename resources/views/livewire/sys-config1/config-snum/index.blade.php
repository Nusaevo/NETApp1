<x-ui-page-card title="Config Snum" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-container">
        @livewire('sys-config1.config-snum.index-data-table')
    </div>
</x-ui-page-card>

