{{-- @once
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('success', (message) => {
            toastr.success(message);
        });
        Livewire.on('error', (message) => {
            toastr.error(message);
        });

        Livewire.on('notify-swal', (dataArray) => {
            console.log('alert event triggered'); // Debugging
            console.log('Event data:', dataArray); // Debugging

            // Assuming the data is an array, get the first item
            let data = dataArray[0];
            let message = data.message || '';
            let icon = data.type || 'success';
            let confirmButtonText = 'Ok';

            Swal.fire({
                text: message,
                icon: icon,
                buttonsStyling: false,
                confirmButtonText: confirmButtonText,
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
        });
    });
</script>
@endonce --}}
