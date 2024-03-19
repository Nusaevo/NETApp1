<x-ui-page-card title="Data Suppliers" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
        'clickEvent' => route('Suppliers.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('masters.suppliers.index-data-table')
    </div>
</x-ui-page-card>

