<button wire:click="{{ $clickEvent }}" wire:loading.attr="disabled" class="btn btn-primary" @if (!$enabled) disabled @endif @if (!$visible) style="display: none;" @endif>
    <span wire:loading.remove>
        {{ $buttonName ?? 'button' }}
    </span>
    <span wire:loading>
        <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
    </span>
</button>
