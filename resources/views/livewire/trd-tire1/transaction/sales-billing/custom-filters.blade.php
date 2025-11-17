<div x-data="{
    selectedItems: [],

    init() {
        console.log('Sales Billing Alpine.js initialized');
        this.updateSelection();
    },

    updateSelection() {
        const checkboxes = document.querySelectorAll('.custom-checkbox:checked');
        this.selectedItems = Array.from(checkboxes).map(cb => cb.value);
        console.log('Selected items:', this.selectedItems);
    },

    getSelectedCount() {
        return this.selectedItems.length;
    }
}" @change.window="updateSelection()">
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
    <div class="flex-grow-1 d-flex align-items-center justify-content-between">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Centang checkbox untuk auto-update tanggal tagih, lalu klik "Cetak" untuk mencetak
        </small>
        <div class="d-flex gap-2 align-items-center">
            <span x-show="getSelectedCount() > 0">
                <span class="badge bg-info">
                    <i class="fas fa-check-square me-1"></i>
                    <span x-text="getSelectedCount()"></span> item dipilih
                </span>
                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        wire:click="clearSelections"
                        title="Clear Selection">
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </span>
        </div>
    </div>
    </div>

    <style>
    /* Badge styling */
    .badge {
        font-size: 0.75em !important;
        padding: 0.2em 0.4em;
    }

    /* Alpine.js transitions */
    [x-cloak] {
        display: none !important;
    }
    </style>

    <script>
    document.addEventListener('livewire:initialized', function() {
        console.log('Sales Billing - Simple version initialized');

        // Listen for Livewire events to update Alpine.js state
        Livewire.on('selectionUpdated', () => {
            // Force Alpine.js to update selection
            setTimeout(() => {
                window.dispatchEvent(new Event('change'));
            }, 100);
        });

        // Update Livewire property when date changes
        document.addEventListener('change', function(e) {
            if (e.target.id === 'tanggalTagihInput') {
                @this.set('tanggalTagih', e.target.value);
            }

            // Handle checkbox changes untuk mengelola tanggal tagih
            if (e.target.classList.contains('custom-checkbox')) {
                const rowId = e.target.value;
                const isChecked = e.target.checked;

                console.log(`Checkbox ${rowId} changed to: ${isChecked}`);

                // Dispatch event for Alpine.js
                window.dispatchEvent(new Event('change'));

                const tanggalInput = document.getElementById('tanggalTagihInput');

                if (isChecked) {
                    // Jika checkbox di-check dan sudah ada tanggal tagih yang dipilih
                    const currentTanggalTagih = tanggalInput ? tanggalInput.value : '';

                    if (currentTanggalTagih) {
                        // Update ke tanggal tagih baru
                        console.log(`Updating row ${rowId} dengan tanggal tagih: ${currentTanggalTagih}`);
                        @this.call('updateTanggalTagih', rowId, currentTanggalTagih);
                    } else {
                        // Jika belum ada tanggal tagih, clear dulu
                        @this.call('clearTanggalTagih', rowId);
                    }
                } else {
                    // Jika checkbox di-uncheck, kosongkan tanggal tagih
                    console.log(`Clearing tanggal tagih for row ${rowId}`);
                    @this.call('clearTanggalTagih', rowId);
                }
            }
        });

        // Function to clear all selections and reset UI
        function clearAllSelections() {
            console.log('Sales Billing - Clearing all selections and resetting UI');

            // Clear backend selections
            @this.call('clearSelections');

            // Reset all checkbox UI
            document.querySelectorAll('.custom-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Update Alpine.js immediately
            window.dispatchEvent(new Event('change'));
            console.log('Sales Billing - All selections cleared and UI reset');
        }

        // 1. Deteksi browser back navigation - Multiple methods
        window.addEventListener('pageshow', function(event) {
            console.log('Sales Billing Pageshow event:', {
                persisted: event.persisted,
                navigationType: performance.navigation ? performance.navigation.type : 'unknown'
            });

            // Deteksi jika halaman dimuat dari cache (browser back) ATAU navigation type = 2
            if (event.persisted || (performance.navigation && performance.navigation.type === 2)) {
                console.log('Sales Billing - Browser back navigation detected');
                clearAllSelections();
            }
        });

        // 2. Deteksi popstate (browser back/forward button)
        window.addEventListener('popstate', function(event) {
            console.log('Sales Billing - Popstate detected - browser navigation');
            setTimeout(() => {
                clearAllSelections();
            }, 50);
        });

        // 3. Deteksi saat component di-mount ulang (fallback)
        setTimeout(() => {
            // Check if navigation type indicates back navigation
            if (performance.navigation && performance.navigation.type === 2) {
                console.log('Sales Billing - Back navigation detected on load');
                clearAllSelections();
            }
        }, 100);
    });
    </script>
</div>
