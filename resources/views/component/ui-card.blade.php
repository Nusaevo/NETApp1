<div class="card border border-dark " style='padding:20px;' wire:ignore.self>
    @isset($slot)
        {{ $slot }}
    @endisset
</div>
