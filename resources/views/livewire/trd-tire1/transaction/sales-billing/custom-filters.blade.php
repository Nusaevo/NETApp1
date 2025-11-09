{{-- Custom content setelah filters untuk tanggal tagih --}}
<div class="d-flex flex-wrap gap-2 mb-3 p-3 bg-light rounded align-items-end">
    <div>
        <label class="form-label fw-bold">Tanggal Tagih</label>
        <input type="date"
               class="form-control"
               wire:model.defer="tanggalTagih"
               id="tanggalTagihInput"
               value="{{ $this->tanggalTagih ?? '' }}"
               style="max-width: 200px;">
    </div>
    <div>
        <button type="button"
                class="btn btn-success"
                wire:click="cetak"
                wire:loading.attr="disabled"
                wire:target="cetak">
            <span wire:loading.remove wire:target="cetak">
                <i class="fas fa-print me-1"></i>
                Cetak
            </span>
            <span wire:loading wire:target="cetak">
                <i class="fas fa-spinner fa-spin me-1"></i>
                Mencetak...
            </span>
        </button>
    </div>
    <div class="flex-grow-1 d-flex align-items-center">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Centang checkbox untuk auto-update tanggal tagih, lalu klik "Cetak" untuk mencetak
        </small>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', function() {
        console.log('Custom checkbox system initialized');

        // Update Livewire property when date changes
        document.addEventListener('change', function(e) {
            if (e.target.id === 'tanggalTagihInput') {
                @this.set('tanggalTagih', e.target.value);
            }
        });
    });
</script>
