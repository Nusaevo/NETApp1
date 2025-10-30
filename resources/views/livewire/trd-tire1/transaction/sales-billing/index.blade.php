<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="table-container">
            @livewire($currentRoute.'.index-data-table')
        </div>
    </x-ui-page-card>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            // Auto-update functionality sudah dipindah ke dalam datatable custom-filters view
            // Script ini hanya untuk handling parent component communication jika diperlukan
        });
    </script>
@endpush
