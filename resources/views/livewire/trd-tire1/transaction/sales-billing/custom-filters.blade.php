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
                class="btn btn-primary"
                wire:click="autoUpdateSelected"
                wire:loading.attr="disabled"
                wire:target="autoUpdateSelected">
            <span wire:loading.remove wire:target="autoUpdateSelected">
                <i class="fas fa-calendar-check me-1"></i>
                Update Tanggal Tagih
            </span>
            <span wire:loading wire:target="autoUpdateSelected">
                <i class="fas fa-spinner fa-spin me-1"></i>
                Processing...
            </span>
        </button>
    </div>
    <div class="flex-grow-1 d-flex align-items-center">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Pilih data lalu klik tombol update
        </small>
    </div>
</div>

<script>
    // Simple functionality - no auto-update to prevent loading states
    document.addEventListener('livewire:initialized', function() {
        console.log('Tanggal tagih filter initialized - manual submit only');

        // Update Livewire property when date changes
        document.addEventListener('change', function(e) {
            if (e.target.id === 'tanggalTagihInput') {
                @this.set('tanggalTagih', e.target.value);
            }
        });
    });
</script>
