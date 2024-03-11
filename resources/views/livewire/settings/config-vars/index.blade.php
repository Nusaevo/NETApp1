<x-ui-page-card title="Config Vars" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('ConfigVars.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('settings.config-vars.index-data-table')
    </div>
</x-ui-page-card>

