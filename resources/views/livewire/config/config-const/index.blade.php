<x-ui-page-card title="Config Const" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('config.config-const.index-data-table')
    </div>
</x-ui-page-card>

