<div class="modal fade custom-modal" id="{{ $id ?? 'default-dialog' }}" tabindex="-1" aria-labelledby="{{ $id ?? 'default-dialog-label' }}" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" style="width: {{ $width }}; height: {{ $height }};">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id ?? 'default-dialog-label' }}">{{ $title ?? '' }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ $body ?? '' }}
            </div>
            <div class="modal-footer">
                {{ $footer ?? '' }}
            </div>
        </div>
    </div>
</div>
