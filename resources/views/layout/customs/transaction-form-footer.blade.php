@php
// Ensure the route is correctly prepared
$printPdfRoute = preg_replace('/\.[^.]+$/', '.PrintPdf', $baseRoute);
@endphp

@if ($actionValue === 'Edit')
<x-ui-button :action="$actionValue" click-event="{{ route($printPdfRoute,
['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($object->id)]) }}"
    cssClass="btn-primary" type="Route" loading="true" button-name="Print" iconPath="print.svg" />
@endif
<x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
