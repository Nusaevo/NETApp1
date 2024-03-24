<x-ui-page-card title="Data Partners" status="{{ $status }}">
    @include('layout.customs.buttons.create', ['route' => $route])

    <div class="table-responsive">
        @livewire('trd-jewel1.master.partner.index-data-table')
    </div>
</x-ui-page-card>

