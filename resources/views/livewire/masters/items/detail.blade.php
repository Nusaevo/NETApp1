<div>
    <div>
        <a href="{{ route('item.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i> Kembali </a>
    </div>
    <div>
        @include('layout.customs.notification')
    </div>
   @livewire('masters.items.details.item-variant-detail', ['item' => $item])
</div>
