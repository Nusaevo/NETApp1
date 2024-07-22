@php
// Ensure the route is correctly prepared
$printPdfRoute = preg_replace('/\.[^.]+$/', '.PrintPdf', $baseRoute);
@endphp

@if ($actionValue === 'Edit' || $actionValue === 'View')

@if ($status === 'OPEN' || !$object->deleted_at)
@if(isset($permissions['delete']) && $permissions['delete'])
<x-ui-button button-name="Delete" clickEvent="" loading="true" action="Edit" cssClass="btn-danger btn-dialog-box" iconPath="delete.svg" />
@endif
@endif
<x-ui-button :action="$actionValue" clickEvent="{{ route($printPdfRoute,
['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($object->id)]) }}"
    cssClass="btn-primary" type="Route" loading="true" button-name="Print" iconPath="print.svg" />
@endif
{{-- <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" /> --}}

<script>
    document.addEventListener('livewire:load', function() {
        $(document).on('click', '.btn-dialog-box', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Apakah Anda Yakin ingin melanjutkannya?"
                , text: ""
                , icon: "question"
                , buttonsStyling: false
                , showConfirmButton: true
                , showCancelButton: true
                , confirmButtonText: "Yes"
                , cancelButtonText: "No"
                , closeOnConfirm: false
                , customClass: {
                    confirmButton: "btn btn-primary"
                    , cancelButton: 'btn btn-secondary'
                }
            }).then(confirm => {
                if (confirm.isConfirmed) {
                    Livewire.dispatch('delete');
                }
            });
        });
    });
</script>
