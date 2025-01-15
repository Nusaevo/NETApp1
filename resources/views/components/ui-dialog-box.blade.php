<div class="modal fade custom-modal" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}-label" aria-hidden="true"
    wire:ignore.self>
    <div class="modal-dialog" style="max-width: {{ $width }}; max-height: {{ $height }};">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $id }}-label">{{ $title }}</h5>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalId = '#{{ $id }}';

        // Listen for open and close events
        window.addEventListener('{{ $onOpened }}', function () {
            $(modalId).modal('show');
        });

        window.addEventListener('{{ $onClosed }}', function () {
            $(modalId).modal('hide');
        });
    });
</script>
