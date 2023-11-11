@if (isset($visible) && $visible === 'true')
<div class="modal" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                @isset($title)
                    <h5 class="modal-title">{{ $title }}</h5>
                @endisset
            </div>
            <div class="modal-body">
                @isset($body)
                    {{ $body }}
                @endisset
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @isset($cancelAction) wire:click="{{ $cancelAction }}" @endisset>Cancel</button>
                <button type="button" class="btn btn-primary" @isset($continueAction) wire:click="{{ $continueAction }}" @endisset>Yes</button>
            </div>
        </div>
    </div>
</div>
@endif
