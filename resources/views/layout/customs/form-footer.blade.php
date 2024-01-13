<div class="card-footer d-flex justify-content-end">
    @if ($actionValue !== 'Create' && (!$object instanceof App\Models\Settings\ConfigUser || auth()->user()->id !== $object->id))
        <div style="padding-right: 10px;">
            @if ($status === 'Active')
                <x-ui-button button-name="Disable" click-event="" loading="true" :action="$actionValue" cssClass="btn-danger btn-dialog-box" iconPath="images/disable-icon.svg" />
            @else
                <x-ui-button button-name="Enable" click-event="" loading="true" :action="$actionValue" cssClass="btn-success btn-dialog-box" iconPath="images/enable-icon.png" />
            @endif
        </div>
        @include('layout.customs.modal', ['modal_listener' => 'changeStatus'])
    @endif
    <div>
        <x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
    </div>
</div>
