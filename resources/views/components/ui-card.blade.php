<div
    class="card p-4 mb-4"
    @if($id) id="{{ $id }}" @endif
    style="
        width: {{ $width }};
        height: {{ $height }};
        overflow: auto;
    "
>
    {{-- Header --}}
    @if($title)
        <h5 class="mb-3">{{ $title }}</h5>
    @endif

    {{-- Body --}}
    <div style="max-width: 100%; max-height: 100%; overflow: auto;">
        @isset($slot)
            {{ $slot }}
        @endisset
    </div>
</div>
