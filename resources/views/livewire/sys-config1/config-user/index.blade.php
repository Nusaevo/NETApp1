<x-ui-page-card title="{{ $menuName }}" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-container">
        @livewire('sys-config1.config-user.index-data-table')
    </div>
</x-ui-page-card>

