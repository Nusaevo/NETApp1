<x-ui-footer>
    @if ($actionValue !== 'Create' && (!$object instanceof App\Models\SysConfig1\ConfigUser || auth()->user()->id !== $object->id))
    @if(isset($permissions['delete']) && $permissions['delete'])
    <div style="padding-right: 10px;">
        @include('layout.customs.buttons.disable')
    </div>
    @endif

    @endif
    <div>
        <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
    </div>
</x-ui-footer>
