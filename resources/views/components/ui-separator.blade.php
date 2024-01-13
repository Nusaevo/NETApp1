<div class="col"
     @if (isset($visible) && $visible === 'false') style="display: none;" @endif>
    <div class="styled-separator {{ $type ?? 'default-type' }} {{ $orientation ?? 'default-orientation' }}"
         style="{{ (isset($orientation) && ($orientation === 'left' || $orientation === 'right')) ? 'margin-' . $orientation . ': ' . $orientationMargin ?? 5 . 'px;' : '' }}">
        @if (isset($header) && $header === 'true' && isset($caption))
            <h2>{{ $caption }}</h2>
        @endif
    </div>
</div>
