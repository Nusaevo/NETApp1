<x-ui-page-card title="Data Materials" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('trd-jewel1.master.material.index-data-table')
    </div>
</x-ui-page-card>

