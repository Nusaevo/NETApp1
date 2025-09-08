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
{{--  <x-ui-button :action="$actionValue" clickEvent="{{ route($printPdfRoute,
['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($object->id)]) }}"
    cssClass="btn-primary" type="Route" loading="true" button-name="Print" iconPath="print.svg" />  --}}
@endif
{{-- <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" /> --}}

<script>
    $(document).on('click', '.btn-dialog-box', function(e) {
        e.preventDefault();
        Swal.fire({
            title: "Konfirmasi Penghapusan"
            , text: "Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan."
            , icon: "warning"
            , iconColor: "#f27474"
            , background: "#fff"
            , backdrop: "rgba(0,0,0,0.4)"
            , buttonsStyling: false
            , showConfirmButton: true
            , showCancelButton: true
            , confirmButtonText: "<i class='fas fa-trash me-1'></i>Ya, Hapus"
            , cancelButtonText: "<i class='fas fa-times me-1'></i>Batal"
            , closeOnConfirm: false
            , allowOutsideClick: false
            , allowEscapeKey: true
            , customClass: {
                popup: "rounded-3 shadow-lg"
                , title: "fw-bold text-dark fs-4 mb-3"
                , htmlContainer: "text-muted fs-6 mb-4"
                , confirmButton: "btn btn-danger me-3 px-4 py-2 rounded-pill"
                , cancelButton: "btn btn-outline-secondary px-4 py-2 rounded-pill"
                , actions: "gap-2 mt-4"
            }
        }).then(confirm => {
            if (confirm.isConfirmed) {
                Livewire.dispatch('delete');
            }
        });
    });

</script>

