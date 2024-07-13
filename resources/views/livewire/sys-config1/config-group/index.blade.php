<x-ui-page-card title="Config Group" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-container">
        @livewire('sys-config1.config-group.index-data-table')
    </div>
</x-ui-page-card>

