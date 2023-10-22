<div class="text-left">
    @if ($enable_this_row)
        @if ($allow_details)
            <button class="btn btn-secondary icon-button" wire:click="{{ $wire_click_show }}" data-bs-toggle="tooltip" data-bs-delay-show="500" data-bs-custom-class="tooltip-dark" data-bs-placement="top" title="Detail">
                <span class="svg-icon svg-icon-3">
                    <img src="{{ asset('images/view-icon.svg') }}" alt="View" style="width: 24px; height: 24px;">
                </span>
            </button>
        @endif

        @if ($allow_edit)
            <button class="btn btn-secondary icon-button" wire:click="{{ $wire_click_edit }}" data-bs-toggle="tooltip" data-bs-delay-show="500" data-bs-custom-class="tooltip-dark" data-bs-placement="top" title="Edit">
                <span class="svg-icon svg-icon-3">
                    <img src="{{ asset('images/edit-icon.svg') }}" alt="Edit" style="width: 24px; height: 24px;">
                </span>
            </button>
        @endif

        @if ($allow_delete)
            <button class="btn btn-danger icon-button btn-delete" wire:click="{{ $wire_click_delete }}" data-bs-toggle="tooltip" data-bs-delay-show="500" data-bs-custom-class="tooltip-dark" data-bs-placement="top" title="Delete">
                <span class="svg-icon svg-icon-3">
                    <span class="svg-icon svg-icon-3">
                        <img src="{{ asset('images/delete-icon.svg') }}" alt="Delete" style="width: 24px; height: 24px;">
                    </span>
                </span>
            </button>
        @endif

        @if ($allow_disable)
        <button class="btn btn-danger icon-button btn-delete" wire:click="{{ $wire_click_disable }}" data-bs-toggle="tooltip" data-bs-delay-show="500" data-bs-custom-class="tooltip-dark" data-bs-placement="top" title="Disable">
            <span class="svg-icon svg-icon-3">
                <span class="svg-icon svg-icon-3">
                    <img src="{{ asset('images/disable-icon.svg') }}" alt="Disable" style="width: 24px; height: 24px;">
                </span>
            </span>
        </button>
        @endif
    @endif
</div>
