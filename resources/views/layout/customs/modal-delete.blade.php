<script>
        $(document).on('click', '.btn-delete', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Apakah Anda Yakin ingin mengahpus data ini?",
                text: "Tindakan ini tidak dapat diurungkan.",
                icon: "question",
                buttonsStyling: false,
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: "Hapus",
                cancelButtonText: "Batalkan",
                closeOnConfirm: false,
                customClass: {
                    confirmButton: "btn btn-danger",
                    cancelButton: 'btn btn-secondary'
                }
            }).then(confirm => {
                if (confirm.isConfirmed) {
                    Livewire.dispatch('destroy_listener');
                }
            });
        });
</script>
