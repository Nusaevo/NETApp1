<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="table-container">
            @livewire($currentRoute . '.index-data-table')
        </div>
    </x-ui-page-card>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            Livewire.on('openPrintPdf', event => {
                window.location.href = '{{ route('TrdTire1.Tax.TaxInvoice.PrintPdf', ['action' => 'Print']) }}?orders=' + JSON.stringify(event.orders);
            });
        });
    </script>
@endpush
