@if (isset($action) && $action !== 'View')
<div class="{{ $type === 'close' ? 'close-button' : '' }}">
    <span style="padding: 2px;">
        <a wire:click="{{ isset($clickEvent) ? $clickEvent : '#' }}"
           class="btn {{ isset($cssClass) ? $cssClass : '' }} btn-link-text"
           @if (isset($visible) && $visible === 'false') style="display: none;" @endif
           @if (isset($enabled) && $enabled === 'false') disabled @endif
           @if (isset($id)) id="{{ $id }}" @endif>
            <span style="font-size: 16px;">{{ isset($name) ? $name : 'link' }}</span>
        </a>
    </span>
</div>
@endif
