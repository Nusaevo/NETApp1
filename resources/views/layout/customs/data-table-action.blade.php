<div class="text-left">
    @if ($enable_this_row)
        @if ($allow_details)
            <a href="#" wire:click="{{ $wire_click_show }}" data-bs-toggle="tooltip" data-bs-delay-show="500" data-bs-custom-class="tooltip-dark" data-bs-placement="top" title="Detail">
                <span class="svg-icon svg-icon-3" style="margin-right: 10px;">
                    <img src="{{ asset('images/view-icon.svg') }}" alt="View" style="width: 20px; height: 20px;">
                </span>
            </a>
        @endif

        @if ($allow_edit)
            <a href="#" wire:click="{{ $wire_click_edit }}" data-bs-toggle="tooltip" data-bs-delay-show="500" data-bs-custom-class="tooltip-dark" data-bs-placement="top" title="Edit">
                <span class="svg-icon svg-icon-3" style="margin-right: 10px;">
                    <img src="{{ asset('images/edit-icon.svg') }}" alt="Edit" style="width: 20px; height: 20px;">
                </span>
            </a>
        @endif

        @if ($allow_delete)
            <a href="#" wire:click="{{ $wire_click_delete }}" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm btn-delete" data-bs-toggle="tooltip" data-bs-delay-show="500" data-bs-custom-class="tooltip-dark" data-bs-placement="top" title="Delete">
                <span class="svg-icon svg-icon-3" style="margin-right: 10px;">
                    <span class="svg-icon svg-icon-3">
                        <img src="{{ asset('images/delete-icon.svg') }}" alt="Delete" style="width: 20px; height: 20px;">
                    </span>
                </span>
            </a>
        @endif
    @endif
</div>
