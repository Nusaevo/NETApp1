{{-- Custom content setelah filters untuk tanggal kirim dan warehouse --}}
<div class="d-flex flex-wrap gap-2 mb-3 p-3 bg-light rounded align-items-end">
    <div>
        <label class="form-label fw-bold">Tanggal Kirim</label>
        <input type="date"
               class="form-control"
               wire:model.defer="tanggalKirim"
               id="tanggalKirimInput"
               value="{{ $this->tanggalKirim ?? '' }}"
               style="max-width: 200px;">
    </div>
    <div>
        <label class="form-label fw-bold">Warehouse</label>
        <select class="form-select"
                wire:model.defer="warehouse"
                id="warehouseInput"
                style="max-width: 200px;">
            <option value="">Pilih Warehouse</option>
            @foreach($warehouses ?? [] as $warehouse)
                <option value="{{ $warehouse['value'] }}">{{ $warehouse['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex-grow-1 d-flex align-items-center">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Isi tanggal kirim dan warehouse, lalu pilih nota dan klik aksi "Kirim" di bulk actions untuk langsung memproses delivery
        </small>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', function() {
        console.log('Custom delivery filters initialized');

        // Update Livewire property when date changes
        document.addEventListener('change', function(e) {
            if (e.target.id === 'tanggalKirimInput') {
                @this.set('tanggalKirim', e.target.value);
            }
            if (e.target.id === 'warehouseInput') {
                @this.set('warehouse', e.target.value);
            }
        });
    });
</script>

