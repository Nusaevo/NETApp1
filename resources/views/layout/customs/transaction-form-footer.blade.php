<x-ui-footer>
    @php
    // Ensure the route is correctly prepared
    $printPdfRoute = preg_replace('/\.[^.]+$/', '.PrintPdf', $baseRoute);
    @endphp

    @if ($actionValue === 'Edit')
    <x-ui-button :action="$actionValue" click-event="{{ route($printPdfRoute, ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($object->id)]) }}" cssClass="btn-secondary" type="Route" loading="true" button-name="Print" />
    @endif
    <div>
        <x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
    </div>
</x-ui-footer>

