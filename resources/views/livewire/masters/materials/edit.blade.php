<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="" type="Back" button-name="Back"/>
        </div>
    </div>
     @livewire('masters.materials.material-form', ['action' => $action, 'objectId' => $objectId])
</div>
