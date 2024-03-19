<x-ui-page-card title="Config Group" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('config.config-group.index-data-table')
    </div>
</x-ui-page-card>

