
<div class="text-center" style="position: relative;">
    @if($enable_this_row)
    <div class="dropdown" style="position: relative;">
        <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton{{ $row->id }}" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="svg-icon svg-icon-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                    <path d="M12 8C13.1 8 14 7.1 14 6C14 4.9 13.1 4 12 4C10.9 4 10 4.9 10 6C10 7.1 10.9 8 12 8Z" fill="currentColor"></path>
                    <path d="M12 14C13.1 14 14 13.1 14 12C14 10.9 13.1 10 12 10C10.9 10 10 10.9 10 12C10 13.1 10.9 14 12 14Z" fill="currentColor"></path>
                    <path d="M12 20C13.1 20 14 19.1 14 18C14 16.9 13.1 16 12 16C10.9 16 10 16.9 10 18C10 19.1 10.9 20 12 20Z" fill="currentColor"></path>
                </svg>
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton{{ $row->id }}">
            @if($allow_details && isset($permissions['read']) && $permissions['read'])
            <li>
                <a class="dropdown-item btn btn-sm" href="#" wire:click="viewData({{ $row->id }})">Detil</a>
            </li>
            @endif
            @if($allow_edit && (isset($permissions['read']) && $permissions['read'] || isset($permissions['update']) && $permissions['update']))
            <li>
                <a class="dropdown-item btn btn-sm" href="#" wire:click="editData({{ $row->id }})">Edit</a>
            </li>
            @endif
            @if($allow_delete && isset($permissions['delete']) && $permissions['delete'])
            <li>
                <a class="dropdown-item btn btn-sm btn-dialog-box" href="#" wire:click="selectData({{ $row->id }})">Delete</a>
            </li>
            @endif
            @if($allow_disable && isset($permissions['delete']) && $permissions['delete'])
            <li>
                <a class="dropdown-item btn btn-sm btn-dialog-box" href="#" wire:click="selectData({{ $row->id }})">Disable</a>
            </li>
            @endif
            @if($custom_actions && isset($custom_actions))
            @foreach ($custom_actions as $action)
            <li>
                <a class="dropdown-item btn btn-sm" href="{{ $action['route'] }}" style="text-decoration: none;">
                    <i class="{{ $action['icon'] }}"></i> {{ $action['label'] }}
                </a>
            </li>
            @endforeach
            @endif
        </ul>
    </div>
    @endif
</div>

<script>
    document.addEventListener('livewire:load', function() {
        var dropdowns = document.querySelectorAll('.dropdown');
        dropdowns.forEach(function(dropdown) {
            dropdown.addEventListener('show.bs.dropdown', function(event) {
                var menu = event.target.querySelector('.dropdown-menu');
                var rect = event.target.getBoundingClientRect();
                menu.style.top = rect.top + window.scrollY + event.target.offsetHeight + 'px';
                menu.style.left = rect.left + window.scrollX + 'px';
                menu.style.position = 'absolute';
            });
        });
    });

    $(document).on('click', '.btn-dialog-box', function(e) {
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
                Livewire.emit('disableData');
            }
        });
    });
</script>
