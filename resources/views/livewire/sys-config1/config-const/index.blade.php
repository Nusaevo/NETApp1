<x-ui-page-card title="Config Const" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-index">
        @livewire('sys-config1.config-const.index-data-table')
    </div>
</x-ui-page-card>

