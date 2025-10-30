{{-- Custom content setelah filters untuk tanggal tagih --}}
<div class="d-flex flex-wrap gap-2 mb-3 p-3 bg-light rounded">
    <div class="flex-grow-1">
        <label class="form-label fw-bold">Tanggal Tagih</label>
        <input type="date"
               class="form-control"
               wire:model.live="tanggalTagih"
               id="tanggalTagihInput"
               value="{{ $this->tanggalTagih ?? now()->format('Y-m-d') }}"
               style="max-width: 200px;">
    </div>
    <div class="d-flex align-items-end">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Otomatis update saat pilih data
        </small>
    </div>
</div>

<script>
    // Auto-update functionality ketika ada perubahan selection dan tanggal
    document.addEventListener('livewire:initialized', function() {
        let debounceTimer;

        function triggerAutoUpdate() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                // Check apakah ada checkbox yang selected di dalam tbody
                const selectedCheckboxes = document.querySelectorAll('tbody input[type="checkbox"]:checked');

                if (selectedCheckboxes.length > 0) {
                    console.log('Auto-updating tanggal tagih for', selectedCheckboxes.length, 'selected items');
                    @this.call('autoUpdateSelected');
                }
            }, 300);
        }

        // Listen untuk perubahan checkbox selection di dalam tbody
        document.addEventListener('change', function(e) {
            if (e.target.type === 'checkbox' &&
                e.target.closest('tbody') &&
                e.target.checked) {
                console.log('Checkbox checked, triggering auto-update...');
                triggerAutoUpdate();
            }
        });

        // Listen untuk bulk selection (select all)
        document.addEventListener('click', function(e) {
            if (e.target.type === 'checkbox' &&
                e.target.closest('thead')) {
                setTimeout(() => {
                    triggerAutoUpdate();
                }, 200);
            }
        });

        // Listen untuk perubahan tanggal tagih - auto update selected records
        document.addEventListener('change', function(e) {
            if (e.target.id === 'tanggalTagihInput') {
                console.log('Tanggal tagih changed, auto-updating selected records...');
                triggerAutoUpdate();
            }
        });

        // Listen untuk Livewire events dan refresh table setelah update
        document.addEventListener('livewire:updated', function() {
            // Refresh table setelah update untuk melihat perubahan data
            setTimeout(() => {
                console.log('Refreshing table to show updated data...');
                Livewire.dispatch('refreshDatatable');
            }, 500);
        });
    });
</script>
