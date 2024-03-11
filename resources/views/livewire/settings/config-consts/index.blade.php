<x-ui-page-card title="Config Consts" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('ConfigConsts.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('settings.config-consts.index-data-table')
    </div>
</x-ui-page-card>

