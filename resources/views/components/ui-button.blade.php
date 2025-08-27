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
                if (history.length > 1) history.back();
                else window.location.href = '/';
            } else {
                window.location.href = href;
            }
        }, 80);
    }
</script>
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
