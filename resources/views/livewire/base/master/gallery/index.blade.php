<div>
    <x-ui-page-card title="{!! $menuName !!}">
        <div>
            @livewire($currentRoute.'.storage-component', ['isDialogBoxComponent' => false])
        </div>
    </x-ui-page-card>
</div>

