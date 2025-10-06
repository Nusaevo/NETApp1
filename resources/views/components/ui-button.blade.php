@if(isset($type) && $type == 'Route')
<span style="padding: 2px;">
    <a href="{{ isset($clickEvent) ? $clickEvent : '' }}" class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-route" @if (isset($visible) && $visible==='false' ) style="display: none;" @endif>
        @if (isset($iconPath) && $iconPath)
        <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
        @endif
        <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : 'button' }}</span>
    </a>
</span>
@elseif(isset($type) && $type == 'Back')
<div id="backButtonContainer">
    <button type="button" id="backButton" class="btn btn-outline-primary d-inline-flex align-items-center me-3 mb-2" wire:click="goBack" wire:loading.attr="disabled" title="Go back" aria-label="Go back to previous page">
        <i class="bi bi-arrow-left-circle-fill fs-5 me-2" aria-hidden="true"></i>
        <span class="d-none d-sm-inline" id="backButtonText" wire:loading.remove>Back</span>
        <span class="spinner-border spinner-border-sm ms-2 visually-hidden" id="backButtonLoading" role="status" aria-hidden="true" wire:loading></span>
    </button>
</div>

@elseif(isset($type) && $type == 'BackManual')
<div id="backManualButtonContainer">
    <a href="{{ isset($clickEvent) ? $clickEvent : '#' }}" id="backManualButton" class="btn btn-outline-primary d-inline-flex align-items-center me-3 mb-2" onclick="handleBackManualClick(event, this)" title="Go back">
        <i class="bi bi-arrow-left-circle-fill fs-5 me-2" aria-hidden="true"></i>
        <span class="d-none d-sm-inline" id="backManualButtonText">Back</span>
        <span class="spinner-border spinner-border-sm ms-2 visually-hidden" id="backManualButtonLoading" role="status" aria-hidden="true"></span>
    </a>
</div>

<script>
    // Small helper to show a spinner and navigate for manual back links
    function handleBackManualClick(e, el) {
        e.preventDefault();
        const spinner = el.querySelector('.spinner-border');
        const txt = el.querySelector('#backManualButtonText');
        if (spinner) spinner.classList.remove('visually-hidden');
        if (txt) txt.classList.add('visually-hidden');

        const href = el.getAttribute('href');
        setTimeout(() => {
            if (!href || href === '#') {
                // Smart back navigation that skips dropdown endpoints
                if (history.length > 1) {
                    // Check if current URL is a dropdown endpoint
                    const currentUrl = window.location.href;

                    // Check if current URL contains search-dropdown
                    const shouldSkipCurrent = currentUrl.includes('search-dropdown');

                    if (shouldSkipCurrent) {
                        // If we're currently on a dropdown endpoint, go back twice
                        // First back() will go to the page that made the dropdown request
                        // But we might still be on dropdown URL, so we need to check and go back again if needed
                        history.back();

                        // Add a small delay to allow history to update, then check again
                        setTimeout(() => {
                            const newUrl = window.location.href;
                            const stillOnDropdown = newUrl.includes('search-dropdown');

                            if (stillOnDropdown && history.length > 1) {
                                history.back(); // Go back one more time
                            }
                        }, 100);
                    } else {
                        // Normal back navigation
                        history.back();
                    }
                } else {
                    window.location.href = '/';
                }
            } else {
                window.location.href = href;
            }
        }, 80);
    }
</script>
@elseif(isset($type) && $type == 'delete')
@php
    // Check if user has delete permission
    $hasDeletePermission = empty($permissions) || (isset($permissions['delete']) && $permissions['delete']);

    // Check if object can be deleted (status and not deleted)
    $canDelete = empty($object) || ($status === 'OPEN' || !$object->deleted_at);
@endphp

@if($hasDeletePermission && $canDelete)
<span style="padding: 2px;">
    @if(isset($enableConfirmationDialog) && $enableConfirmationDialog === 'true')
        {{-- Delete button WITH confirmation dialog --}}
        <button type="button"
                @if (isset($id)) id="{{ $id }}" @endif
                class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action btn-delete-dialog"
                @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
                @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
                data-click-event="{{ isset($clickEvent) ? $clickEvent : '' }}"
                @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
                @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif>
            @if (isset($iconPath) && $iconPath)
            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
            @endif
            <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
        </button>
    @else
        {{-- Delete button WITHOUT confirmation dialog --}}
        <button type="button"
                @if (isset($id)) id="{{ $id }}" @endif
                class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
                @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
                @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
                @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
                @if (isset($loading) && $loading === 'true') wire:loading.attr="disabled" wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}" @endif
                @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
                @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif>

            @if (isset($loading) && $loading === 'true')
            <span wire:loading.remove wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
                @if (isset($iconPath) && $iconPath)
                <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
                @endif
                <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
            </span>
            <span wire:loading wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
                <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
            </span>
            @else
            @if (isset($iconPath) && $iconPath)
            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
            @endif
            <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
            @endif
        </button>
    @endif
</span>

@if(isset($enableConfirmationDialog) && $enableConfirmationDialog === 'true')
<script>
    $(document).on('click', '.btn-delete-dialog', function(e) {
        e.preventDefault();

        const clickEvent = $(this).data('click-event');

        Swal.fire({
            title: "Konfirmasi Penghapusan",
            text: "Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.",
            icon: "warning",
            iconColor: "#f27474",
            background: "#fff",
            backdrop: "rgba(0,0,0,0.4)",
            buttonsStyling: false,
            showConfirmButton: true,
            showCancelButton: true,
            confirmButtonText: "<i class='fas fa-trash me-1'></i>Ya, Hapus",
            cancelButtonText: "<i class='fas fa-times me-1'></i>Batal",
            closeOnConfirm: false,
            allowOutsideClick: false,
            allowEscapeKey: true,
            customClass: {
                popup: "rounded-3 shadow-lg",
                title: "fw-bold text-dark fs-4 mb-3",
                htmlContainer: "text-muted fs-6 mb-4",
                confirmButton: "btn btn-danger me-3 px-4 py-2 rounded-pill",
                cancelButton: "btn btn-outline-secondary px-4 py-2 rounded-pill",
                actions: "gap-2 mt-4"
            }
        }).then(result => {
            if (result.isConfirmed && clickEvent) {
                Livewire.dispatch(clickEvent);
            }
        });
    });
</script>
@endif
@endif
@elseif(isset($type) && $type == 'save')
<span style="padding: 2px;">
    @if (isset($loading) && $loading === 'true')
    <button type="button"
            @if (isset($id)) id="{{ $id }}" @endif
            class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
            @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
            @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
            @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
            wire:loading.attr="disabled"
            @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
            @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif
            wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">

        <span wire:loading.remove wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
            @if (isset($iconPath) && $iconPath)
            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
            @endif
            <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
        </span>

        <span wire:loading wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
            <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
        </span>
    </button>

    @else
    <button type="button"
            @if (isset($id)) id="{{ $id }}" @endif
            class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
            @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
            @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
            @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
            @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
            @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif>
        @if (isset($iconPath) && $iconPath)
        <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
        @endif
        <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
    </button>
    @endif
</span>
@elseif(isset($type) && $type == 'InputButton')
    @if (isset($loading) && $loading === 'true')
    <button type="button"
            @if (isset($id)) id="{{ $id }}" @endif
            class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
            @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
            @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
            @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
            wire:loading.attr="disabled"
            @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
            @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif
            wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">

        <span wire:loading.remove wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
            @if (isset($iconPath) && $iconPath)
            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
            @endif
            <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
        </span>

        <span wire:loading wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
            <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
        </span>
    </button>

    @else
    <button type="button"
            @if (isset($id)) id="{{ $id }}" @endif
            class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
            @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
            @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
            @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
            @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
            @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif>
        <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
    </button>
    @endif
@else
<span style="padding: 2px;">
    @if (isset($loading) && $loading === 'true')
    <button type="button"
            @if (isset($id)) id="{{ $id }}" @endif
            class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
            @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
            @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
            @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
            wire:loading.attr="disabled"
            @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
            @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif
            wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">

        <span wire:loading.remove wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
            @if (isset($iconPath) && $iconPath)
            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
            @endif
            <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
        </span>

        <span wire:loading wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
            <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
        </span>
    </button>

    @else
    <button type="button"
            @if (isset($id)) id="{{ $id }}" @endif
            class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
            @if (!(isset($enabled) && $enabled === 'always') && (isset($enabled) && $enabled === 'false' || (isset($action) && $action === 'View'))) disabled @endif
            @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
            @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
            @if (isset($dataBsTarget) && $dataBsTarget !=='' ) data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal" @endif
            @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif>
        <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
    </button>
    @endif
</span>
@endif
