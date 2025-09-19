@php
// Ensure the route is correctly prepared
$printPdfRoute = preg_replace('/\.[^.]+$/', '.PrintPdf', $baseRoute);
@endphp

@if ($actionValue === 'Edit' || $actionValue === 'View')

@if ($status === 'OPEN' || !$object->deleted_at)
@if(isset($permissions['delete']) && $permissions['delete'])
<x-ui-button
    button-name="Delete"
    clickEvent="delete"
    loading="true"
    :action="$actionValue"
    cssClass="btn-danger"
    iconPath="delete.svg"
    type="delete"
    enableConfirmationDialog="true"
    :permissions="$permissions"
/>
@endif
@endif
{{--  <x-ui-button :action="$actionValue" clickEvent="{{ route($printPdfRoute,
['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($object->id)]) }}"
    cssClass="btn-primary" type="Route" loading="true" button-name="Print" iconPath="print.svg" />  --}}
@endif
{{-- <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" /> --}}

