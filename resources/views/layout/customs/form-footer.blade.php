<x-ui-footer>
    @if ($actionValue !== 'Create' && (!$object instanceof App\Models\SysConfig1\ConfigUser || auth()->user()->id !== $object->id))
    @if(isset($permissions['delete']) && $permissions['delete'])
    <div style="padding-right: 10px;">
        @if ($status === 'ACTIVE' || !$object->deleted_at)
        <x-ui-button button-name="Disable" click-event="" loading="true" :action="$actionValue" cssClass="btn-danger btn-dialog-box" iconPath="disable.svg" />
        @else
        <x-ui-button button-name="Enable" click-event="" loading="true" :action="$actionValue" cssClass="btn-success btn-dialog-box" iconPath="enable.svg" />
        @endif
    </div>
    @endif

    @endif
    <div>
        <x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
    </div>
</x-ui-footer>

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
                    Livewire.emit('changeStatus');
                }
            });
        });
    });

</script>

