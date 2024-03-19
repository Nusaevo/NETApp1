<x-ui-page-card title="Config Var" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('config.config-var.index-data-table')
    </div>
</x-ui-page-card>

