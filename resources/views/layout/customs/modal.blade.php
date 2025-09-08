<script>
        $(document).on('click', '.btn-dialog-box', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Konfirmasi Aksi",
                text: "Apakah Anda yakin ingin melanjutkan tindakan ini?",
                icon: "question",
                iconColor: "#3085d6",
                background: "#fff",
                backdrop: "rgba(0,0,0,0.4)",
                buttonsStyling: false,
                showConfirmButton: true,
                showCancelButton: true,
                confirmButtonText: "<i class='fas fa-check me-1'></i>Ya, Lanjutkan",
                cancelButtonText: "<i class='fas fa-times me-1'></i>Batal",
                closeOnConfirm: false,
                allowOutsideClick: false,
                allowEscapeKey: true,
                customClass: {
                    popup: "rounded-3 shadow-lg",
                    title: "fw-bold text-dark fs-4 mb-3",
                    htmlContainer: "text-muted fs-6 mb-4",
                    confirmButton: "btn btn-primary me-3 px-4 py-2 rounded-pill",
                    cancelButton: "btn btn-outline-secondary px-4 py-2 rounded-pill",
                    actions: "gap-2 mt-4"
                }
            }).then(confirm => {
                if (confirm.isConfirmed) {
                    Livewire.dispatch('{{ $modal_listener }}');
                }
            });
        });
</script>
