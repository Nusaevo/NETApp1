<x-ui-page-card title="Data Customers" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('Customers.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('masters.customers.index-data-table')
    </div>
</x-ui-page-card>

