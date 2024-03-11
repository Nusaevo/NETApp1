<x-ui-page-card title="Config Users" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('ConfigUsers.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('settings.config-users.index-data-table')
    </div>
</x-ui-page-card>

