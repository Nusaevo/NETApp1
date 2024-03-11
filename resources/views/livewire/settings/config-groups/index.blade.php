<x-ui-page-card title="Config Groups" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('ConfigGroups.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('settings.config-groups.index-data-table')
    </div>
</x-ui-page-card>

