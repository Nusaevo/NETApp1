{{-- ui-tab-view.blade.php --}}
<div>
    <ul class="nav nav-tabs" id="{{ $id }}" role="tablist">
        @php
            // If $tabs is not an array, convert it into an array
            $tabItems = is_array($tabs) ? $tabs : explode(',', $tabs);
        @endphp

        @foreach ($tabItems as $tab)
            <li class="nav-item" role="presentation">
                <button class="nav-link{{ $loop->first ? ' active' : '' }}" id="{{ $tab }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tab }}" type="button" role="tab" aria-controls="{{ $tab }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ ucfirst($tab) }}</button>
            </li>
        @endforeach
    </ul>
</div>
