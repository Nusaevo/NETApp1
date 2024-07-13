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
        <a class="btn btn-link btn-color-info btn-active-color-primary me-3 mb-2" id="backButton">
            <i class="bi bi-arrow-left-circle fs-2 me-1"></i> <span id="backButtonText" style="font-size: 12px;">Back</span>
            <span class="spinner-border spinner-border-sm" id="backButtonLoading" role="status" aria-hidden="true" style="display: none;"></span>
        </a>
    </div>

    <script>
         document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('backButton').addEventListener('click', function(event) {
                event.preventDefault();
                window.history.go(-1);
            });
        });
    </script>
@elseif(isset($type) && $type == 'BackManual')
<div id="backManualButtonContainer">
    <a href="{{ isset($clickEvent) ? $clickEvent : '#' }}" class="btn btn-link btn-color-info btn-active-color-primary me-3 mb-2" id="backManualButton">
        <i class="bi bi-arrow-left-circle fs-2 me-1"></i> <span id="backManualButtonText" style="font-size: 12px;">Back</span>
        <span class="spinner-border spinner-border-sm" id="backManualButtonLoading" role="status" aria-hidden="true" style="display: none;"></span>
    </a>
</div>
@else
    @if (isset($action) && $action !== 'View')
        <span style="padding: 2px;">
            @if (isset($loading) && $loading === 'true')
                <button type="button" @if (isset($id)) id="{{ $id }}" @endif wire:loading.attr="disabled"
                        class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
                        @if (isset($enabled) && $enabled==='false' ) disabled @endif
                        @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
                        @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
                        @if (isset($dataBsTarget) && $dataBsTarget !== '') data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal"  @endif
                        @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif>
                    <span wire:loading.remove>
                        @if (isset($iconPath) && $iconPath)
                            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 20px; height: 20px;">
                        @endif
                        <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
                    </span>
                    <span wire:loading>
                        <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                    </span>
                </button>
            @else
                <button type="button" @if (isset($id)) id="{{ $id }}" @endif
                        class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-action"
                        @if (isset($enabled) && $enabled==='false' ) disabled @endif
                        @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
                        @if (isset($clickEvent) && $clickEvent) wire:click="{{ $clickEvent }}" @endif
                        @if (isset($dataBsTarget) && $dataBsTarget !== '') data-bs-target="{{ $dataBsTarget }}" data-bs-toggle="modal"  @endif
                        @if (isset($jsClick) && $jsClick) onclick="{{ $jsClick }}" @endif>
                    <span style="font-size: 16px;">{{ isset($buttonName) ? $buttonName : '' }}</span>
                </button>
            @endif
        </span>
    @endif
@endif
