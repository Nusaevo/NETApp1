<x-ui-page-card title="Config Application" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('sys-config1.config-application.index-data-table')
    </div>
</x-ui-page-card>

