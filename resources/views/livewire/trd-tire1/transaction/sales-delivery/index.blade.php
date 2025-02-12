<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="table-container">
            @livewire($currentRoute . '.index-data-table', ['selectedItems' => $selectedItems])
        </div>
    </x-ui-page-card>

    <!-- Modal untuk Set Tanggal Kirim -->
    <x-ui-dialog-box id="modalDeliveryDate" title="Set Tanggal Kirim">
        <x-slot name="header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </x-slot>
        <x-slot name="body">
            <div class="form-group">
                <p>Nomor Nota yang dipilih :</p>
                <ul>
                    @foreach ($selectedItems as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <label for="deliveryDate">Tanggal Kirim</label>
                <input type="date" class="form-control @error('tr_date') is-invalid @enderror" id="deliveryDate"
                    wire:model="tr_date">
                @error('tr_date')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
                @dump(
                    $inputs,
                    $selectedItems,
                )
            </div>
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="submitDeliveryDate" button-name="Kirim" loading="true" :action="$actionValue"
                cssClass="btn-primary" />
        </x-slot>
    </x-ui-dialog-box>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            // Listener untuk membuka modal
            Livewire.on('open-modal-delivery-date', event => {
                $('#modalDeliveryDate').modal('show');
            });

            // Listener untuk menutup modal
            Livewire.on('close-modal-delivery-date', event => {
                $('#modalDeliveryDate').modal('hide');
            });

            // Handle modal hidden event
            $('#modalDeliveryDate').on('hidden.bs.modal', function() {
                @this.set('deliveryDate', '');
            });
        });
    </script>
@endpush
