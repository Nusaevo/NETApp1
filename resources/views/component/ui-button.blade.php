@if(isset($type) && $type == 'Route')
<a href="{{ isset($clickEvent) ? $clickEvent : '' }}" class="{{ isset($cssClass) ? $cssClass : '' }}" @if (isset($visible) && $visible==='false' ) style="display: none;" @endif style="padding: 5px 10px; font-size: 16px;">
    @if (isset($iconPath) && $iconPath)
            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 24px; height: 24px;">
    @endif
    {{ isset($buttonName) ? $buttonName : 'button' }}
</a>
@elseif(isset($type) && $type == 'Back')
<div id="backButtonContainer">
    <a class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2" id="backButton">
        <i class="bi bi-arrow-left-circle fs-2 me-2"></i> <span id="backButtonText">Back</span>
        <span class="spinner-border spinner-border-sm" id="backButtonLoading" role="status" aria-hidden="true" style="display: none;"></span>
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('backButton').addEventListener('click', function(event) {
            event.preventDefault();
            backToPreviousUrl();
        });
    });

    function backToPreviousUrl() {
        document.getElementById("backButtonText").style.display = 'none';
        document.getElementById("backButtonLoading").style.display = '';

        window.history.back();

        setTimeout(() => {
            document.getElementById("backButtonText").style.display = '';
            document.getElementById("backButtonLoading").style.display = 'none';
        }, 1000);
    }

</script>


@else
@if (isset($action) && $action !== 'View')
    <span style="padding: 5px;">
        @if (isset($loading) && $loading === 'true')
            <button type="button" @if (isset($id)) id="{{ $id }}" @endif  wire:loading.attr="disabled"
                    class="btn {{ isset($cssClass) ? $cssClass : '' }}"
                    @if (isset($enabled) && $enabled==='false' ) disabled @endif
                    @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
                    style="padding: 5px 10px; font-size: 16px;"
                    @if (isset($onClickJavascript)) onclick="{{ $onClickJavascript }}" @endif
                    @if (isset($clickEvent)) wire:click="{{ $clickEvent }}" @endif>
                    <span wire:loading.remove>
                        @if (isset($iconPath) && $iconPath)
                            <img src="{{ imagePath($iconPath) }}" alt="Icon" style="width: 24px; height: 24px;">
                        @endif
                        {{ isset($buttonName) ? $buttonName : '' }}
                    </span>
                    <span wire:loading>
                        <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                    </span>
            </button>
        @else
            <button type="button" @if (isset($id)) id="{{ $id }}" @endif wire:click="{{ isset($clickEvent) ? $clickEvent : '' }}"
                    class="btn {{ isset($cssClass) ? $cssClass : '' }}"
                    @if (isset($enabled) && $enabled==='false' ) disabled @endif
                    @if (isset($visible) && $visible==='false' ) style="display: none;" @endif
                    style="padding: 5px 10px; font-size: 16px;"
                    @if (isset($onClickJavascript)) onclick="{{ $onClickJavascript }}" @endif
                    @if (isset($clickEvent)) wire:click="{{ $clickEvent }}" @endif>
                {{ isset($buttonName) ? $buttonName : '' }}
            </button>
        @endif
    </span>
@endif
@endif
