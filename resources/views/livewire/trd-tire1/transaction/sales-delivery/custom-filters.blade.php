<div x-data="{
    selectedItems: [],
    
    init() {
        console.log('Alpine.js initialized');
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
    {{-- Debug info --}}
    <!-- Custom Filters Loaded Successfully -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-gear"></i> Pengaturan Pengiriman
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Filter Controls & Action Buttons - Single Row -->
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label for="tanggal_kirim" class="form-label">Tanggal Kirim</label>
                            <input type="date"
                                   class="form-control"
                                   id="tanggal_kirim"
                                   wire:model="tanggalKirim"
                                   max="{{ date('Y-m-d') }}">
                            @error('tanggalKirim')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Warehouse</label>
                            <select wire:model="warehouse" class="form-select">
                                <option value="">Pilih Warehouse</option>
                                @foreach($warehouses as $wh)
                                    <option value="{{ $wh['value'] }}">{{ $wh['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <button type="button"
                                    class="btn btn-success"
                                    wire:click="setDeliveryDate">
                                <i class="bi bi-truck"></i> Kirim
                            </button>
                        </div>
                        <div class="col-md-auto">
                            <button type="button"
                                    class="btn btn-warning"
                                    wire:click="cancelDeliveryDate">
                                <i class="bi bi-x-octagon"></i> Batal Kirim
                            </button>
                        </div>
                        <div class="col-md-auto">
                            <button type="button"
                                    class="btn btn-danger"
                                    wire:click="cancel">
                                <i class="bi bi-ban"></i> Cancel
                            </button>
                        </div>
                        <div class="col-md-auto">
                            <button type="button"
                                    class="btn btn-info"
                                    wire:click="unCancel">
                                <i class="bi bi-arrow-counterclockwise"></i> Un-Cancel
                            </button>
                        </div>
                    </div>

                    <!-- Help Text with Selection Info -->
                    <div class="mt-3 flex-grow-1 d-flex align-items-center justify-content-between">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Pilih satu atau lebih nota dari tabel di bawah, atur tanggal kirim dan warehouse,
                            kemudian klik tombol aksi yang diinginkan.
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
        console.log('Sales Delivery - Simple version initialized');
        
        // Listen for checkbox changes
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('custom-checkbox')) {
                // Dispatch custom event for Alpine.js
                window.dispatchEvent(new Event('change'));
            }
        });
        
        // Listen for Livewire events to update Alpine.js state
        Livewire.on('selectionUpdated', () => {
            // Force Alpine.js to update selection
            setTimeout(() => {
                window.dispatchEvent(new Event('change'));
            }, 100);
        });

        // Function to clear all selections and reset UI
        function clearAllSelections() {
            console.log('Clearing all selections and resetting UI');
            
            // Clear backend selections
            @this.call('clearSelections');
            
            // Reset all checkbox UI
            document.querySelectorAll('.custom-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Update Alpine.js immediately
            window.dispatchEvent(new Event('change'));
            console.log('All selections cleared and UI reset');
        }

        // 1. Deteksi browser back navigation - Multiple methods
        window.addEventListener('pageshow', function(event) {
            console.log('Pageshow event:', { 
                persisted: event.persisted, 
                navigationType: performance.navigation ? performance.navigation.type : 'unknown'
            });
            
            // Deteksi jika halaman dimuat dari cache (browser back) ATAU navigation type = 2
            if (event.persisted || (performance.navigation && performance.navigation.type === 2)) {
                console.log('Browser back navigation detected');
                clearAllSelections();
            }
        });

        // 2. Deteksi popstate (browser back/forward button)
        window.addEventListener('popstate', function(event) {
            console.log('Popstate detected - browser navigation');
            setTimeout(() => {
                clearAllSelections();
            }, 50);
        });

        // 3. Deteksi saat component di-mount ulang (fallback)
        setTimeout(() => {
            // Check if navigation type indicates back navigation
            if (performance.navigation && performance.navigation.type === 2) {
                console.log('Back navigation detected on load');
                clearAllSelections();
            }
        }, 100);
    });
    </script>
</div>