<div class="card border border-dark m-5">
    <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#{{ $id }}" wire:ignore.self>
        <h3 class="card-title">{{ $title }}</h3>
        <div class="card-toolbar rotate-180">
            <i class="bi bi-arrow-bar-down"></i>
        </div>
    </div>
    <div id="{{ $id }}" class="{{ $isOpen ? 'show' : 'collapse' }}" wire:ignore.self>
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>
</div>
