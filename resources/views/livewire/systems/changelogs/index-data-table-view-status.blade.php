@if (isset($value))
    @if (strtolower($value) == 'released')
        <span class="badge badge-success badge-square badge-sm">Released</span>
    @else
        {{ $value }}
    @endif

@endif

