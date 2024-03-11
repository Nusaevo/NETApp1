<x-ui-page-card title="Config Menus" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('ConfigMenus.Detail', ['action' => encryptWithSessionKey('Create')])
    ])

    <div class="table-responsive">
        @livewire('settings.config-menus.index-data-table')
    </div>
</x-ui-page-card>

