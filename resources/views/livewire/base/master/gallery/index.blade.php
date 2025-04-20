<div>
    <x-ui-page-card title="{!! $menuName !!}">
        <div>
            @livewire($currentRoute.'.storage-component', ['isComponent' => false])
        </div>
    </x-ui-page-card>
</div>

