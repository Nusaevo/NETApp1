<script>
    document.addEventListener('livewire:load', function() {
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Apakah Anda Yakin ingin melanjutkannya?",
                text: "",
                icon: "question",
                buttonsStyling: false,
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: "Yes",
                cancelButtonText: "No",
                closeOnConfirm: false,
                customClass: {
                    confirmButton: "btn btn-primary",
                    cancelButton: 'btn btn-secondary'
                }
            }).then(confirm => {
                if (confirm.isConfirmed) {
                    Livewire.emit('{{ $modal_listener }}');
                }
            });
        });
    });
</script>
