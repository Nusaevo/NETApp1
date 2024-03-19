<x-ui-page-card title="Data Materials" status="{{ $status }}">
    @include('layout.customs.buttons.create', [
    'clickEvent' => route('Materials.Detail', ['action' => encryptWithSessionKey('Create')])
    ])
    <div class="table-responsive">
        @livewire('masters.materials.index-data-table')
    </div>
</x-ui-page-card>

