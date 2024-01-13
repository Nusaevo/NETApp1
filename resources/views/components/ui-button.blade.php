@if(isset($type) && $type == 'Route')
    <a href="{{ isset($clickEvent) ? $clickEvent : '' }}" class="{{ isset($cssClass) ? $cssClass : '' }}" @if (isset($visible) && $visible === 'false') style="display: none;" @endif style="padding: 5px 10px; font-size: 16px;">
        {{-- @if (isset($iconPath) && $iconPath)
            <img src="{{ asset($iconPath) }}" alt="Icon" style="width: 24px; height: 24px;">
        @endif --}}
        {{ isset($buttonName) ? $buttonName : 'button' }}
    </a>
@elseif(isset($type) && $type == 'Back')
<div>
    <a onclick="backToPreviousUrl()" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2" id="backButton">
        <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Back
    </a>
</div>
<script>
    var backButton = document.getElementById("backButton");

    function backToPreviousUrl() {
        if (!backButton.disabled) {
            backButton.disabled = true;
            var previousUrl = document.referrer;
            history.back();
        }
    }
</script>

@else
    @if (isset($action) && $action !== 'View')
        @if (isset($loading) && $loading === 'true')
        <button type="button" wire:click="{{ isset($clickEvent) ? $clickEvent : '' }}" wire:loading.attr="disabled" class="btn {{ isset($cssClass) ? $cssClass : '' }}" @if (isset($enabled) && $enabled === 'false') disabled @endif @if (isset($visible) && $visible === 'false') style="display: none;" @endif style="padding: 5px 10px; font-size: 16px;">
            <span wire:loading.remove>
                {{-- @if (isset($iconPath) && $iconPath)
                    <img src="{{ asset($iconPath) }}" alt="Icon" style="width: 24px; height: 24px;">
                @endif --}}
                {{ isset($buttonName) ? $buttonName : '' }}
            </span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
            </span>
        </button>
        @else
        <button type="button" wire:click="{{ isset($clickEvent) ? $clickEvent : '' }}" class="btn {{ isset($cssClass) ? $cssClass : '' }}" @if (isset($enabled) && $enabled === 'false') disabled @endif @if (isset($visible) && $visible === 'false') style="display: none;" @endif style="padding: 5px 10px; font-size: 16px;">
            {{-- @if (isset($iconPath) && $iconPath)
                <img src="{{ asset($iconPath) }}" alt="Icon" style="width: 24px; height: 24px;">
            @endif --}}
            {{ isset($buttonName) ? $buttonName : '' }}
        </button>
        @endif
    @endif
@endif
