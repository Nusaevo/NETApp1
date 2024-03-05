<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('materials.index') }}" type="Back" button-name="Back" />
        </div>
    </div>
        @livewire('masters.materials.material-form', ['materialActionValue' => $actionValue, 'materialIDValue' => $objectIdValue])
</div>
