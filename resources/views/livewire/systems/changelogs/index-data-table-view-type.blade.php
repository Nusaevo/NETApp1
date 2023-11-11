@if (isset($value))
    @if (strtolower($value) == 'feature')
        <span class="badge badge-info badge-square badge-sm">Feature</span>
    @elseif (strtolower($value) == 'improvement')
        <span class="badge badge-success badge-square badge-sm">Improvements</span>
    @elseif (strtolower($value) == 'hotfix')
        <span class="badge badge-danger badge-square badge-sm">Hot Fix</span>
    @else
        {{ $value }}
    @endif

@endif

