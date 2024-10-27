<div>
    <x-ui-page-card title="{!! $menuName !!}">
        <div>
            @livewire($baseRenderRoute.'.storage-component', ['isDialogBoxComponent' => false])
        </div>
    </x-ui-page-card>
</div>

