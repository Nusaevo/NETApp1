<x-ui-page-card title="Config User" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-index">
        @livewire('sys-config1.config-user.index-data-table')
    </div>
</x-ui-page-card>

