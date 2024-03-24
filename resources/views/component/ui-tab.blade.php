<!-- components/ui-tab.blade.php -->

<div class="tab-pane fade @if(isset($active) && $active === 'true') show active @endif" id="{{ $id }}" role="tabpanel" aria-labelledby="{{ $id }}-tab">
    {{ $slot }}
</div>
