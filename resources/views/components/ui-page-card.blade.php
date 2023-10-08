<div id="kt_content_container" class="container-xxl mb-5" wire:ignore>
    <div class="card shadow-sm">
        <div>
            <h3 class="p-5">{{ $title }}</h3>
        </div>
        <div class="card-body">
            {{ $slot }}
        </div>
    </div>
</div>
