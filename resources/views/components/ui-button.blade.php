@if($type == 'Route')
    <a href="{{ $clickEvent }}" class="{{ $cssClass ?? '' }}" @if (!$visible) style="display: none;" @endif style="padding: 5px 10px; font-size: 12px;">
        @if ($iconPath)
            <img src="{{ asset($iconPath) }}" alt="Icon" style="width: 24px; height: 24px;">
        @endif
        {{ $buttonName ?? 'button' }}
    </a>
@elseif($type == 'Back')
    <div>
        <a href="{{ $clickEvent }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2">
            <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Back
        </a>
    </div>
@else
    <button wire:click="{{ $clickEvent }}" wire:loading.attr="disabled" class="btn {{ $cssClass ?? '' }}" @if (!$enabled) disabled @endif @if (!$visible) style="display: none;" @endif style="padding: 5px 10px; font-size: 12px;">
        <span wire:loading.remove>
            @if ($iconPath)
                <img src="{{ asset($iconPath) }}" alt="Icon" style="width: 24px; height: 24px;">
            @endif
            {{ $buttonName ?? 'button' }}
        </span>
        <span wire:loading>
            <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
        </span>
    </button>
@endif
