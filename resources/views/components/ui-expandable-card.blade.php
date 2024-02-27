<div class="card border border-dark " wire:ignore.self>
    @isset($id)
        <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#{{ $id }}">
            @isset($title)
                <h3 class="card-title">{{ $title }}</h3>
            @endisset
            <div class="card-toolbar rotate-180">
                <i class="bi bi-arrow-bar-down"></i>
            </div>
        </div>
        @isset($isOpen)
            <div id="{{ $id }}" class="{{ $isOpen == 'true' ? 'show' : 'collapse' }}" >
                <div class="card-body">
                    @isset($slot)
                        {{ $slot }}
                    @endisset
                </div>
            </div>
        @endisset
    @endisset
</div>
