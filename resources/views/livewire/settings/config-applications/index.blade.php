<x-ui-page-card title="Config Applications" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('ConfigApplications.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('settings.config-applications.index-data-table')
    </div>
</x-ui-page-card>

