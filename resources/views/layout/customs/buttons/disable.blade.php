@if ($status === 'ACTIVE' || !$object->deleted_at)
<x-ui-button button-name="Non Active" clickEvent="" loading="true" :action="$actionValue" cssClass="btn-danger btn-dialog-box" iconPath="disable.svg" />
@else
<x-ui-button button-name="Active" clickEvent="" loading="true" :action="$actionValue" cssClass="btn-success btn-dialog-box" iconPath="enable.svg" />
@endif

<script>
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
                Livewire.dispatch('changeStatus');
            }
        });
    });

</script>

