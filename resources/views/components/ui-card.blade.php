<div class="card p-4 mb-4">
    {{-- Header --}}
    @if($title)
        <h5 class="mb-3">{{ $title }}</h5>
    @endif

    {{-- Body --}}
    <div>
        @isset($slot)
            {{ $slot }}
        @endisset
    </div>
</div>
