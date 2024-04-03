<div class="card border border-dark" wire:ignore.self>
    <div class="card-body">
    @isset($slot)
        {{ $slot }}
    @endisset
</div>
</div>
